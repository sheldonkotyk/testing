<?php  namespace App\Controllers\Donor;
 
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
use Emailtemplate;
use Image;
use AWS;
use File;
use Donation;
use Setting;
use Designation;
use Commitment;
use Donoremail;
use URL;
use Gateway;
    
use App\Http\Controllers\Controller;

class DonorController extends Controller
{
        

    // path to temporary folder used for resizing/rotating files.
    private function tempFileFolder()
    {
            $public = public_path();
            return $path = "$public/temp/";
    }


    public function donorUploadFile($client_id, $program_id, $entity_id, $session_id)
    {
            
            $redis= RedisL4::connection();

        if ($redis->exists($session_id)!=1) {
            return redirect('/frontend/login/'.$client_id.'/'.$program_id);
        }

            $donor_id= $redis->hget($session_id, 'donor_id');

            $uploads = Donor::find($donor_id)->uploads;
            $upload = new Upload;

        if (!empty($client)) {
            $box_access_token=$client->box_access_token;
        } else {
            $box_access_token= '';
        }

        if ($upload->useBox($client_id)) {
            if ($upload->isBoxLoggedIn($client_id)==false) {
                return $upload->logBoxIn(null, URL::to('frontend/donor_upload', [$client_id,$program_id,$entity_id,$session_id]));
            }
            $client= Client::find($client_id);
            $box_access_token=$client->box_access_token;
        }

            $number_of_files = count($uploads);

            //This will limit the Donor uploads to a max of 5 files
        if ($number_of_files>=5) {
            $files_full=true;
        } else {
            $files_full=false;
        }

            $number_of_files_allowed= 5 - $number_of_files;

        if ($number_of_files_allowed < 0) {
            $number_of_files_allowed=0;
        }

            $entity = Entity::find($entity_id);

            $program= Program::find($entity->program_id);

            $program_settings = (array) json_decode(Setting::find($program->setting_id)->program_settings);

            $disable_program_link='';
        if (isset($program_settings['disable_program_link'])) {
            $disable_program_link=$program_settings['disable_program_link'];
        }
            
            $box_folder_id = '0';
            $the_donor = Donor::find($donor_id);

        if ($upload->useBox($client_id)) {
            $box_folder_id=$upload->getBoxDonorFolderId($the_donor->hysform_id, $client_id);
            if ($box_folder_id == false) {
                $box_folder_id = '0';
            }
        }

            $program = Hysform::find($the_donor->hysform_id);
            
            
            return view('donor.views.donorUploadFile', [
                'session_id'    => $session_id,
                'client_id'     => $client_id,
                'program_id'    => $program_id,
                'id'            => $donor_id,
                'type'          =>'donor',
                'upload'        => $upload,
                'slug'  => sha1(time()*time()),
                'box_folder_id'=>$box_folder_id,
                'box_access_token'=>$box_access_token,
                'program'       =>$program,
                'files_full'    => $files_full,
                'number_of_files_allowed' => $number_of_files_allowed,
                'entity_id'     => $entity_id,
                'disable_program_link' => $disable_program_link

            ]);
    }



    public function postRecordUpload($client_id, $session_id)
    {
            
        $redis= RedisL4::connection();

        if ($redis->exists($session_id)!=1) {
            return 'Can\'t upload, not logged in.';
        }

        $data= Input::all();

        if ($data['status']=='box_duplicate') {
             // if this is a duplicate on Box.com, delete the upload from S3 and our Records.
            $s3 = AWS::get('s3');
             // delete the current file at AWS
            $result = $s3->deleteObjects([
                'Bucket' => 'hys',
                'Objects' => [
                    [
                        'Key' => $data['hys_slug'].'.'.$data['hys_ext'],
                    ],
                ],
            ]);

            $up= Upload::where('box_name', '=', '--file_id--'.$data['file_id'])->where('name', '=', $data['hys_slug'].'.'.$data['hys_ext'])->first();
            if ($up->count()) {
                $up->delete();
            }

            return 'duplicate';
        }

        if ($data['status']=='5') {
            //Look for pre-existing record if this is the S3 upload being posted
            if (empty($data['box_url'])) {
                $up= Upload::where('box_name', '!=', '')->where('name', '=', $data['hys_slug'].'.'.$data['hys_ext'])->get()->first();
            } //Look for pre-existing record if this is the BOX upload being posted
            else {
                $up= Upload::where('box_name', '=', '--file_id--'.$data['file_id'])->where('name', '=', $data['hys_slug'].'.'.$data['hys_ext'])->get()->first();
            }
                
            //If no pre-existing, make a new record!
            if (empty($up)) {
                $up= new Upload;
            }
                

            $up->client_id=$client_id;
            $up->file_name=$data['name'];
                
            if ($data['box_url']=='') { //Only post S3 filename to db if S3 js section is posting.
                $up->name=$data['hys_slug'].'.'.$data['hys_ext'];
            }
                
            $otherFiles=1;
            if ($data['hys_type']=='donor') {
                $up->donor_id= $data['hys_id'];
                $entity_id = $data['hys_entity_id'];
                //count uploads that are images for this donor
                $otherFiles=Upload::where('donor_id', $data['hys_id'])->where('type', 'image')->get()->count();
            }
            if ($data['hys_type']=='entity') {
                $up->entity_id= $data['hys_id'];
                //count uploads that are images for this entity
                $otherFiles=Upload::where('entity_id', $data['hys_id'])->where('type', 'image')->get()->count();
            }

                
            if ($data['type']=='image/jpeg'||$data['type']=='image/gif'||$data['type']=='image/png') {
                $up->type='image';
            } else {
                $up->type='doc';
            }
            //Always make these files public
            $up->permissions='public';

            if ($up->useBox($client_id)) {
                if (empty($data['box_url'])) {
                    //Temporarily store the plupload file_id for validation upon box file being posted later.
                    $up->box_name= '--file_id--' . $data['file_id'];
                } else { //Set the Box Url
                    $up->box_name= $data['box_url'];
                }
            }

            $up->save();
                
            //If it's an image file make a thumbnail
            if ($up->type=='image'&&!empty($up->name)&&empty($data['box_url'])) {
                $up->makeThumbnail($up->name, $up);
            }

            //If this is the first upload and it's an image, make it the profile pic
            if ($up->type=='image'&&$otherFiles==0) {
                $up->makeProfile($up->id);
            }


            if ($data['email']=='true') {
                // MAke sure it only does this once!
                //Send Email to admin!
                $file_link= $up->makeAWSlink($up);
                $file_thumb= $up->makeAWSlinkThumb($up);

                $filetype = '';
                switch ($up->type) {
                    case "doc":
                        $filetype = "Document";
                        break;
                    case "image":
                        $filetype = "Image";
                        break;
                }

                $email = new Donoremail;

                $subject= "New file uploaded";
                $message="A new file has been uploaded<br/>";
                $message.= "File name: ". $up->file_name;
                if ($filetype=='image') {
                    $message.='<br/>Click on the Thumbnail to view the file<br/><a href="'.$file_link.'"><img src="'.$file_thumb.'" width="100"></a>';
                } else {
                    $message.= '<br>File Link: <a href="'.$file_link.'">'.$up->file_name.'</a>';
                }
                $email->subject=$subject;
                $email->message=$message;
                $email->client_id=$client_id;
                $email->entity_id=$entity_id;

                $email->donor_id=$up->donor_id;
                    
                $email->status=1; //1 = new email

                $email->save();

                $donor = new Donor;
                    
                $admin_email= new Emailtemplate;

                $details['entity'] = $admin_email->getEntity($entity_id);
                $details['donor'] = $admin_email->getDonor($up->donor_id);
                $details['message']= ['body' => $message, 'subject' => $subject];

                $d = $donor->getDesignation(1, $entity_id);

                $to= ['type' => 'admin','email' => '' ,'name' => 'admin'];

                $emailSent = $admin_email->sendEmail($d['emailset_id'], $details, 'sp_email', $to);
            }
        }

        return 'successsfully posted to box';
    }

