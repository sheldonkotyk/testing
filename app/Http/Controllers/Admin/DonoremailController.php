<?php  namespace App\Controllers\Admin;
 
    use Auth;
use BaseController;
use Form;
use Input;
use Redirect;
use Sentry;
use View;
use RedisL4;
use Validator;
use Session;
use DB;
use Donoremail;
use Donor;
use Entity;
use Program;
use Group;
use User;
use Mail;
use Emailsetting;
use URL;
use Upload;
use Emailtemplate;
    
use App\Http\Controllers\Controller;

class DonoremailController extends Controller
{
        
    public function viewEmailManager()
    {
        $user = Sentry::getUser();
        $permissions = Session::get('permissions');
        $programs = Program::whereClientId(Session::get('client_id'))->get();
        $program_ids = [];
        foreach ($programs as $program) {
            $p = 'program-'.$program->id.'';
            if (isset($permissions->$p) && $permissions->$p == 1) {
                $program_ids[] = $program->id;
            }
        }
        $raw_emails = Donoremail::withTrashed()->whereClientId(Session::get('client_id'))->where('parent_id', 0)->get();
        $emails = [];
        $a_emails = [];
        $redis = RedisL4::connection();
        foreach ($raw_emails as $email) {
            // only add emails the admin has permissions for
            $donor = new Donor;
            $entity_name = $donor->getEntityName($email->entity_id); // returns array with id and name

            if (in_array($entity_name['program_id'], $program_ids)) {
                $entity = new Entity;
                $donor_name = $entity->getDonorName($email->donor_id); // returns array with id, hysform_id, and name
                    
                // array with all email
                if ($email->from=='entity') {
                    $emails[] = [
                    'id' => $email->id,
                    'status' => $email->status, // 1 = new, 2 = In Process, 3 = Complete
                    'to' => $donor_name,
                    'from' => $entity_name,
                    'subject' => strip_tags($email->subject),
                    'message' => strip_tags($email->message),
                    'date' => $email->created_at
                    ];
                } else {
                    $emails[] = [
                    'id' => $email->id,
                    'status' => $email->status, // 1 = new, 2 = In Process, 3 = Complete
                    'to' => $entity_name,
                    'from' => $donor_name,
                    'subject' => strip_tags($email->subject),
                    'message' => strip_tags($email->message),
                    'date' => $email->created_at
                    ];
                }
                    
                // array with only assigned email
                if ($email->admin_assigned == $user->id && !$email->trashed()) {
                    $a_emails[] = [
                    'id' => $email->id,
                    'status' => $email->status, // 1 = new, 2 = In Process, 3 = Complete
                    'to' => $entity_name,
                    'from' => $donor_name,
                    'subject' => strip_tags($email->subject),
                    'message' => strip_tags($email->message),
                    'date' => $email->created_at
                    ];
                }
            }
        }
        return view('admin.views.viewEmailManager', [
            'emails' => $emails,
            'a_emails' => $a_emails
        ]);
    }
        
