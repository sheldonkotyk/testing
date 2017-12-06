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
use Donor;
use Donorfield;
use Upload;
use Hash;
use Entity;
use Field;
use Program;
use Client;
use Response;
use DonorEntity;
use Hysform;
use Carbon;
use Mail;
use Emailtemplate;
use Emailset;
use Commitment;
use Donation;
use Report;
use URL;
use Queue;
use Config;
use Cache;
use User;
    
use App\Http\Controllers\Controller;

class DonorController extends Controller
{
        
    public function addDonor($hysform_id)
    {
        $hysform = Hysform::find($hysform_id);
        $fields = Donorfield::where('hysform_id', $hysform_id)->orderBy('field_order')->get();
        $emailset = new Emailset;


        $emailsets = $emailset->getEmailSets($hysform_id);

        if ($emailsets==false) {
            return redirect('admin/show_all_donors/'.$hysform_id);
        }

        $donor =new Donor;
        $counts=$donor->getHysformCounts($hysform_id);
            
        return view('admin.views.addDonor')
            ->with([
                'fields'    => $fields,
                'hysform'   => $hysform,
                'hysform_id'=> $hysform->id,
                'counts'    => $counts,
                'emailsets' => $emailsets]);
    }

    public function rand_string($length)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        return substr(str_shuffle($chars), 0, $length);
    }
        
    public function postAddDonor($hysform_id)
    {

        $data = Input::all();
        unset($data['_token']);
            
        $rules = [
            'username'  =>  'unique:donors|min:5',
            'email'     =>  'email|unique:donors,email,NULL,id,client_id,'.Session::get('client_id'),
        ];
           
        $validator = Validator::make($data, $rules);
            
        if ($validator->passes()) {
            $hysform= Hysform::find($hysform_id);
            $count = $hysform->counter + 1;
            $hysform->counter = $count;
            $hysform->save();

            $counter = $hysform->prefix.$count;
            $fields = Donorfield::whereHysformId($hysform_id)->get();
            foreach ($fields as $field) {
                $field_types[$field->field_key] = $field->field_type;
            }

            $donor = new Donor;

            //make random password eight characters long
            $random_password = $donor->rand_string(8);
            $data['password'] = $random_password;

            $redis = RedisL4::connection();
            $password = Hash::make($data['password']);
                
            $donor->client_id = Session::get('client_id');
            $donor->hysform_id = $hysform_id;
            $donor->username = $data['username'];
            $donor->email = $data['email'];
            $donor->do_not_email = 0;
            $donor->password = $password;
                
            //Record which admin added this donor
            $donor->who_added= json_encode(['type'=>'admin','method'=>'individual','id'=>Sentry::getUser()->id]);
                

            if ($data['notify_donor'] != 'no') {
                // return var_dump('Not Disabled');
                if (!empty($data['email'])) {
                    // return var_dump('Not empty');
                    $e = new Entity;
                    $emailtemplate = new Emailtemplate;
                    $name = $e->getDonorName($donor->id);
                    $to = ['type' => 'donor', 'name' => $name['name'], 'email' => $data['email'], 'id' => $donor->id];
                    $details['login_info'] = $data;
                    $details['donor'] = $emailtemplate->getDonor($donor->id);
                    //Email the donor and give them their auto generated temporary password
                    $sent = $emailtemplate->sendEmail($data['notify_donor'], $details, 'notify_donor', $to);

                    // return var_dump($sent);
                }
            }
            unset($data['notify_donor']);
            unset($data['email']);
            unset($data['username']);
            unset($data['password']);

                
            $hash = "donor:id:{$donor->id}";
            $profile = [];
            foreach ($data as $k => $v) {
                if (is_array($v)) {
                    $link = '';
                    foreach ($v as $part) {
                        if (!empty($part)) {
                            $link .= ''.$part.'|';
                        }
                    }
                    $v = substr($link, 0, -1); // Removes the last pipe
                }
                if ($field_types[$k] == 'hysCustomid') {
                    $v = $counter;
                }
                $profile[$k] = "$v";
            }
                    

            $donor->json_fields = json_encode($profile);

            $donor->save();

            $donor->reloadDonorsToCache($donor);

            $result = $donor->syncDonorsToMailchimp($donor);
            $mailchimp = '';

            // $result;

            if (isset($result['add_count'])&&$result['add_count']>0) {
                $mailchimp = " and added to your Mailchimp list <strong>".$result['name']."</strong>";
            }

            Session::flash('message', Session::get('message').'The user account <strong>'.$donor->username.'</strong> has been created'.$mailchimp.'.');
            Session::flash('alert', 'success');

            return redirect('admin/edit_donor/'.$donor->id.'')
                ->withInput();
        }
        return redirect('admin/add_donor/'.$hysform_id.'')
            ->withErrors($validator)
            ->withInput();
    }
    
    public function showAllDonors($hysform_id, $trashed = false)
    {

        $d = new Donor;

        $hysform = Hysform::where('client_id', Session::get('client_id'))->where('type', 'donor')->find($hysform_id);

        if (count($hysform)==0) {
            return "Error: Donor Form Not Found.";
        }

        $vars = $d->getDonors($hysform_id, $trashed);

        return view('admin.views.showAllDonors')
            ->with($vars);
    }

    public function showAllDonorsTable($hysform_id, $trashed = false)
    {
        // check for saved preferences
        // if none load first 6 fields

        $d = new Donor;
        $vars = $d->getDonorsTable($hysform_id, $trashed);

        return view('admin.views.showAllDonorsTable', $vars);
    }

    public function showAllDonorsAjax($hysform_id, $trashed = false)
    {

        $user=Sentry::getUser();
        $redis = RedisL4::connection();
        $d=new Donor;
        $details= $d->getDonorAmountDetailsForWholeTable();
        $hash = "admin:{$user->id}:donor-$hysform_id";
        $admin = $redis->hgetall($hash);
        $manage = false;
        $thumb = false;
        $created_at = false;
        $updated_at = false;
        $email = false;
        $username = false;
        $details_display=[];

        if (!empty($admin['donor'])) {
            $fields = json_decode($admin['donor']);
            if (isset($fields->manage)) {
                $manage = $fields->manage;
                unset($fields->manage);
            }
            if (isset($fields->email)) {
                $email = $fields->email;
                unset($fields->email);
            }
            if (isset($fields->username)) {
                $username = $fields->username;
                unset($fields->username);
            }
            if (isset($fields->thumb)) {
                $thumb = $fields->thumb;
                unset($fields->thumb);
            }
            if (isset($fields->created_at)) {
                $created_at = $fields->created_at;
                unset($fields->created_at);
            }
            if (isset($fields->updated_at)) {
                $updated_at = $fields->updated_at;
                unset($fields->updated_at);
            }
            foreach ($details as $name => $detail) {
                $n=strtolower(str_replace(' ', '_', $name));
                if (isset($fields->{$n})) {
                    $details_display[$n]=$name;
                    unset($fields->{$n});
                }
            }
        }
            
        if (!isset($fields)) {
            $fields= $fields = Donorfield::where('hysform_id', $hysform_id)->orderBy('field_order')->get();
            $manage = true;
        }


        $vars = $d->getDonorsAjax($hysform_id, URL::to(''), $trashed);

        $hashes= $vars['hashes'];
        $pipeline= $vars['pipeline'];

        if (!empty($hashes)) {
            $d = new Donor;
            $e = new Entity;
            foreach ($hashes as $k => $hash) {
                //Set the Donor id as the first field.
                $profiles[$k][] = $k;
                
                //Set the manage field with it's buttons
                if ($manage) {
                    $profiles[$k][]=$hash['manage'];
                }

                //Set the fields that the particular user has selected to view
                foreach ($fields as $f) {
                    if (isset($f->field_key)) {
                        if (isset($pipeline[$k]['hys_profile'][$f->field_key])) {
                            $profiles[$k][]=$pipeline[$k]['hys_profile'][$f->field_key];
                        }
                    }
                }

                if ($email) {
                    $profiles[$k][]= $hash['email'];
                }
                
                if ($username) {
                    $profiles[$k][]= $hash['username'];
                }

                foreach ($details_display as $name => $detail) {
                    if (isset($hash[$detail])) {
                        $profiles[$k][]= $hash[$detail];
                    }
                }

                if ($created_at) {
                    $profiles[$k][] = $hash['created_at'];
                }
                if ($updated_at) {
                    $profiles[$k][] = $hash['updated_at'];
                }
                $processed[] = $profiles[$k];
            }
        } else {
            $processed = [];
        }

        return json_encode(['data'=>$processed]);
    }

    public function editDonor($id)
    {

        $donor = Donor::where('client_id', Session::get('client_id'))->withTrashed()->find($id);

        if (count($donor)==0) {
            return "Error: Donor Not Found.";
        }

        $profile = json_decode($donor->json_fields, true);

        $hysform= Hysform::find($donor->hysform_id);
        $fields = Donorfield::where('hysform_id', $donor->hysform_id)->orderBy('field_order')->get();
            
        $upload = new Upload;
        $uploads = Donor::withTrashed()->find($id)->uploads()->where('profile', 1)->first();
        $profilePic = '';
        if (!empty($uploads)) {
            $profilePic = $uploads->makeAWSlink($uploads);
        }
            
        $e = new Entity;
        $name = $e->getDonorName($id);

        $details = $donor->getDonorAmountDetails($id);

        $emailset = new Emailset;
        $emailsets = $emailset->getEmailSets($hysform->id);
        $template_errors=[];
        if (!empty($emailsets['default_emailset'])) {
            $t = new Emailtemplate;
            $e_s= Emailset::where('id', $emailsets['default_emailset']['id'])->get();
            $template_errors = $t->templateErrors($e_s);
        }

        $years = $donor->getYears($donor);

        return view('admin.views.editDonor', [
            'profile' => $profile,
            'fields' => $fields,
            'donor' => $donor,
            'hysform'=> $hysform,
            'profilePic' => $profilePic,
            'name' => $name['name'],
            'details'   => $details,
            'emailsets' => $emailsets,
            'years' => $years,
            'template_errors' => $template_errors
        ]);
    }
        
    public function postEditDonor($id)
    {
        $data = Input::all();
        unset($data['_token']);
        $donor = Donor::withTrashed()->find($id);
            
        $archived = false;
        if (!empty($donor->deleted_at)) {
            $archived = true;
        }
            
        // check for change in username and email
        if (!empty($data['username']) && $donor->username == $data['username']) {
            unset($data['username']);
        }
        if (!empty($data['email']) && $donor->email == $data['email']) {
            unset($data['email']);
        }
            
        
        $rules = [
            'username' => 'unique:donors|min:5',
            'email' => 'email',
            'password' => 'min:5'
        ];
           
        $validator = Validator::make($data, $rules);

        $old_email = $donor->email;
            
        if ($validator->passes()) {
            if (!empty($data['username'])) {
                $donor->username = $data['username'];
                unset($data['username']);
            }
                
            if (!empty($data['email'])) {
                $donor->email = $data['email'];
                unset($data['email']);
            }
                
            if (!empty($data['password'])) {
                $donor->password = Hash::make($data['password']);
                unset($data['password']);
            }
                
            $fields = DonorField::whereHysformId($donor->hysform_id)->get();
            foreach ($fields as $field) {
                $field_types[$field->field_key] = $field->field_type;
            }
                
            $hash = "donor:id:$id";
            $profile = [];
            foreach ($data as $k => $v) {
                if (is_array($v)) {
                    // check if it is a checkbox
                    if (in_array('checkbox', $v)) {
                        $v = json_encode($v);
                    }

                    if (isset($field_types[$k]) && $field_types[$k] == 'hysTable') {
                        $v = json_encode($v);
                    }
                        
                    if (isset($field_types[$k]) && $field_types[$k] == 'hysLink') {
                        $link = '';
                        foreach ($v as $part) {
                            if (!empty($part)) {
                                $link .= ''.$part.'|';
                            }
                        }
                        $v = substr($link, 0, -1); // Removes the last pipe
                    }
                }
                    
                $profile[$k] = "$v";
            }

            $trashed_options = [''];
                
            $donor->json_fields = json_encode($profile);
            $donor->save();

            // in case mysql db was not updated
            $donor->touch();
            if ($archived == true) {
                $donor->delete(); // if editing archived donor make sure it stays archived.
                $trashed_options=['1'];
            }
            //Reload the Cache entry for this donor!
            $donor->reloadDonorsToCache($donor);
            if ($donor->email==$old_email) {
                $old_email= null;
            }

            Session::flash('message', 'Profile saved');
            Session::flash('alert', 'success');

            $donor->syncDonorsToMailchimp($donor, $old_email);

            return redirect('admin/edit_donor/'.$donor->id.'')
                ->withInput();
        }
        return redirect('admin/edit_donor/'.$id.'')
            ->withErrors($validator)
            ->withInput();
    }
        
        
    public function removeDonor($donor_id)
    {
        // archive any sponsorships and remove commitments

        $donor = Donor::where('client_id', Session::get('client_id'))->withTrashed()->find($donor_id);
        if (count($donor)==0) {
            return "Error: Donor Not Found.";
        }

        $donor = new Donor;
        $DonorEntity = DonorEntity::whereDonorId($donor_id)->get();
            
        if (count($DonorEntity)) {
            foreach ($DonorEntity as $de) {
                $de->delete();
                $donor->setStatus($de->entity_id);
            }
            $e = new Entity;
            $e->reloadSponsorshipsToCache($DonorEntity);
        }
            
        $commitment = Commitment::where('donor_id', $donor_id)->get();
            
        if (count($commitment)) {
            foreach ($commitment as $c) {
                $c->delete();
            }
        }

        // then remove the donor
        $donor = Donor::find($donor_id);

        $donor->delete();

        $e = new Entity;

        $e->reloadSponsorshipsToCache($DonorEntity);

        $donor->reloadDonorsToCache($donor);

        return Redirect::back();
    }

    public function removeDonors($hysform_id)
    {
        // archive any sponsorships and remove commitments
        $a_donor = new Donor;

        $donor_ids= Input::get('donor_ids');

        $DonorEntity = DonorEntity::whereIn('donor_id', $donor_ids)->get();
            
        if (count($DonorEntity)) {
            foreach ($DonorEntity as $de) {
                $de->delete();
                $a_donor->setStatus($de->entity_id);
            }
            $e = new Entity;
            $e->reloadSponsorshipsToCache($DonorEntity);
        }
            
        $commitment = Commitment::whereIn('donor_id', $donor_ids)->get();
            
        if (count($commitment)) {
            foreach ($commitment as $c) {
                $c->delete();
            }
        }

        // then remove the donor
        $donors = Donor::where('hysform_id', $hysform_id)->whereIn('id', $donor_ids)->get();
            
        foreach ($donors as $donor) {
            $donor->delete();
        }

            

        $a_donor->reloadDonorsToCache($donors);
    }

    public function deleteDonor($donor_id)
    {

        $donor = Donor::where('client_id', Session::get('client_id'))->withTrashed()->find($donor_id);
        if (count($donor)==0) {
            return "Error: Donor Not Found.";
        }

        // archive any sponsorships and remove commitments
        $donor = new Donor;
        $DonorEntity = DonorEntity::onlyTrashed()->whereDonorId($donor_id)->get();
            
        if (count($DonorEntity)) {
            foreach ($DonorEntity as $de) {
                $de->forceDelete();
                $donor->setStatus($de->entity_id);
            }
        }
            
        $commitment = Commitment::where('donor_id', $donor_id)->get();
            
        if (count($commitment)) {
            foreach ($commitment as $c) {
                $c->forceDelete();
            }
        }
            
        // then remove the donor
        $donor = Donor::onlyTrashed()->where('id', $donor_id)->get();
            
        //deletes this donor from the trashed cache table
        $donor->first()->removeDonorsFromTrashedCache($donor);
            
        $hysform_id = $donor->first()->hysform_id;

        // $donor->syncDonorsToMailchimp($donor);

        $donor->first()->forceDelete();
            
        return redirect('admin/show_all_donors/'.$hysform_id.'/1')->with('message', 'Donor Deleted Successfully')->with('alert', 'success');
    }

    public function deleteDonors($donor_id)
    {

        $donor_ids= Input::get('donor_ids');
        // archive any sponsorships and remove commitments
        $d = new Donor;
        $DonorEntity = DonorEntity::where('client_id', Session::get('client_id'))->whereIn('donor_id', $donor_ids)->get();
            
        if (count($DonorEntity)) {
            foreach ($DonorEntity as $de) {
                $de->forceDelete();
                $d->setStatus($de->entity_id);
            }
        }
            
        $commitment = Commitment::where('client_id', Session::get('client_id'))->whereIn('donor_id', $donor_ids)->get();
            
        if (count($commitment)) {
            foreach ($commitment as $c) {
                $c->forceDelete();
            }
        }
            
        // then remove the donor
        $donors = Donor::onlyTrashed()->where('client_id', Session::get('client_id'))->whereIn('id', $donor_ids)->get();
            
        //deletes this donor from the trashed cache table
        $d->removeDonorsFromTrashedCache($donors);
            
        $hysform_id = $donors->first()->hysform_id;
            
        // $d->syncDonorsToMailchimp($donors);

        foreach ($donors as $donor) {
            $donor->forceDelete();
        }
    }


    public function activateDonor($donor_id, $with_commitments = false)
    {

        $donor = Donor::where('client_id', Session::get('client_id'))->withTrashed()->find($donor_id);
        if (count($donor)==0) {
            return "Error: Donor Not Found.";
        }

        $donor = Donor::onlyTrashed()->find($donor_id);

        if (!count($donor)) {
            $donor= Donor::withTrashed()->where('id', $donor_id)->first();
            if (count($donor)) {
                return redirect('admin/show_all_donors/'.$donor->hysform_id.'/1')
                ->with('message', 'Error: Donor has already been Restored.')
                ->with('alert', 'warning');
            } else {
                return redirect('admin')
                ->with('message', 'Error: Donor could not be found.')
                ->with('alert', 'danger');
            }
        }
            
        $donor->restore();
            
        if ($with_commitments) {
            $DonorEntity = DonorEntity::onlyTrashed()->whereDonorId($donor_id)->get();
            
            if (count($DonorEntity)) {
                foreach ($DonorEntity as $de) {
                    $de->restore();
                    $donor->first()->setStatus($de->entity_id);
                }
                $e = new Entity;
                $e->reloadSponsorshipsToCache($DonorEntity);
            }
                
                
            $commitment = Commitment::onlyTrashed()->where('donor_id', $donor_id)->get();
                
            if (count($commitment)) {
                foreach ($commitment as $c) {
                    $c->restore();
                }
            }
        }

        $donor->reloadDonorsToCache($donor);



        $donor->syncDonorsToMailchimp($donor);

        return Redirect::back()
            ->with('message', 'Successfully Restored')
            ->with('alert', 'success');
    }

    public function activateDonors($hysform_id, $with_commitments = false)
    {

        $a_donor = new Donor;

        $donor_ids =Input::get('donor_ids');

        $donors = Donor::onlyTrashed()->where('client_id', Session::get('client_id'))->where('hysform_id', $hysform_id)->whereIn('id', $donor_ids)->get();
            
            
        if ($with_commitments) {
            $DonorEntity = DonorEntity::onlyTrashed()->where('client_id', Session::get('client_id'))->whereIn('donor_id', $donor_ids)->get();
            
            if (count($DonorEntity)) {
                foreach ($DonorEntity as $de) {
                    $de->restore();
                    $a_donor->first()->setStatus($de->entity_id);
                }
                $e = new Entity;
                $e->reloadSponsorshipsToCache($DonorEntity);
            }
                
            $commitment = Commitment::onlyTrashed()->where('client_id', Session::get('client_id'))->whereIn('donor_id', $donor_ids)->get();
                
            if (count($commitment)) {
                foreach ($commitment as $c) {
                    $c->restore();
                }
            }
        }

        foreach ($donors as $donor) {
            $donor->restore();
        }

        $a_donor->reloadDonorsToCache($donors);
        $a_donor->syncDonorsToMailchimp($donors);
    }
        
    // creates an array of all available for sponsorship by client
    public function listAvailableEntities()
    {

        $all = [];
        foreach (Client::with([
            'entities' => function ($query) {
                $query->whereIn('status', [0,2]);
            },
            'fields' => function ($query) {
                $query->where('is_title', 1)->orderBy('field_order', 'asc');
            },
            'programs'])->where('id', Session::get('client_id'))->get() as $client) {
            foreach ($client->entities as $entity) {
                $anames = [];
                foreach ($client->programs as $program) {
                    if ($program->id == $entity->program_id) {
                        $hysform_id = $program->hysform_id;
                    }
                }
                    
                if (isset($hysform_id)) {
                    foreach ($client->fields as $field) {
                        if ($field->hysform_id == $hysform_id) {
                            $anames[] = $field->field_key;
                        }
                    }
                    $json_fields = json_decode($entity->json_fields, true);
                        
                    $n = '';
                    if (!empty($json_fields)) {
                        foreach ($json_fields as $key => $name) {
                            if (in_array($key, $anames)) {
                                $n .= "$name ";
                            }
                        }
                    }

                    $all[] = ['id' => $entity->id, 'name' => trim($n)];
                }
            }
        }

        return $all;
    }
                
    public function sponsorships($id)
    {

        $donor = Donor::where('client_id', Session::get('client_id'))->withTrashed()->find($id);
        if (count($donor)==0) {
            return "Error: Donor Not Found.";
        }

        $entities = Cache::remember('entities_list-'.Session::get('client_id'), 10080, function () {
            return $entities = $this->listAvailableEntities();
        });

        $donor = Donor::withTrashed()->find($id);

        $sponsorships = $donor->getSponsorships($id);
            
        $archived = $donor->getSponsorships($id, $trashed = true);
        $e = new Entity;
        $name = $e->getDonorName($id);

        $hysform= Hysform::find($donor->hysform_id);

        $emailset = new Emailset;
        $emailsets = $emailset->getEmailSets($donor->hysform_id);
        $template_errors=[];
        if (!empty($emailsets['default_emailset'])) {
            $t = new Emailtemplate;
            $e_s= Emailset::where('id', $emailsets['default_emailset']['id'])->get();
            $template_errors = $t->templateErrors($e_s);
        }
            
        return view('admin.views.sponsorships', [
            'entities' => $entities,
            'donor' => $donor,
            'hysform'=>$hysform,
            'sponsorships' => $sponsorships,
            'archived' => $archived,
            'name' => $name['name'],
            'emailsets' => $emailsets,
            'years' =>  $donor->getYears($donor),
            'template_errors'=> $template_errors
        ]);
    }
        
    public function postSponsorshipsNext($id)
    {

        $donor = Donor::where('client_id', Session::get('client_id'))->withTrashed()->find($id);
        if (count($donor)==0) {
            return "Error: Donor Not Found.";
        }

        $donor = Donor::withTrashed()->find($id);
        $dntns = new Donation;
        $data = Input::all();
        $hysform= Hysform::find($donor->hysform_id);
        $programs= [];
        $pre_rules = ['entities' => 'required'];

        $pre_validator = Validator::make($data, $pre_rules);
                
        if ($pre_validator->passes()) {
            // get program type and sponsorship amount
            $name = $donor->getEntityName($data['entities']);
            $entity = Entity::find($data['entities']);
            $dname= $entity->getDonorName($id);
            $program = Program::whereId($entity->program_id)->with('setting')->first();
            if (!count($program)) {
                return redirect('admin/sponsorships/'.$id)->
                withErrors($pre_validator)->
                withInput()->
                with('message', 'Error: That Recipient has no program connected to it.')->
                with('alert', 'danger');
            }
            $program->toArray();
            $program_settings = (array) json_decode($program['setting']['program_settings']);

            // get primary program and sub_programs
            $program = Program::find($entity->program_id);
            $other_programs = Program::where('client_id', Session::get('client_id'))->where('link_id', $entity->program_id)->get();

            $programs= [$program->id => $program->name. ' (Parent Program)'];
            foreach ($other_programs as $o_p) {
                $programs[$o_p->id]=$o_p->name. ' (Sub Program)';
            }

            if ($program_settings['program_type'] == 'contribution') {
                $sp_amount = explode(',', $program_settings['sponsorship_amount']);
                $vars = ['sp_num' => $entity->sp_num, 'sp_amount' => $sp_amount, 'symbol' => $program_settings['currency_symbol']];
            }
                
            if ($program_settings['program_type'] == 'number') {
                $vars = ['sp_num' => $entity->sp_num, 'sp_amount' => $entity->sp_amount, 'symbol' => $program_settings['currency_symbol']];
            }

            if ($program_settings['program_type'] == 'funding') {
                $sp_amount='';
                if (empty($entity->sp_amount)&&!empty($program_settings['sponsorship_amount'])) {
                    $sp_amount = explode(',', $program_settings['sponsorship_amount']);
                } else {
                    $sp_amount=$entity->sp_amount;
                }

                $vars = ['sp_num' => $entity->sp_num, 'sp_amount' => $sp_amount, 'symbol' => $program_settings['currency_symbol']];
            }

                
            $vars['program_type'] = $program_settings['program_type'];
                
            // determine if continuous or times sponsorship
            if (!empty($program_settings['duration'])) {
                // determine if date or number of days
                $rules = ['duration' => 'date'];
                $validator = Validator::make($program_settings, $rules);
                    
                if ($validator->passes()) { // handle as a date
                    $vars['end_date'] = $program_settings['duration'];
                } else { // handle as a number of days
                    $dt = Carbon::now();
                    $date = $dt->addDays((int) $program_settings['duration']);
                    $vars['end_date'] = $date;
                }
            }
            $email_template=Emailtemplate::where('emailset_id', $program->emailset_id)->where('trigger', 'new_donor')->get()->first();

            return view('admin.views.sponsorshipsNext', [
                'name' => $name,
                'dname' =>$dname['name'],
                'donor'=> $donor,
                'vars' => $vars,
                'id' => $id,
                'dntns' => $dntns,
                'programs' => $programs,
                'email_template' => $email_template,
                'program'=> $program,
                'hysform'=>$hysform]);
        } else {
            return redirect('admin/sponsorships/'.$id)->
                withErrors($pre_validator)->
                withInput()->
                with('message', 'Error: You must select a valid sponsorship Recipient.')->
                with('alert', 'danger');
        }
    }
                
    public function postAddSponsorships($id)
    {
        $donor = new Donor;
        $data = Input::all();
            
        $donor = Donor::where('client_id', Session::get('client_id'))->withTrashed()->find($id);
        if (count($donor)==0) {
            return "Error: Donor Not Found.";
        }

        $rules = [
            'sp_amount'     =>  'numeric|min:5|required'
        ];
           
        $validator = Validator::make($data, $rules);

            
        if ($validator->passes()) {
            $p= new Program;

            $program_type= $p->getProgramTypeFromEntity($data['entity_id']);

            $DonorEntity = new DonorEntity;
            $DonorEntity->donor_id = $id;
            $DonorEntity->entity_id = $data['entity_id'];
            $DonorEntity->client_id = Session::get('client_id');

            $entity=Entity::find($data['entity_id']);


            //Set the program id if the admin chooses (for sub-programs)
            $DonorEntity->program_id=$entity->program_id;
            if (isset($data['program_id'])) {
                $temp_program=Program::find($data['program_id']);
                if ($temp_program!=null) {
                    if ($temp_program->client_id==Session::get('client_id')) {
                        $DonorEntity->program_id=$data['program_id'];
                    }
                }
            }

            $DonorEntity->save();

            $e = new Entity;

                
                
            $commitment = new Commitment;
            $commitment->client_id = Session::get('client_id');
            $commitment->donor_id = $id;
            $commitment->donor_entity_id = $DonorEntity->id;
            $commitment->type = 1;
            $commitment->frequency = 1;
            if (isset($data['frequency'])) {
                $commitment->frequency = $data['frequency'];
            }
            if (isset($data['arb_subscription_id'])) {
                $commitment->arb_subscription_id = $data['arb_subscription_id'];
            }
                        
            if (isset($data['until'])) {
                $commitment->until = $data['until'];
            }

            if ($program_type=='funding') {
                $commitment->funding=1;
            }

            if (isset($data['next'])) {
                $donor= new Donor;
                $last= $donor->getLastFromNext($commitment, $data['next']);
                if ($last) {
                    $commitment->last=$last;
                }
            }

            $commitment->amount = $data['sp_amount'];
            $commitment->designation = $data['entity_id'];
            $commitment->method = $data['method'];
            $commitment->save();


            // //Reload the Cache entry for this donor!
            // $donor->reloadDonorsToCache($donor);
                
            $e->reloadEntitiesToCache($entity);

                
            if (isset($data['send_email'])) {
                // send email
                $emailtemplate = new Emailtemplate;
                $donor= Donor::find($id);
                $program_id = DB::table('entities')->where('id', $DonorEntity->entity_id)->pluck('program_id');
                $program = Program::find($program_id);
                $details['entity'] = $emailtemplate->getEntity($DonorEntity->entity_id);
                $details['donor'] = $emailtemplate->getDonor($DonorEntity->donor_id);
                $details['other'] = ['date'=>Carbon::now()->toFormattedDateString(),'username'=>$donor->username];
                $entity = new Entity;
                $d = $entity->getDonorName($DonorEntity->donor_id);
                $to = ['type' => 'donor', 'name' => $d['name'], 'email' => $d['email'], 'id' => $DonorEntity->donor_id];
                $email = $emailtemplate->sendEmail($program->emailset_id, $details, 'new_donor', $to);
            }
                
            $status = $donor->setStatus($DonorEntity->entity_id);

                
            return redirect('admin/sponsorships/'.$id.'')
                ->with('message', 'Sponsorship relationship successfully created.')
                ->with('alert', 'success');
        }
        return redirect('admin/sponsorships/'.$id)
        ->with('message', 'Error: You must input a valid sponsorship amount.')
        ->with('alert', 'danger');
    }


    public function sendSignupEmail($commitment_id)
    {
        $commitment= Commitment::find($commitment_id);
        if (!count($commitment)) {
            return Redirect::back()
                ->with('message', 'Error: Commitment not found.')
                ->with('alert', 'danger');
        }

        $donor = Donor::find($commitment->donor_id);
        $DonorEntity = DonorEntity::find($commitment->donor_entity_id);


        if (!count($DonorEntity)) {
            return Redirect::back()
                ->with('message', 'Error: Commitment broken, please fix.')
                ->with('alert', 'danger');
        }

        if (!count($donor)) {
            return Redirect::back()
                ->with('message', 'Error: Donor not found.')
                ->with('alert', 'danger');
        }

        $emailtemplate = new Emailtemplate;
        $program_id = DB::table('entities')->where('id', $DonorEntity->entity_id)->pluck('program_id');
        if (empty($program_id)) {
            return Redirect::back()
                ->with('message', 'Error: Program not found.')
                ->with('alert', 'danger');
        }

        $program = Program::find($program_id);

        if (!count($program)) {
            return Redirect::back()
                ->with('message', 'Error: Program not found.')
                ->with('alert', 'danger');
        }

        $details['entity'] = $emailtemplate->getEntity($DonorEntity->entity_id);
        $details['donor'] = $emailtemplate->getDonor($DonorEntity->donor_id);
        $details['other'] = ['date'=>Carbon::now()->toFormattedDateString()];
        $entity = new Entity;
        $d = $entity->getDonorName($DonorEntity->donor_id);
        $to = ['type' => 'donor', 'name' => $d['name'], 'email' => $d['email'], 'id' => $DonorEntity->donor_id];
        $email = $emailtemplate->sendEmail($program->emailset_id, $details, 'new_donor', $to);

        if ($email) {
            return Redirect::back()
                ->with('message', 'Donor Signup email successfully sent to '.$d['name']. ' using the email address: '.$d['email'])
                ->with('alert', 'success');
        } else {
            return Redirect::back()
                ->with('message', 'Error: Email could not send.')
                ->with('alert', 'danger');
        }
    }
        
    public function removeSponsorship($donor_entity_id)
    {
        $donor = new Donor;
        $DonorEntity = DonorEntity::find($donor_entity_id);
            
        if (!count($DonorEntity)) {
            return Redirect::back()
                ->with('message', 'Sponsorship has already been removed.')
                ->with('alert', 'danger');
        }

        $DonorEntity->delete();
            
        $commitment = Commitment::where('donor_entity_id', $donor_entity_id)->get()->first();
            
        if (count($commitment)) {
            $commitment->delete();
        }
            
        $donor = Donor::withTrashed()->find($DonorEntity->donor_id);

        Session::flash('message', 'Sponsorship Successfully Removed.');
        Session::flash('alert', 'success');
            
        if (count($donor)) {
            $status = $donor->setStatus($DonorEntity->entity_id);

            //Reload the Cache entry for this donor!
            $donor->reloadDonorsToCache($donor);
                
            $e = new Entity;
            $e->reloadSponsorshipsToCache($DonorEntity);

            return Redirect::back();
        }

        return Redirect::back()
            ->with('message', 'Donor has already been removed.')
            ->with('alert', 'danger');
    }
        
    public function restoreSponsorship($donor_entity_id)
    {
        $donor = new Donor;
        DonorEntity::withTrashed()->where('id', $donor_entity_id)->restore();
        Commitment::withTrashed()->where('donor_entity_id', $donor_entity_id)->restore();


        $DonorEntity = DonorEntity::find($donor_entity_id);

        $e = new Entity;

        $e->reloadSponsorshipsToCache($DonorEntity);

        if (count($DonorEntity)) {
            Session::flash('message', 'Sponsorship Successfully Restored.');
            Session::flash('alert', 'success');
            $status = $donor->setStatus($DonorEntity->entity_id);

            $donor= Donor::find($DonorEntity->donor_id);
            //Reload the Cache entry for this donor!
            $donor->reloadDonorsToCache($donor);
                
            return redirect('admin/sponsorships/'.$DonorEntity->donor_id.'');
        } else {
            return Redirect::back()
                ->with('message', 'Error: Sponsorship could not be restored.')
                ->with('alert', 'danger');
        };
    }

    public function getFieldOptions($hysform_id)
    {
        $donorFields = Donorfield::whereHysformId($hysform_id)->orderBy('field_order')->get();
        $dfs = [];
            
        foreach ($donorFields as $df) {
            if ($df->field_type != 'hysTable' or $df->field_type != 'hysCheckbox') {
                $dfs[] = ['field_key' => $df->field_key, 'field_label' => $df->field_label];
            }
        }
        // place fields in array with key matching permissions for easy exclusion in template
        $fields['donor-'.$hysform_id.''] = $dfs;
            
        return $fields;
    }

    public function fieldOptions($hysform_id, $type)
    {


        $vars = Cache::remember('donorfieldoptions-'.$hysform_id, 1440, function () use ($hysform_id, $type) {

            $data = Input::all();
            $fieldOptions = $this->getFieldOptions($hysform_id);
                
            $fields = DonorField::where('hysform_id', $hysform_id)->orderBy('field_order')->get();
            $reports = Report::whereHysformId($hysform_id)->get();

            $d = new Donor;

            $details=$d->getDonorAmountDetailsForWholeTable();

            return $vars = [
                'fieldOptions' => $fieldOptions,
                'fields' => $fields,
                'type' => $type,
                'reports' => $reports,
                'hysform_id' => $hysform_id,
                'details'=>$details];
        });

        return view('admin.views.donorFieldOptions', $vars);
    }
        
    public function postFieldOptions($hysform_id)
    {
        $data = Input::all();
        unset($data['_token']);
            
        // save to admin preferences in redis
        $redis = RedisL4::connection();
        $user = Sentry::getUser();
        $hash = "admin:{$user->id}:donor-$hysform_id";

        $d = new Donor;

        $details=$d->getDonorAmountDetailsForWholeTable();
            
        $dfields = [];
        if (!empty($data['donor'])) {
            $redis->hdel($hash, 'donor');
            $donorFields = Donorfield::whereHysformId($hysform_id)->orderBy('field_order')->get();
                
            foreach ($data['donor'] as $field) {
                foreach ($donorFields as $df) {
                    if ($field == $df->field_key) {
                        $dfields[] = ['field_key' => $field, 'field_label' => $df->field_label, 'field_type' => $df->field_type, 'field_data' => $df->field_data];
                    }
                }
                if ($field == 'thumb') {
                    $dfields['thumb'] = true;
                }

                if ($field == 'created_at') {
                    $dfields['created_at'] = true;
                }

                if ($field == 'updated_at') {
                    $dfields['updated_at'] = true;
                }

                if ($field== 'email') {
                    $dfields['email'] = true;
                }

                if ($field== 'username') {
                    $dfields['username'] = true;
                }

                    
                if ($field == 'manage') {
                    $dfields['manage'] = true;
                }

                foreach ($details as $name => $detail) {
                    //make names the same as field inputs
                    $n=strtolower(str_replace(' ', '_', $name));
                    //If any of these names already exist, put them in the efields!
                    if ($field==$n) {
                        $dfields[$n]=true;
                    }
                }
            }
            $fields['donor'] = json_encode($dfields);
        }
        
            

        $redis->hmset($hash, $fields);
            
        // save report
        if (!empty($data['report_name'])) {
            $report = new Report;
            $report->client_id = Session::get('client_id');
            $report->hysform_id = $hysform_id;
            $report->name = $data['report_name'];
            $report->fields = json_encode($fields);
            $report->save();

            //Clear cache so donor report will show in dropdown menu
            $d = new Donor;
            $d->clearDonorCache($hysform_id, $reload = ['donors'], $trashed_options = null);
            Cache::forget('donorfieldoptions-'.$hysform_id);
        }
            
        return 'Preferences Saved';
    }
        
    public function selectDonorSavedReport($report_id, $hysform_id, $trashed = null)
    {
        $report = Report::find($report_id);
        $fields = json_decode($report->fields, true);
            
        // save to admin preferences in redis
        $redis = RedisL4::connection();
        $user = Sentry::getUser();
        $hash = "admin:{$user->id}:donor-$hysform_id";
        $redis->hdel($hash, 'donor');
        $redis->hmset($hash, $fields);

        // //Clear cache for donors counts
        // $d = new Donor;
        // $d->clearDonorCache($hysform_id,array('donors'),null);

        if ($trashed==null) {
            return redirect('admin/show_all_donors/'.$hysform_id);
        } else {
            return redirect('admin/show_all_donors/'.$hysform_id.'/'.$trashed);
        }
    }
        
    public function removeDonorSavedReport($report_id, $hysform_id)
    {
        $report = Report::find($report_id);
        $report->delete();

        //Clear cache for donors counts
        $d = new Donor;
        $d->clearDonorCache($hysform_id, ['donors'], null);
        Cache::forget('donorfieldoptions-'.$hysform_id);
            
        return redirect('admin/show_all_donors/'.$hysform_id);
    }
        
    public function moveDonorsToSQL()
    {
                
        $donors = Donor::onlyTrashed()->get();
            
        //$donors = Donor::all();
            
        //This gets all the entities at once with the redis pipeline
        $pipeline = RedisL4::pipeline(function ($pipe) use ($donors) {
            foreach ($donors as $donor) {
                $pipe->hgetall("donor:id:{$donor->id}");
            }
        });

        $i=0;
        $changed=0;
        foreach ($donors as $donor) {
            $hysforms[$donor->hysform_id]='1';
            if (isset($pipeline[$i])) {
                $donor->json_fields=json_encode($pipeline[$i]);
                $donor->save();
                $changed++;
            }
            $i++;
        }
        $d = new Donor;
        foreach ($hysforms as $k => $v) {
            $d->clearDonorCache($k, null, null);
        }

        return 'total Donors Copied to SQL = '. $changed;
    }

    public function sendNotifyDonors($hysform_id, $emailset_id)
    {

        $hysform = Hysform::where('client_id', Session::get('client_id'))->where('type', 'donor')->find($hysform_id);

        if (count($hysform)==0) {
            return "Error: Donor Form Not Found.";
        }

        $donor_ids = Input::get('donor_ids');

        if (empty($donor_ids)) {
            $donors= Donor::where('hysform_id', $hysform_id)->get();
        } else {
            $donors = Donor::whereIn('id', $donor_ids)->get();
        }

        $success_message='';
        $error_message='';
        $success=[];
        $failed= [];

        $emailtemplate= Emailtemplate::find($emailset_id);

        foreach ($donors as $donor) {
            $result = $donor->sendNotifyDonor($donor, $emailset_id);
            if ($result) {
                $success[]=$donor;
            } else {
                    $failed[]=$donor ;
            }
        }
            

        if (count($success)) {
            if (count($success)==1) {
                $success_message = '<span class="glyphicon glyphicon-ok"></span> Account Notification Email successfully sent to '.$success[0]->email.'.';
            } else {
                $success_message = '<span class="glyphicon glyphicon-ok"></span> Account Notification Emails successfully sent to '.count($success).' Donors. <br> <a href="'.URL::to('admin/info').'" target="_blank">View Report</a> ';

                $success_list = '<span class="glyphicon glyphicon-ok"></span> Success:  Account Notification Emails successfuly sent to '.count($success).' Donors. <br> Successfuly Sent Emails Table: <br><table><thead><tr><th>Email</th>';
                foreach ($success as $donor) {
                    $success_list.="<tr>";
                    $success_list.="<td><a href=\"".URL::to('admin/edit_donor', [$donor->id])."\">".$donor->email."</a></td>";
                    $success_list.="</tr>";
                }
                Session::set('info', $success_list);
                Session::set('info-alert', 'success');
            }
        }

        if (count($failed)) {
            $s = 's';
            if (count($failed)==1) {
                $s='';
            }

            $error_message = '<span class="glyphicon glyphicon-warning-sign"></span> Error: '.count($failed). ' Account Notification Email'.$s.' failed to send.';
        }

        return ['success_message'=>$success_message,'error_message'=>$error_message];
    }

    public function sendYearEndDonors($hysform_id, $emailset_id, $year)
    {

        $hysform = Hysform::where('client_id', Session::get('client_id'))->where('type', 'donor')->find($hysform_id);

        if (count($hysform)==0) {
            return "Error: Donor Form Not Found.";
        }

        $donor_ids = Input::get('donor_ids');

        if (empty($donor_ids)) {
            $donors= Donor::where('hysform_id', $hysform_id)->get();
        } else {
            $donors = Donor::whereIn('id', $donor_ids)->get();
        }

        $success_message='';
        $error_message='';
        $success=[];
        $failed= [];

        foreach ($donors as $donor) {
            $result = $donor->first()->queueSendYearEndDonor($donor->id, $emailset_id, $year);
            if ($result) {
                $success[]=$donor;
            } else {
                $failed[]=$donor ;
            }
        }
            

        if (count($success)) {
            if (count($success)==1) {
                $success_message = '<span class="glyphicon glyphicon-ok"></span> '.$year.' Year End Statement successfully sent to '.$success[0]->email.'.';
            } else {
                $success_message = '<span class="glyphicon glyphicon-ok"></span> '.$year.' Year End Statements successfully sent to '.count($success).' Donors. <br> <a href="'.URL::to('admin/info').'" target="_blank">View Report</a> ';
                    
                $success_list = '<span class="glyphicon glyphicon-ok"></span> Success:  '.$year. ' Year End Statements successfuly sent to '.count($success).' Donors. <br> Successfuly Sent Emails Table: <br><table><thead><tr><th>Email</th>';
                foreach ($success as $donor) {
                    $success_list.="<tr>";
                    $success_list.="<td><a href=\"".URL::to('admin/edit_donor', [$donor->id])."\">".$donor->email."</a></td>";
                    $success_list.="</tr>";
                }
                Session::set('info', $success_list);
                Session::set('info-alert', 'success');
            }
        }

        if (count($failed)) {
            $s = 's';
            if (count($failed)==1) {
                $s = '';
                $error_message = '<span class="glyphicon glyphicon-warning-sign"></span> Error: '.$year.' Year End Statement failed to send to '.$failed[0]->email.'<br> <a href="'.URL::to('admin/error').'" target="_blank">View Report</a> ';
            } else {
                $error_message = '<span class="glyphicon glyphicon-warning-sign"></span> Error: '.$year.' Year End Statements failed to send to '.count($failed). ' Donors<br> <a href="'.URL::to('admin/error').'" target="_blank">View Report</a> ';
            }

            $error_list = '<span class="glyphicon glyphicon-warning-sign"></span> Error: '.$year.' Year End Statement'.$s.' failed to send to '.count($failed). ' Donor'.$s.'.<br> Failed Emails Table: <br><table><thead><tr><th>Email</th><th>Reason for Failure</th>';
            foreach ($failed as $donor) {
                $error_list.="<tr>";
                if (empty($donor->email)) {
                    $error_list.="<td><a href=\"".URL::to('admin/edit_donor', [$donor->id])."\">".$donor->username."</a></td><td> has no email address</td>";
                } else {
                    $error_list.="<td><a href=\"".URL::to('admin/donations_by_donor', [$donor->id])."\">".$donor->email."</a></td><td>  has no existing donations</td>";
                }
                $error_list.="</tr>";
            }
            Session::set('error', $error_list);
            Session::set('error-alert', 'danger');
        }

        return ['success_message'=>$success_message,'error_message'=>$error_message];
    }
}