    public function filesTable($client_id, $program_id, $id, $entity_id, $session_id)
    {
            
        $uploads = Donor::find($id)->uploads;
            
        $upload = new Upload;
        $files = [];
        $links = [];
        $box_exists=false;
        foreach ($uploads as $file) {
            switch ($file->permissions) {
                case "public":
                    $permissions = "Everyone";
                    break;
                case "donor":
                    $permissions = "Donor and Admins";
                    break;
                case "admin":
                    $permissions = "Admins Only";
                    break;
            }
                
            switch ($file->type) {
                case "doc":
                    $filetype = "Document";
                    break;
                case "image":
                    $filetype = "Image";
                    break;
            }
            if (!empty($file->box_name)) {
                $box_exists=true;
                if (strpos($file->box_name, '--file_id--') !== false) {
                    $file->box_name='';
                } else {
                }
            }
                
            $files[] = [
                'id'            => $file->id,
                'file_name'     => $file->file_name,
                'type'          => $filetype,
                'permissions'   => $permissions,
                'box_name'      => $file->box_name,
                'profile'       => $file->profile,
                'created_at'    => $file->created_at,
                'updated_at'    => $file->updated_at,
                'link'          => $upload->makeAWSlink($file)
                    
            ];
        }
            
        return view('donor.views.filesTable', [
            'files'         => $files,
            'session_id'    => $session_id,
            'client_id'     => $client_id,
            'program_id'    => $program_id,
            'entity_id'     => $entity_id,
            'box_exists'    => $box_exists
            ]);
    }

    // need to make this into a function so we can upload files from anywhere in the app
    public function postUploadFile($client_id, $program_id, $entity_id, $session_id)
    {
        $type='donor';
        //ini_set('memory_limit', '3G');
        $input = Input::all();
            
        $rules = [
            'file' => 'mimes:pdf,doc,docx,xls,txt,zip,csv,jpg,jpeg,gif,png|max:3000',
        ];
         
        $validation = Validator::make($input, $rules);
         
        if ($validation->fails()) {
            return response($validation->errors->first(), 400);
        }

        $redis= RedisL4::connection();
        $donor_id= $redis->hget($session_id, 'donor_id');

        $program= new Program;
        $program_ids=$program->getPrograms($client_id, $program_id);

         
        $file = Input::file('file');
        $extension = $file->getClientOriginalExtension();
        $name = $file->getClientOriginalName();
        $thesha = sha1(time().time());
        $thumb_filename = $thesha.'_t_.'.$extension;
        $filename = $thesha.'.'.$extension;

        $sourceFile = Input::file('file')->getRealPath();
            
        $ext = strtolower($extension);
        if ($ext == 'png' or $ext == 'gif' or $ext == 'jpg' or $ext == 'jpeg') {
            $filetype = 'image';
                
            $img = Image::make($sourceFile)->resize(500, null, function ($constraint) {
                $constraint->aspectRatio();
            })->save($this->tempFileFolder().$filename, 70);
            $img = Image::make($sourceFile)->fit(300, 300, null, 'top')->save($this->tempFileFolder().$thumb_filename, 70);

            $sourceFile = $this->tempFileFolder().$filename;
            $sourceFileThumb = $this->tempFileFolder().$thumb_filename;
        } else {
            $filetype = 'doc';
        }
            
        try {
            $s3 = AWS::get('s3');
            $s3->putObject([
                'Bucket'     => 'hys',
                'Key'        => $filename,
                'SourceFile' => $sourceFile,
                'ACL'    => 'public-read',
            ]);

            if ($filetype != 'doc') {
                $s3->putObject([
                    'Bucket'     => 'hys',
                    'Key'        => $thumb_filename,
                    'SourceFile' => $sourceFileThumb,
                    'ACL'    => 'public-read',
                ]);
            }
        } catch (S3Exception $e) {
            echo "There was an error uploading the file.\n";
        }
            
        if ($filetype == 'image') {
            unlink($this->tempFileFolder().$filename);
            unlink($this->tempFileFolder().$thumb_filename);
        }
            
        if (!isset($e)) {
            $upload = new Upload;
                
            if ($type == 'entity') {
                $upload->entity_id = $entity_id;
            } elseif ($type == 'donor') {
                $upload->donor_id = $donor_id;
            }

            $upload->name = $filename;
            $upload->file_name = $name;
            $upload->type = $filetype;
            $upload->permissions = 'public';

            if ($filetype=='image') {
                $upload->thumbnail_exists='1';
            }

            $upload->save();

            if ($type=='donor') {
                //Send Email to admin!
                $file_link= $upload->makeAWSlink($upload);
                $file_thumb= $upload->makeAWSlinkThumb($upload);


                $email = new Donoremail;

                $subject= "New file uploaded";
                $message="A new file has been uploaded<br/>";
                $message.= "File name: ". $upload->file_name;
                if ($filetype=='image') {
                    $message.='<br/>Click on the Thumbnail to view the file<br/><a href="'.$file_link.'"><img src="'.$file_thumb.'" width="100"></a>';
                } else {
                    $message.= '<br>File Link: <a href="'.$file_link.'">'.$upload->file_name.'</a>';
                }
                $email->subject=$subject;
                $email->message=$message;
                $email->client_id=$client_id;
                $email->entity_id=$entity_id;
                $email->donor_id=$donor_id;
                    
                $email->status=1; //1 = new email

                $email->save();

                $donor = new Donor;
                    
                $admin_email= new Emailtemplate;

                $details['entity'] = $admin_email->getEntity($entity_id);
                $details['donor'] = $admin_email->getDonor($donor_id);
                $details['message']= ['body' => $message, 'subject' => $subject];

                $d = $donor->getDesignation(1, $entity_id);

                $to= ['type' => 'admin','email' => '' ,'name' => 'admin'];

                $emailSent = $admin_email->sendEmail($d['emailset_id'], $details, 'sp_email', $to);
            }
        }
    }