    public function viewEmail($email_id)
    {
        $donor = new Donor;
        $entity = new Entity;
        $redis = RedisL4::connection();
                        
        $e = Donoremail::withTrashed()->whereId($email_id)->first();
        $disabled= '';
        $adonor = Donor::find($e->donor_id);
        if (!$adonor) {
            $adonor = Donor::withTrashed()->find($e->donor_id);
            if (!$adonor) {
                Session::flash('message', 'Error: This donor has been deleted, so no further messages may be sent.');
                Session::flash('alert', 'danger');
                $disabled='disabled';
            } else {
                Session::flash('message', 'Warning: This donor has been archived, no further messages may be sent until you <a href="'.URL::to('admin/edit_donor/'.$e->donor_id).'">restore this donor</a>.');
                Session::flash('alert', 'warning');
                $disabled= 'disabled';
            }
        }
        if ($e->from=='entity') {
            $email = [
            'id' => $e->id,
            'status' => $e->status, // 1 = new, 2 = In Process, 3 = Complete
            'to' => $entity->getDonorName($e->donor_id),
            'from_name' => $donor->getEntityName($e->entity_id),
            'from'=> $e->from,
            'subject' => $e->subject,
            'message' => $e->message,
            'date' => $e->created_at,
            'admin_assigned' => $e->admin_assigned
            ];
            $program_id = $email['from_name']['program_id'];
        } else {
            $email = [
                'id' => $e->id,
                'status' => $e->status, // 1 = new, 2 = In Process, 3 = Complete
                'to' => $donor->getEntityName($e->entity_id),
                'from_name' => $entity->getDonorName($e->donor_id),
                'from' => $e->from,
                'subject' => $e->subject,
                'message' => $e->message,
                'date' => $e->created_at,
                'admin_assigned' => $e->admin_assigned
            ];
            $program_id = $email['to']['program_id'];
        }
        // get any responses
        $responses = Donoremail::whereParentId($e->id)->get();
            
        // groups with permission for this entity
        $groups = Group::whereClientId(Session::get('client_id'))->get();
        foreach ($groups as $group) {
            $permissions = json_decode($group->permissions, true);
                
            if (isset($permissions['program-'.$program_id.''])) {
                $group_ids[] = $group->id;
            }
        }
            
        // get admins with permissions
        $admins = User::whereIn('group_id', $group_ids)->get();
        return view('admin.views.viewEmail', [
            'email' => $email,
            'admins' => $admins,
            'responses' => $responses,
            'disabled'=>$disabled
            ]);
    }
        
    public function assignAdmin($email_id)
    {
        $data = Input::all();
        if (!empty($data['admin'])) {
            $email = Donoremail::withTrashed()->find($email_id);
            $email->admin_assigned = $data['admin'];
            $email->save();
                
            // notify admin
            $admin = User::find($data['admin']);
            unset($data);
            $data['body'] = "A new email message from a sponsor has been assigned to you.";
            $name = ''.$admin->first_name.' '.$admin->last_name.'';
                
            Mail::queue('emails.adminEmailTemplate', $data, function ($message) use ($admin, $name) {
                $message->to($admin->email, $name)->subject('New sponsor email assigned');
            });
        }
        return redirect('admin/view_email/'.$email_id.'');
    }
        
    public function updateEmailStatus($email_id)
    {
        $email = Donoremail::withTrashed()->whereId($email_id)->first();
        switch ($email->status) {
            case 1:
                $status = 2;
                $span = '<span class="label label-warning">In Process</span>';
                break;
            case 2:
                $status = 3;
                $span = '<span class="label label-default">Complete</span>';
                break;
            case 3:
                $status = 1;
                $span = '<span class="label label-success">New</span>';
                if ($email->trashed()) {
                    $email->restore();
                }
                break;
        }
        $email->status = $status;
        $email->save();
            
        if ($status == 3) {
            $email->delete();
        }
            
        return $span;
    }
        