    public function deleteFile($client_id, $program_id, $id, $entity_id, $session_id)
    {

        $redis= RedisL4::connection();

        if ($redis->exists($session_id)!=1) {
            return redirect('/frontend/login/'.$client_id.'/'.$program_id);
        }

        $donor_id= $redis->hget($session_id, 'donor_id');

        $file = Upload::find($id);
        if (!empty($file->entity_id)) {
            $type = 'entity';
            $entity_id = $file->entity_id;
        } else {
            $type = 'donor';
            //$entity_id = $file->donor_id;
        }
        $file = Upload::where('id', $id)->delete();
            
        return redirect('frontend/donor_upload/'.$client_id.'/'.$program_id.'/'.$entity_id.'/'.$session_id);
    }
        

    public function updateInfo($client_id, $program_id, $session_id = null)
    {
        if ($session_id==null) {
            return redirect('/frontend/login/'.$client_id.'/'.$program_id);
        }

        $redis = RedisL4::connection();

        //get individual donor with $donor_id
        if (($redis->hget($session_id, 'logged_in'))=='true') {
            $donor_id= $redis->hget($session_id, 'donor_id');
        } else {
            return redirect('/frontend/login/'.$client_id.'/'.$program_id);
        }

        $donor = Donor::find($donor_id);

        $hysform_id = $donor->hysform_id;

        $donor_fields = Donorfield::where('hysform_id', $hysform_id)
                ->where(function ($query) {
                    $query->where('permissions', '=', 'public')
                          ->orWhere('permissions', '=', 'donor');
                })->orderBy('field_order')->get();


        $donor_profile = json_decode($donor->json_fields, true);

        
        if ($program_id=='none') {
            $disable_program_link='';
        } else {
            $program= new Program;
            $program_ids=$program->getPrograms($client_id, $program_id);

            $program = Program::find($program_ids[0]);
            $program_settings = (array) json_decode(Setting::find($program->setting_id)->program_settings);

            $disable_program_link='';
            if (isset($program_settings['disable_program_link'])) {
                $disable_program_link=$program_settings['disable_program_link'];
            }
        }
        $email= $donor->email;
        $username= $donor->username;

        $do_not_email= $donor->do_not_email;

        return view('donor.views.updateInfo', [
            'client_id'     => $client_id,
            'program_id'    => $program_id,
            'session_id'    => $session_id,
            'donor_fields'  => $donor_fields,
            'email'         => $email,
            'username'      => $username,
            'donor_profile' => $donor_profile,
            'do_not_email'  => $do_not_email,
            'disable_program_link' => $disable_program_link
            ]);
    }

    public function postUpdateInfo($client_id, $program_id, $session_id)
    {

        $redis= RedisL4::connection();

        //get individual donor with $donor_id
        if (($redis->hget($session_id, 'logged_in'))=='true') {
            $donor_id= $redis->hget($session_id, 'donor_id');
        }

        //Let session live for one hour from now
            $redis->expire($session_id, 3600);
        if (isset($donor_id)) {
            $data= Input::all();

            $donor = Donor::find($donor_id);

            $json_fields = json_decode($donor->json_fields, true);

            $hysform_id = $donor->hysform_id;

            $donor_fields = Donorfield::where('hysform_id', $hysform_id)
                    ->where(function ($query) {
                        $query->where('permissions', '=', 'public')
                              ->orWhere('permissions', '=', 'donor');
                    })->orderBy('field_order')->get();

            $rules= [
                        'username' => 'required',
                        'email' => 'required'
                        
                        ];

            foreach ($donor_fields as $field) {
                if ($field->required=='1') {
                    $rules[$field->field_key] = 'required';
                }
            }

            $old_email = $donor->email;

            $validator = Validator::make($data, $rules);

            if ($validator->passes()) {
                //Update Info!
                foreach ($donor_fields as $field) {
                    //update the client fields (when they exist)
                    if (isset($data[$field->field_key])&&($field->field_type=='hysTable')) {
                        $json_fields[$field->field_key] = json_encode($data[$field->field_key]);
                    } elseif (isset($data[$field->field_key])) {
                        // $redis->hset('donor:id:'.$donor_id,$field->field_key,$data[$field->field_key]);
                        $json_fields[$field->field_key] = $data[$field->field_key];
                    }
                    $donor = Donor::find($donor_id);
                }

                if (isset($data['password']) && $data['password']!='') {
                    $donor = Donor::find($donor_id);
                    $password = Hash::make($data['password']);
                    unset($data['password']);

                    $donor->password = $password;

                    $donor->save();
                    $redis->hset($session_id.':messages', 'password', 'Your password has been successfully updated.');
                    $redis->expire($session_id.':messages', 3600);
                }
                if (isset($data['email'])) {
                    $donor = Donor::find($donor_id);
                    $old_email=$donor->email;
                        
                    if ($old_email!=$data['email']) {
                        $donor->email= $data['email'];
                        $donor->save();
                        $redis->hset($session_id.':messages', 'email', 'Your email address has been successfully updated.');
                        $redis->expire($session_id.':messages', 3600);
                    }
                }

                if (isset($data['do_not_email'])) {
                    $donor = Donor::find($donor_id);
                    $donor->do_not_email=$data['do_not_email'];
                    $donor->save();
                } else {
                    $donor = Donor::find($donor_id);
                    $donor->do_not_email=0;
                    $donor->save();
                }

                $donor->json_fields= json_encode($json_fields);
                $donor->save();

                //Reload the Cache entry for this donor!
                $donor->reloadDonorsToCache($donor);

                if ($donor->email==$old_email) {
                    $old_email= null;
                }
                $donor->syncDonorsToMailchimp($donor, $old_email);
            } else {
                $redis->hset($session_id.':messages', 'error', 'Error: Your info could not be updated.');
                $redis->expire($session_id.':messages', 3600);

                return redirect('frontend/donor_update_info/'.$client_id.'/'.$program_id.'/'.$session_id)
                ->withErrors($validator)
                ->withInput();
            }

            return redirect('frontend/donor_view/'.$client_id.'/'.$program_id.'/'.$session_id);
        }
    }