    public function sendEmailResponse($email_id)
    {
        $data = Input::all();

        $email = Donoremail::withTrashed()->whereId($email_id)->first();

        $donor = Donor::find($email->donor_id);
            
        if (!$donor) {
            $trashed =false;

            $donor = Donor::withTrashed()->find($email->donor_id);

            if (!$donor) {
                Session::flash('message', 'Error: This donor has been deleted, so no further messages may be sent.');
                Session::flash('alert', 'Danger');
            } else {
                Session::flash('message', 'Warning: This donor has been archived, no further messages may be sent until you <a href="'.URL::to('admin/edit_donor/'.$email->donor_id).'">restore this donor</a>.');
                Session::flash('alert', 'warning');
            }


            return redirect('admin/view_email/'.$email_id.'');
        }

        $response = new Donoremail;
        $response->message = $data['response'];
        $response->parent_id = $email_id;
        $response->save();
        unset($data);

        $client_id=Session::get('client_id');
        $settings = Emailsetting::where('client_id', $client_id)->first();
        $from_email = 'no_reply@helpyousponsor.org';
        $from_name = 'Help You Sponsor';
        if (count($settings)) {
            $from_email=$settings->from_address;
            $from_name=$settings->from_name;
        }
            
            
        // return var_dump($email);

            

        $redis = RedisL4::connection();
        $entity = new Entity;
        $name = $entity->getDonorName($email->donor_id);
        $data = [
            'body' => $response->message,
            'to' => 'donor',
            'id' => $email->donor_id
        ];

        if ($donor->do_not_email != 1) {
            Mail::queue('emails.donorEmailTemplate', $data, function ($message) use ($donor, $email, $name, $from_email, $from_name) {
                $message->from($from_email, $from_name)->to($donor->email, $name['name'])->subject($email->subject);
            });
        }
        return redirect('admin/view_email/'.$email_id.'');
    }

    public function sendMessage($entity_id, $donor_id, $from, $file_id = null)
    {
        $e = new Entity;
        $d = new Donor;
        $up = new Upload;
        $entities = [];
        $donors = [];
        $s = '';
            
        if ($from=='donor') {
            $file = Upload::where('donor_id', $donor_id)->find($file_id);
        }
        if ($from=='entity') {
            $file= Upload::where('entity_id', $entity_id)->find($file_id);
        }

        if ($entity_id=='all') {
            $entities = $e->getEntitiesFromDonor($donor_id);
        } else {
            $entities = [$d->getEntityName($entity_id)];
        }

        if ($donor_id =='all') {
            $donors = $e->getSponsors($entity_id);
        } else {
            $donors = [$e->getDonorName($donor_id)];
        }

        if ($from=='donor') {
            $from_title=$from;
            $upload = new Upload;
            $uploads = Donor::withTrashed()->find($donor_id)->uploads()->where('profile', 1)->first();
            $profileThumb = '';
            if (!empty($uploads)) {
                $profileThumb = $uploads->makeAWSlinkThumb($uploads);
            }
            $from_name = reset($donors)['name'];
            $recipients = $entities;
            if (count($recipients)>1) {
                $to = 'Recipients';
            } else {
                $to = 'Recipient';
            }
        }
        if ($from=='entity') {
            $from_title='Recipient';
            $upload = new Upload;
            $uploads = Entity::withTrashed()->find($entity_id)->uploads()->where('profile', 1)->first();
            $profileThumb = '';
            if (!empty($uploads)) {
                $profileThumb = $uploads->makeAWSlinkThumb($uploads);
            }
            $from_name = reset($entities)['name'];
            $recipients = $donors;
            if (count($recipients)>1) {
                $to = 'Donors';
            } else {
                $to = 'Donor';
            }
        }

        return view('admin.views.sendMessage', [
            'entity_id'=>$entity_id,
            'donor_id' => $donor_id,
            'file_id'=> $file_id,
            'entities' => $entities,
            'donors' => $donors,
            'from' => $from,
            'from_title' =>$from_title,
            'to' => $to,
            's' => $s,
            'from_name'=> $from_name,
            'recipients' => $recipients,
            'file'=>$file,
            'thumbnail'=>$up->makeAWSlinkThumb($file),
            'link' => $up->makeAWSlink($file),
            'profileThumb'=>$profileThumb
            ]);
    }