    public function updateCard($client_id, $program_id, $session_id = null)
    {
        if ($session_id==null) {
            return redirect('/frontend/login/'.$client_id.'/'.$program_id);
        }

        $redis= RedisL4::connection();

        //get individual donor with $donor_id
        if (($redis->hget($session_id, 'logged_in'))=='true') {
            $donor_id= $redis->hget($session_id, 'donor_id');
        } else {
            return redirect('/frontend/login/'.$client_id.'/'.$program_id);
        }

        $credit_card='[None]';

        //return var_dump($donation->isDonorCardActive($donor->id,$client_id));
        $donation = new Donation;
        if ($donation->isDonorCardActive($donor_id, $client_id)) {
            $credit_card='[Saved]';
        }

        $donor= Donor::find($donor_id);
        $sponsorships = $donor->getSponsorships($donor_id);
        
        // return var_dump($sponsorships);
        $commitments = Commitment::where('type', 2)->where('donor_id', $donor_id)->get();

        $months = ['01'=>'01','02'=>'02','03'=>'03','04'=>'04','05'=>'05','06'=>'06','07'=>'07','08'=>'08','09'=>'09','10'=>'10','11'=>'11','12'=>'12'];
        
        $today = Carbon::now();
        $i = 0;
        while ($i < 10) {
            $years[$today->year+$i] = $today->year+$i;
            $i++;
        }

        if ($program_id=='none') {
            $disable_program_link='';
        } else {
            $program= new Program;
            $program_ids=$program->getPrograms($client_id, $program_id);

            $program = Program::find($program_ids[0]);
            $program_settings = (array) json_decode(Setting::find($program->setting_id)->program_settings);

            $disable_program_link='';
            if (isset($program_settings['disable_program_link'])) {
                $disable_program_link=$program_settings['disable_program_link'];
            }
        }

        return view('donor.views.updateCard', [
            'client_id'     => $client_id,
            'program_id'    => $program_id,
            'session_id'    => $session_id,
            'months'        => $months,
            'years'         => $years,
            'disable_program_link' =>$disable_program_link,
            'credit_card'   => $credit_card,
            'sponsorships'  => $sponsorships,
            'commitments'   => $commitments
            ]);
    }

    public function postUpdateCard($client_id, $program_id, $session_id)
    {

        $redis= RedisL4::connection();

        //get individual donor with $donor_id
        if (($redis->hget($session_id, 'logged_in'))=='true') {
            $donor_id= $redis->hget($session_id, 'donor_id');
        } else {
            return redirect('/frontend/login/'.$client_id.'/'.$program_id);
        }

        //Let session live for one hour from now
        $redis->expire($session_id, 3600);
        if (isset($donor_id)) {
            $data= Input::all();

            $cc_rules= [
                        'firstName' => 'required',
                        'lastName' => 'required',
                        'number' => 'required|creditcard',
                        'cvv' => 'required',
                        'expiryMonth' => 'required',
                        'expiryYear' => 'required'
                        ];

            $validator = Validator::make($data, $cc_rules);


            if ($validator->passes()) {
                // try {
                        $card = [
                        'firstName' => $data['firstName'],
                        'lastName' => $data['lastName'],
                        'number' => $data['number'],
                        'cvv' => $data['cvv'],
                        'expiryMonth' => $data['expiryMonth'],
                        'expiryYear' => $data['expiryYear']
                        ];
                        $donation= new Donation;
                        $card_exists= $donation->isDonorCardActive($donor_id, $client_id);

                        $entity= new Entity;
                        $donor_name= $entity->getDonorName($donor_id);

                        $description= 'Credit Card updated by '.$donor_name['name'];

                        $params = [
                            'description' => $description,
                            'donor_id'  => $donor_id
                        ];

                        if ($card_exists==false) {
                            $response = $donation->createCustomer($card, $params, $client_id);
                        } else {
                            $response = $donation->updateCard($card, $params, $client_id);
                        }
                    

                    
                    //return var_dump($response);

                        if ($response->success) {
                            // Credit Card was successfully updated!
                            $cnt=[];
                            // Now let's update all Commitments to use this new card.
                            $donor= Donor::find($donor_id);

                            $donor->touch();
                        
                            //Reload the Cache entry for this donor!
                            $donor->reloadDonorsToCache($donor);

                            foreach (Commitment::where('client_id', $client_id)->where('donor_id', $donor_id)->get() as $c) {
                                if ($c->method!='3') {
                                    $tmp=$donor->getDesignation($c->type, $c->designation);
                                    $cnt[]=$tmp['name'];
                                    $c->method='3'; //This sets the method to Credit Card.
                                    $c->save();
                                }
                            }
                            $sponsorships= $donor->getSponsorships($donor->id);

                            $message1= 'Your Credit Card has been successfully '.($card_exists==true ? 'updated.' : 'added.');
                            if (!empty($sponsorships)) {
                                foreach ($sponsorships as $s) {
                                    $s_array[]=$s['name'].' @ '.$s['currency_symbol'].$s['commit'].' paid '.$s['frequency'];
                                }
                                $message1.="<br>The following sponsorship".(count($sponsorships)>1 ? 's': ''). ' will be paid with your new Credit Card.<br>'.implode('<br>', $s_array);
                            }
                            $message2='';
                            if (count($cnt)) {
                                $message2='<br/>The payment method was changed to Credit Card for: ';
                                foreach ($cnt as $c) {
                                    $message2.='</br>'.$c;
                                }
                            }
                            $redis->hset($session_id.':messages', '1', $message1.$message2);
                            $redis->expire($session_id.':messages', 3600);
                        } else {
                            // payment failed: display message to customer
                            $message = 'Payment failed.';
                            if (!empty($response->result)) {
                                $message = $response->result;
                            }
                            $redis->hset($session_id.':messages', 'error', 'Error: '.$message);
                            $redis->expire($session_id.':messages', 3600);
                            $alert = 'danger';
                            return redirect('frontend/donor_update_card/'.$client_id.'/'.$program_id.'/'.$session_id)
                            ->withErrors($validator)
                            ->withInput();
                        }
                 // } catch (\Exception $e) {
                 // 	$message = $e->getMessage();
                 // 	$redis->hset($session_id.':messages','error','Error: '.$message);
                 // 	$redis->expire($session_id.':messages',3600);
                 // 	$alert = 'danger';
                 // 	return redirect('frontend/donor_update_card/'.$client_id.'/'.$program_id.'/'.$session_id)
        // 					->withErrors($validator)
                    // 		->withInput();
                 // }
            } else {
                return redirect('frontend/donor_update_card/'.$client_id.'/'.$program_id.'/'.$session_id)
                ->withErrors($validator)
                ->withInput();
            }
            
            
            

            return redirect('frontend/donor_view/'.$client_id.'/'.$program_id.'/'.$session_id);
        }
    }


    public function donorView($client_id, $program_id, $session_id = null)
    {
                
            $redis = RedisL4::connection();

        if ($session_id== null) {
            return redirect('/frontend/login/'.$client_id.'/'.$program_id);
        }

            //get individual donor with $donor_id
        if (($redis->hget($session_id, 'logged_in'))=='true') {
            $donor_id= $redis->hget($session_id, 'donor_id');
        }

            $program= new Program;

            $program_ids=$program->getPrograms($client_id, $program_id);

            //Let session live for one hour from now
            $redis->expire($session_id, 3600);
        if (isset($donor_id)) {
            $donor = Donor::find($donor_id);

            $hysform_id = $donor->hysform_id;
            $fields = Donorfield::where('hysform_id', $hysform_id)
            ->where(function ($query) {
                $query->where('permissions', '=', 'public')
                      ->orWhere('permissions', '=', 'donor');
            })->orderBy('field_order')->get();

            $profile = json_decode($donor->json_fields, true);

            $sponsorships = $donor->getSponsorships($donor_id);
                
            $commitments = Commitment::where('type', 2)->where('donor_id', $donor_id)->get();

            $upload = new Upload;
            $profilePics=null;
            $temp_en_id=null;

                                
            if ($program_ids[0]=='none') {
                $settings=[];
                $program_settings=[];
                $currency = '$';

                $allow_email='1';
                $allow_donations='1';
                $text_account=null;
                $placeholder=URL::to('/images/placeholder.gif');
                $disable_program_link='';
            } else {
                $settings = $donor->getSettings($donor_id, $program_ids[0]);

                $program= new Program;
                $program_ids=$program->getPrograms($client_id, $program_id);

                $program = Program::find($program_ids[0]);

                $setting = Setting::find($program->setting_id);

                if (!empty($setting)) {
                    $program_settings = (array) json_decode($setting->program_settings);
                }

                $currency= $program_settings['currency_symbol'];

                if (isset($program_settings['placeholder'])&&$program_settings['placeholder']!='') {
                    $placeholder=$program_settings['placeholder'];
                } else {
                    $placeholder=URL::to('/images/placeholder.gif');
                }

                $allow_email=$settings->allow_email;
                $allow_donations=$settings->show_payment;
                $text_account=$settings->text_account;


                $disable_program_link='';
                if (isset($program_settings['disable_program_link'])) {
                    $disable_program_link=$program_settings['disable_program_link'];
                }
            }

            foreach ($sponsorships as $key => $sponsorship) {
                $temp_en_id=$sponsorship['id'];
                $entity=Entity::find($sponsorship['id']);
                    
                if (isset($entity)) {
                    $uploads = $entity->uploads()->where('profile', 1)->first();
                } else {
                    $uploads=null;
                }

                $profilePics[$key] = '';
                if (!empty($uploads)) {
                    $profilePics[$key] = $uploads->makeAWSlinkThumb($uploads);
                } else {
                    $profilePics[$key]= $placeholder;
                }
            }


                

            $donations = Donation::whereDonorId($donor_id)->orderBy('created_at', 'desc')->get();

            $d_names=null;
            foreach ($donations as $id => $donation) {
                if ($donation->type==1) {
                    $tmp_name = $donor->getEntityName($donation->designation, 'donor');

                    $d_names[$id]=$tmp_name['name'];
                }

                if ($donation->type==2) {
                    $d_names[$id]=Designation::withTrashed()->where('id', $donation->designation)->pluck('name');
                    //return $d_names[$id];
                }
            }
            $info=null;

            $commitments=$donor->getCommitments($donor_id);

                
            $donation= new Donation;
            $credit_card='[None]';

        //return var_dump($donation->isDonorCardActive($donor->id,$client_id));
            if ($donation->isDonorCardActive($donor->id, $client_id)) {
                $credit_card='[Saved]';
            }

            $funded_entities= $donor->getFundedEntities($client_id, $donor->id);

            foreach ($funded_entities as $key => $sponsorship) {
                $temp_en_id=$sponsorship['id'];
                $entity=Entity::find($sponsorship['id']);
                    
                if (isset($entity)) {
                    $uploads = $entity->uploads()->where('profile', 1)->first();
                } else {
                    $uploads=null;
                }

                $profilePics[$key] = '';
                if (!empty($uploads)) {
                    $profilePics[$key] = $uploads->makeAWSlinkThumb($uploads);
                } else {
                    $profilePics[$key]= $placeholder;
                }
            }
            $p = new Program;

            foreach ($sponsorships as $k => $s) {
                if (isset($funded_entities[$s['id']])) {
                    unset($funded_entities[$s['id']]);
                }

                $program_settings= (array) $p->getSettings($s['program_id']);


                if (!empty($program_settings['display_percent'])) {
                    $sponsorships[$k]['entity_percent']=$donor->getPercent($s['id']);
                    if ($sponsorships[$k]['entity_percent']>100) {
                        $sponsorships[$k]['entity_percent']= 100;
                    }
                }

                if (!empty($program_settings['display_info'])) {
                    if (!isset($sponsorships[$k]['entity_percent'])) {
                        $sponsorships[$k]['entity_percent']=$donor->getPercent($s['id']);
                    }
                        
                    if ($program_settings['program_type']=='number'||$sponsorships[$k]['entity_percent']==100) {
                        $sponsorships[$k]['entity_info']=$donor->getInfo($s['id']);
                    } else {
                        $sponsorships[$k]['entity_info']=$program_settings['currency_symbol'].$donor->getInfo($s['id']);
                    }
                }
            }
        //return var_dump($sponsorships);
        //return var_dump($profilePics);

            $can_donor_modify_amount='';
            $hide_payment = '';

            $hysform= Hysform::find($donor->hysform_id);

            if (count($hysform)) {
                $can_donor_modify_amount=$hysform->can_donor_modify_amount;
                $hide_payment = $hysform->hide_payment;
            }

            $gateway= new Gateway;

            $useCC=$gateway->hasCC($client_id);

            return view('donor.views.Donor', [
            'session_id'    => $session_id,
            'client_id'     => $client_id,
            'program_id'    => $program_id,
            'profile'       => $profile,
            'fields'        => $fields,
            'donor'         => $donor,
            'redis'         => $redis,
            'username'      => $donor->username,
            'email'         => $donor->email,
            'sponsorships'  => $sponsorships,
            'funded_entities'=> $funded_entities,
            'commitments'   => $commitments,
            'can_donor_modify_amount' => $can_donor_modify_amount,
            'text_account'  => $text_account,
            'allow_email'   => $allow_email,
            'profilePics'   => $profilePics,
            'donations'     => $donations,
            'd_names'       => $d_names,
            'allow_donations'   => $allow_donations,
            'text_account'  => $text_account,
            'useCC'         => $useCC,
            'credit_card'   => $credit_card,
            'currency'      => $currency,
            'disable_program_link' =>$disable_program_link,
            'hide_payment'  => $hide_payment]);
        }

        return redirect('frontend/login/'.$client_id.'/'.$program_id);
    }