    public function postSendMessage($entity_id, $donor_id, $from, $file_id = null)
    {

        $body = Input::get('message');
        $subject = Input::get('subject');
        $e = new Entity;
        $d = new Donor;
        $up = new Upload;
        $entities = [];
        $donors = [];
        $s = '';
        if ($from=='donor') {
            $file = Upload::where('donor_id', $donor_id)->find($file_id);
        }
        if ($from=='entity') {
            $file= Upload::where('entity_id', $entity_id)->find($file_id);
        }

        if ($entity_id=='all') {
            $entities = $e->getEntitiesFromDonor($donor_id);
        } else {
            $entities = [$d->getEntityName($entity_id)];
        }

        if ($donor_id =='all') {
            $donors = $e->getSponsors($entity_id);
        } else {
            $donors = [$e->getDonorName($donor_id)];
        }

        if ($from=='donor') {
            $donor = Donor::find($donor_id);
            $client_id=$donor->client_id;
            $upload = new Upload;
            $uploads = Donor::withTrashed()->find($donor_id)->uploads()->where('profile', 1)->first();
            $profileThumb = '';
            if (!empty($uploads)) {
                $profileThumb = $uploads->makeAWSlinkThumb($uploads);
            }
            $from_name = reset($donors)['name'];
            $recipients = $entities;
            if (count($recipients)>1) {
                $to = 'Recipients';
            } else {
                $to = 'Recipient';
            }
        }
        if ($from=='entity') {
            $entity= Entity::find($entity_id);
            $client_id= $entity->client_id;
            $upload = new Upload;
            $uploads = Entity::withTrashed()->find($entity_id)->uploads()->where('profile', 1)->first();
            $profileThumb = '';
            if (!empty($uploads)) {
                $profileThumb = $uploads->makeAWSlinkThumb($uploads);
            }
            $from_name = reset($entities)['name'];
            $recipients = $donors;
            if (count($recipients)>1) {
                $to = 'Donors';
            } else {
                $to = 'Donor';
            }
        }

            


        $message = '';
        if (!empty($body)) {
            $message = $body.'<br>';
        }

        if ($file!=null) {
            $message.= "Attached File Included: ". $file->file_name;
            if ($file['type']=='image') {
                $message.='<br/>Click on the Thumbnail to view the file<br/><a href="'.$up->makeAWSlink($file).'"><img src="'.$up->makeAWSlinkThumb($file).'" width="100"></a>';
            } else {
                $message.= '<br>File Link: <a href="'.$up->makeAWSlink($file).'">'.$file->file_name.'</a>';
            }
        }
        foreach ($recipients as $r) {
            $email = new Donoremail;

            $email->subject=$subject;
            $email->message=$message;
            $email->client_id=$client_id;

            if ($from=='entity') {
                $email->from='entity';
                $email->entity_id=$entity_id;
                $email->donor_id=$r['id'];
                $email->status=1; //1 = new email
                $email->save();


                //Send an email to the Donor letting him know that the recipient has messaged him on HYS
                $donor_email= new Emailtemplate;

                $details['entity'] = $donor_email->getEntity($entity_id);
                $details['donor'] = $donor_email->getDonor($donor_id);

                $entity = Entity::find($entity_id);

                $url = URL::to('frontend/login', [Session::get('client_id'),$entity->program_id]);

                $link = '<a href="'.$url.'">Click here to login and view Message.</a>';

                $details['message']= ['login_link' => $link,'url'=>$url , 'subject' => $subject];

                $designation = $d->getDesignation(1, $entity_id);

                $to_info= ['type' => 'donor','email' => $r['email'] ,'name' => $r['name'],'id'=>$r['id']];

                $emailSent = $donor_email->sendEmail($designation['emailset_id'], $details, 'recip_email', $to_info);
            }
            if ($from=='donor') {
                $email->from='donor';
                $email->donor_id=$donor_id;
                $email->entity_id=$r['id'];
                $email->status=1; //1 = new email
                $email->save();
            }
        }

        if ($from=='donor') {
            return redirect('admin/edit_donor/'.$donor_id)->with('message', 'Message successfully sent to '.count($recipients).' '. $to)->with('alert', 'success');
        } elseif ($from=='entity') {
            return redirect('admin/edit_entity/'.$entity_id)->with('message', 'Message successfully sent to '.count($recipients).' '. $to)->with('alert', 'success');
        }
    }
}