    public function modifyCommitmentAmount($client_id, $program_id, $commitment_id, $session_id)
    {

        //Authenticate Donor
        $redis =RedisL4::connection();

        if ($session_id==null||$redis->exists($session_id)!=1) {
            return redirect('/frontend/view_all/'.$client_id.'/'.$program_id.'/'.$session_id);
        }

        //get individual donor with $donor_id
        if (($redis->hget($session_id, 'logged_in'))=='true') {
            $donor_id= $redis->hget($session_id, 'donor_id');
        } else {
            return redirect('/frontend/view_all/'.$client_id.'/'.$program_id.'/'.$session_id);
        }

        $redis->expire($session_id, 3600);


        //Get commitment information (but only allow this donor)
        $commitment = Commitment::where('donor_id', $donor_id)->find($commitment_id);

        if (!count($commitment)) {
            $message='Error: The commitment could not be changed, because it was not found.';
            $redis->hset($session_id.':messages', '1', $message);
            $redis->expire($session_id.':messages', 3600);
            return Redirect::back();
        }

        $donor =  Donor::find($donor_id);

        $designation= $donor->getDesignation($commitment->type, $commitment->designation);

        $sponsorships = $donor->getSponsorships($donor_id);

        if (!count($sponsorships)) {
            $message='Error: The commitment could not be changed, because it was not found.';
            $redis->hset($session_id.':messages', '1', $message);
            $redis->expire($session_id.':messages', 3600);
            return Redirect::back();
        }

        $hysform= Hysform::find($donor->hysform_id);

        if ($hysform->can_donor_modify_amount!='1') {
            $message='Error: The commitment could not be changed, because you don\'t have permission to change it.';
            $redis->hset($session_id.':messages', '1', $message);
            $redis->expire($session_id.':messages', 3600);
            return Redirect::back();
        }

        $sponsorship = $sponsorships[$commitment_id];

        $frequency_options = [];
        if ($commitment->type=='1') {
            $entity= Entity::find($commitment->designation);
            $program = Program::find($entity->program_id);
            $program_settings = (array) json_decode(Setting::find($program->setting_id)->program_settings);
            $type = false;
            if (isset($program_settings['program_type'])) {
                $type=$program_settings['program_type'];
            }
            $d= new Donor;
            $frequency_options=$d->getFrequencies($type);
            array_unshift($frequency_options, 'Select Schedule');
            // return var_export($frequency_options);
        }

        return view('donor.views.modifyCommitmentAmount')->with([
            'client_id'     => $client_id,
            'program_id'    => $program_id,
            'commitment'    => $commitment,
            'session_id'    => $session_id,
            'designation'   => $designation,
            'donor'         => $donor,
            'sponsorship'   => $sponsorship,
            'frequency_options' => $frequency_options]);
    }

    public function postModifyCommitmentAmount($client_id, $program_id, $commitment_id, $session_id)
    {
        //Authenticate Donor
        $redis =RedisL4::connection();

        if ($session_id==null||$redis->exists($session_id)!=1) {
            return redirect('/frontend/view_all/'.$client_id.'/'.$program_id.'/'.$session_id);
        }

        //get individual donor with $donor_id
        if (($redis->hget($session_id, 'logged_in'))=='true') {
            $donor_id= $redis->hget($session_id, 'donor_id');
        } else {
            return redirect('/frontend/view_all/'.$client_id.'/'.$program_id.'/'.$session_id);
        }

        $redis->expire($session_id, 3600);

        //Find out if the Donor is allowed to change their commitment amount
        $donor =  Donor::find($donor_id);


        $hysform= Hysform::find($donor->hysform_id);

        if ($hysform->can_donor_modify_amount!='1') {
            $message='Error: The commitment could not be changed, because you don\'t have permission to change it.';
            $redis->hset($session_id.':messages', '1', $message);
            $redis->expire($session_id.':messages', 3600);
            return redirect('frontend/donor_view/'.$client_id.'/'.$program_id.'/'.$session_id);
        }

        //Get commitment information (but only allow this donor)
        $commitment = Commitment::where('donor_id', $donor_id)->find($commitment_id);

        $designation= $donor->getDesignation($commitment->type, $commitment->designation);


        if (!count($commitment)) {
            $message='Error: The commitment could not be changed, because it was not found.';
            $redis->hset($session_id.':messages', '1', $message);
            $redis->expire($session_id.':messages', 3600);
            return redirect('frontend/donor_view/'.$client_id.'/'.$program_id.'/'.$session_id);
        }

        $data = Input::all();
            
        if (isset($data['new_amount'])) {
            $rules=[
                'new_amount'=>'numeric|min:1',
                'frequency' => 'numeric'];
            $validator = Validator::make($data, $rules);

            if ($validator->passes()) {
            //If it's not ARB
                if ($commitment->method!=5) {
                    if (!empty($data['new_amount'])) {
                        $commitment->amount = $data['new_amount'];
                        $commitment->save();
                    }
                    if ($data['frequency']!=0) {
                        $commitment->frequency = $data['frequency'];
                        //Remove last so that the credit card will run immediately
                        $commitment->last= null;
                        $commitment->save();
                    }
                    if ($commitment->type=='entity') {
                        $donor->setStatus($commitment->designation);
                    }
                    $message='The amount for '.$designation['name']. ' was successfully changed to: '.$commitment->amount;
                    $redis->hset($session_id.':messages', '1', $message);
                    $redis->expire($session_id.':messages', 3600);
                } else //If it is ARB
                {
                    if (empty($commitment->arb_subscription_id)) {
                        $message='Error: The amount could not be changed, because the ARB subscription ID was not entered by the admin';
                        $redis->hset($session_id.':messages', '1', $message);
                        $redis->expire($session_id.':messages', 3600);
                        return redirect('frontend/donor_view/'.$client_id.'/'.$program_id.'/'.$session_id);
                    }
                    $donation = new Donation;
                    $result = $donation->modifyARBCommitmentAmount($commitment, $data['new_amount']);

                    if ($result=='Ok') {
                        if (!empty($data['new_amount'])) {
                            $commitment->amount=$data['new_amount'];
                            $commitment->save();
                        }
                        if ($data['frequency']!=0) {
                            $commitment->frequency = $data['frequency'];
                            //Remove last so that the credit card will run immediately
                            $commitment->last= null;
                            $commitment->save();
                        }
                        if ($commitment->type=='entity') {
                            $donor->setStatus($commitment->designation);
                        }
                        $message='The amount for '.$designation['name']. ' was successfully changed to: '.$commitment->amount;
                        $redis->hset($session_id.':messages', '1', $message);
                        $redis->expire($session_id.':messages', 3600);
                    } else {
                        $message='An error occured with Authorize.Net, the amount could not be changed. <br>'.$result;
                        $redis->hset($session_id.':messages', '1', $message);
                        $redis->expire($session_id.':messages', 3600);
                    }
                }
            } else {
                return Redirect::back()->withErrors($validator);
            }
        }

        return redirect('frontend/donor_view/'.$client_id.'/'.$program_id.'/'.$session_id);
    }
    
    public function DonorViewEntity($client_id, $program_id, $entity_id, $session_id)
    {


        $redis = RedisL4::connection();

        if ($session_id==null||$redis->exists($session_id)!=1) {
            return redirect('/frontend/view_all/'.$client_id.'/'.$program_id.'/'.$session_id);
        }

        //get individual donor with $donor_id
        if (($redis->hget($session_id, 'logged_in'))=='true') {
            $donor_id= $redis->hget($session_id, 'donor_id');
        } else {
            return redirect('/frontend/view_all/'.$client_id.'/'.$program_id.'/'.$session_id);
        }

        $donor=new Donor;

        $sponsorships = $donor->getSponsorships($donor_id);

        $test= false;

        foreach ($sponsorships as $sp) {
            if ($sp['id']==$entity_id) {
                $test= true;
            }
        }

        if ($test==false) {
            return 'Entity not found';
        }
                
        $entity = Entity::find($entity_id);
                
        $profile = json_decode($entity->json_fields, true);
        $profile['id'] = $entity_id;
            

        if (!isset($entity)) {
            return "Entity not available";
        }

        $program = new Program;

        $program_ids = $program->getPrograms($client_id, $program_id);

        $program= Program::find($entity->program_id);


        //Only get fields fields designated as 'public' or 'donor'
            
        $donor_fields = Field::where('client_id', $client_id)
        ->where('hysform_id', $program->hysform_id)
        ->where(function ($query) {
            $query->where('permissions', '=', 'public')
                  ->orWhere('permissions', '=', 'donor');
        })->orderBy('field_order')
        ->get();
            

        $donor = new Donor;

        $title = $donor->getEntityName($entity_id, 'donor');

            
        $upload=new Upload;

        $file_links=[];
        $image_links=[];
        foreach ($entity->uploads as $k => $file) {
            if ($file->permissions=='public'||$file->permissions=='donor') {
                if ($file->profile==1) {
                    $link = $upload->makeAWSlink($file);
                }

                if ($file->profile!=1) {
                    if ($file->type=='image') {
                        $image_links[$k]['original'] = $upload->makeAWSlink($file);
                        $image_links[$k]['thumbnail'] = $upload->makeAWSlinkThumb($file);
                    } else {
                        $file_links[$k]['file_link']=$upload->makeAWSlink($file);
                        ;
                        $file_links[$k]['file_name']=$upload->makeAWSlinkThumb($file);
                        ;
                    }
                }
            }
        }
             
        if (isset($link)) {
            $profilePic=$link;
        } else {
            $program_settings = (array) json_decode(Setting::find($program->setting_id)->program_settings);

            if (isset($program_settings['placeholder'])&&$program_settings['placeholder']!='') {
                $profilePic=$program_settings['placeholder'];
            } else {
                $profilePic=URL::to('/images/placeholder.gif');
            }
        }

            
        $program_settings = (array) json_decode(Setting::find($program->setting_id)->program_settings);

        $disable_program_link='';
        if (isset($program_settings['disable_program_link'])) {
            $disable_program_link=$program_settings['disable_program_link'];
        }
        
        //This will get us the email record

         $emails = Donoremail::where('donor_id', '=', $donor_id)->where('entity_id', '=', $entity_id)->get();

        $email_children= DB::table('donoremails AS t1')
        ->where('t1.donor_id', '=', $donor_id)
        ->where('t1.entity_id', '=', $entity_id)
        ->leftJoin('donoremails AS t2', 't2.parent_id', '=', 't1.id')
        ->get();

        $settings = $donor->getSettings($donor_id);
        if (!count($settings)) {
            $allow_email='';
        } else {
            $allow_email = $settings->allow_email;
        }
            
        $new_profile= $entity->formatProfile($profile, $donor_fields);

        //return var_dump($file_links);

        return view('frontend.views.donorPrintEntity', [
            'title'         =>  $title,
            'session_id'    =>  $session_id,
            'entity_id'     =>  $entity_id,
            'client_id'     =>  $client_id,
            'profile'       =>  $new_profile,
            'donor_fields'  =>  $donor_fields,
            'program_id'    =>  $program_id,
            'profilePic'    =>  $profilePic,
            'file_links'    =>  $file_links,
            'image_links'   =>  $image_links,
            'emails'        =>  $emails,
            'email_children'=>  $email_children,
            'allow_emails'  =>  $allow_email,
            'disable_program_link' => $disable_program_link]);
    }

    public function DonorViewEntityMessageHistory($client_id, $program_id, $entity_id, $session_id)
    {


        $redis = RedisL4::connection();

        if ($redis->exists($session_id) != 1) {
            return redirect('/frontend/view_all/'.$client_id.'/'.$program_id.'/'.$session_id);
        }
            
    //get individual donor with $donor_id
        if (($redis->hget($session_id, 'logged_in') ) == 'true') {
            $donor_id = $redis->hget($session_id, 'donor_id');
        } else {
            return redirect('/frontend/login/'.$client_id.'/'.$program_id);
        }

        $donor = new Donor;

        $sponsorships = $donor->getSponsorships($donor_id);

        $recipients = $donor->getFundedEntities($client_id, $donor_id);

        $test = false;

        foreach ($sponsorships as $sp) {
            if ($sp['id'] == $entity_id) {
                $test = true;
            }
        }

        foreach ($recipients as $rp) {
            if ($rp['id'] == $entity_id) {
                $test = true;
            }
        }

        if ($test == false) {
            return 'Entity not found';
        }
            
        $entity = Entity::find($entity_id);
            
        $profile = json_decode($entity->json_fields, true);

        $profile['id'] = $entity_id;
            

        if (!isset($entity)) {
            return "Entity not available";
        }
            
        $program= Program::find($entity->program_id);

        $program_settings = (array) json_decode(Setting::find($program->setting_id)->program_settings);

        $disable_program_link='';
        if (isset($program_settings['disable_program_link'])) {
            $disable_program_link=$program_settings['disable_program_link'];
        }
            
            
        $entity_name = $donor->getEntityName($entity_id, 'donor');
            
        $emails = Donoremail::withTrashed()->where('donor_id', '=', $donor_id)->where('entity_id', '=', $entity_id)->orderBy('created_at', 'desc')->get();

        $email_children = DB::table('donoremails AS t1')
        ->where('t1.donor_id', '=', $donor_id)
        ->where('t1.entity_id', '=', $entity_id)
        ->leftJoin('donoremails AS t2', 't2.parent_id', '=', 't1.id')
        ->get();

        $p = new Program;
        $settings = $p->getBaseSettingsFromEntity($entity_id);

        return view('frontend.views.donorEntityMessageHistory', [
        'session_id'    =>  $session_id,
        'entity_id'     =>  $entity_id,
        'client_id'     =>  $client_id,
        'profile'       =>  $profile,
        'program_id'    =>  $program_id,
        'emails'        =>  $emails,
        'email_children'=>  $email_children,
        'entity_name'   =>  $entity_name['name'],
        'allow_emails'  =>  $settings->allow_email,
        'disable_program_link' => $disable_program_link]);
    }

    public function DonorViewEntityCompose($client_id, $program_id, $entity_id, $parent_id, $session_id)
    {


        $redis = RedisL4::connection();

        if ($redis->exists($session_id)!=1) {
            return redirect('/frontend/view_all/'.$client_id.'/'.$program_id);
        }

        //get individual donor with $donor_id
        if (($redis->hget($session_id, 'logged_in'))=='true') {
            $donor_id= $redis->hget($session_id, 'donor_id');
        } else {
            return redirect('/frontend/view_all/'.$client_id.'/'.$program_id);
        }

        $donor=new Donor;

        $sponsorships = $donor->getSponsorships($donor_id);

        $parent_email = null;
        if ($parent_id!='0') {
            $parent_email = Donoremail::find($parent_id);
            if ($parent_email==null) {
                return "Error, parent email not found.";
            }
        }

        $test= false;

        foreach ($sponsorships as $sp) {
            if ($sp['id']==$entity_id) {
                $test= true;
            }
        }

        $recipients = $donor->getFundedEntities($client_id, $donor_id);

        foreach ($recipients as $rp) {
            if ($rp['id']==$entity_id) {
                $test= true;
            }
        }


        if ($test==false) {
            return 'Entity not found';
        }
                
        $entity = Entity::find($entity_id);

        $profile = json_decode($entity->json_fields, true);
        $profile['id'] = $entity_id;

        if (!isset($entity)) {
            return "Entity not available";
        }


        $program= Program::find($entity->program_id);
            
        $entity_name= $donor->getEntityName($entity_id, 'donor');
            


        $settings = $program->getBaseSettingsFromEntity($entity_id);

        $program= Program::find($entity->program_id);

        $program_settings = (array) json_decode(Setting::find($program->setting_id)->program_settings);

        $disable_program_link='';
        if (isset($program_settings['disable_program_link'])) {
            $disable_program_link=$program_settings['disable_program_link'];
        }
            


        return view('frontend.views.donorPrintEntityCompose', [
            'session_id'    =>  $session_id,
            'entity_id'     =>  $entity_id,
            'client_id'     =>  $client_id,
            'parent_id'     =>  $parent_id,
            'parent_email'  =>  $parent_email,
            'profile'       =>  $profile,
            'program_id'    =>  $program_id,
            'entity_name'   =>  $entity_name['name'],
            'allow_emails'  =>  $settings->allow_email,
            'disable_program_link' => $disable_program_link]);
    }

    public function PostDonorViewEntity($client_id, $program_id, $entity_id, $parent_id, $session_id)
    {


        $redis = RedisL4::connection();

        if ($redis->exists($session_id)!=1) {
            return redirect('/frontend/view_all/'.$client_id.'/'.$program_id.'/'.$session_id);
        }

        //get individual donor with $donor_id
        if (($redis->hget($session_id, 'logged_in'))=='true') {
            $donor_id= $redis->hget($session_id, 'donor_id');
        }

        $donor=new Donor;

        $sponsorships = $donor->getSponsorships($donor_id);

        $test= false;

        foreach ($sponsorships as $sp) {
            if ($sp['id']==$entity_id) {
                $test= true;
            }
        }

        $recipients = $donor->getFundedEntities($client_id, $donor_id);

        foreach ($recipients as $rp) {
            if ($rp['id']==$entity_id) {
                $test= true;
            }
        }

        if ($test==false) {
            return "Entity not available";
        }
            
        $entity = Entity::find($entity_id);

        $entity_name= $donor->getEntityName($entity_id, 'donor');

        if (!isset($entity)) {
            return "Entity not available";
        }

        $data=Input::all();

            

        $message=$data['message'];

        $parent_email= Donoremail::find($parent_id);
        if ($parent_email!=null) {
            $email= new Donoremail;
            $subject = "Re: ".$parent_email->subject;

            $email->message=$message;
            $email->client_id=$client_id;
            $email->parent_id= $parent_id;
            $email->status=1; //1 = new email

            $email->save();
        } else {
            $subject=$data['subject'];
            $email= new Donoremail;

            $email->subject=$subject;
            $email->message=$message;
            $email->client_id=$client_id;
            $email->entity_id=$entity_id;
            $email->donor_id=$donor_id;
            //$email->parent_id= ???
            $email->status=1; //1 = new email

            $email->save();
        }

        $admin_email= new Emailtemplate;

        $details['entity'] = $admin_email->getEntity($entity_id);
        $details['donor'] = $admin_email->getDonor($donor_id);
        $details['message']= ['body' => $message, 'subject' => $subject];

        $d = $donor->getDesignation(1, $entity_id);

        $to= ['type' => 'admin','email' => '' ,'name' => 'admin'];

        $emailSent = $admin_email->sendEmail($d['emailset_id'], $details, 'sp_email', $to);

        $redis->hset($session_id.':messages', '1', 'Message sent to '.$entity_name['name']);
        $redis->expire($session_id.':messages', 3600);

        return redirect('frontend/donor_view/'.$client_id.'/'.$entity->program_id.'/'.$session_id);
    }
}
