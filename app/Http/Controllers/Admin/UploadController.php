<?php  namespace App\Controllers\Admin;
 
    use Auth;
use BaseController;
use Form;
use Input;
use Redirect;
use Sentry;
use View;
use Validator;
use Session;
use Entity;
use AWS;
use Image;
use File;
use Upload;
use DB;
use Donor;
use Program;
use Hysform;
use Emailset ;
use Field;
use Donorfield;
use Carbon;
use RedisL4;
use Setting;
use URL;
use Client;
use Emailtemplate;
use Hash;
use Commitment;
use DonorEntity;
use Queue;
use Import;
    
use App\Http\Controllers\Controller;

class UploadController extends Controller
{
    
    // path to temporary folder used for resizing/rotating files.
    private function tempFileFolder()
    {
        $public = public_path();
        return $path = "$public/temp/";
    }
        
    public function uploadFile($type, $id)
    {
        $redis = RedisL4::connection();
            
        $upload=new Upload;
        $client_id= Session::get('client_id');
        $client= Client::find($client_id);
        $emailsets = false;
        $template_errors=[];
        $years = [];

        if (!empty($client)) {
            $box_access_token=$client->box_access_token;
        } else {
            $box_access_token= '';
        }

        if ($upload->useBox()) {
            if ($upload->isBoxLoggedIn()==false) {
                return $upload->logBoxIn(null, URL::to('admin/upload_file/'.$type.'/'.$id));
            }
            $client= Client::find(Session::get('client_id'));
            $box_access_token=$client->box_access_token;
        }

        if ($type == 'entity') {
            $entity = Entity::where('client_id', Session::get('client_id'))->withTrashed()->find($id);
            if (count($entity)==0) {
                return "Error: Entity Not Found.";
            }

            $program_id = $entity->program_id;

            $box_folder_id=false;
            if ($upload->useBox()) {
                $box_folder_id=$upload->getBoxFolderId($program_id, $client_id);
                if ($box_folder_id==false) {
                    $box_folder_id = '0';
                }
            }
                
            $donor = new Donor;
            $entity_name = $donor->getEntityName($id);
            $name = $entity_name['name'];
            $entity = Entity::withTrashed()->find($id);
            $the_donor = '';
            $hysform = '';

            // submit only forms
            $program = Program::find($program_id);
            $submit_ids = [];
            $submit = [];
            if (!empty($program->entity_submit)) {
                $submit_ids = explode(',', $program->entity_submit);
                $submit = Hysform::whereClientId($client_id)->whereIn('id', $submit_ids)->get();
            }
        } elseif ($type == 'donor') {
            $donor = Donor::where('client_id', Session::get('client_id'))->withTrashed()->find($id);
            if (count($donor)==0) {
                return "Error: Donor Not Found.";
            }
                
            $box_folder_id = false;
            $program_id = 'donor';
            $e = new Entity;
            $entity = '';
            $donor = $e->getDonorName($id);
            $the_donor = Donor::withTrashed()->find($id);
            $hysform = Hysform::find($the_donor->hysform_id);

            $name = $donor['name'];

            if ($upload->useBox()) {
                $box_folder_id=$upload->getBoxDonorFolderId($the_donor->hysform_id, $client_id);
                if ($box_folder_id == false) {
                    $box_folder_id = '0';
                }
            }

            $emailset = new Emailset;
            $emailsets = $emailset->getEmailSets($the_donor->hysform_id);
            $years = $the_donor->getYears($the_donor);
            if (!empty($emailsets['default_emailset'])) {
                $t = new Emailtemplate;
                $e_s= Emailset::where('id', $emailsets['default_emailset']['id'])->get();
                $template_errors = $t->templateErrors($e_s);
            }

            // submit only forms
            $program = Hysform::find($the_donor->hysform_id);
            $submit_ids = [];
            $submit = [];
            if (!empty($program->entity_submit)) {
                $submit_ids = explode(',', $program->entity_submit);
                $submit = Hysform::whereClientId($client_id)->whereIn('id', $submit_ids)->get();
            }
        }
            
            

        //get The thumbnail for the entity
        $upload = new Upload;
        if ($type=='entity') {
            $uploads = Entity::withTrashed()->find($id)->uploads()->where('profile', 1)->first();
        }
        if ($type=='donor') {
            $uploads = Donor::withTrashed()->find($id)->uploads()->where('profile', 1)->first();
        }

        $profileThumb = '';
        if (!empty($uploads)) {
            $profileThumb = $uploads->makeAWSlinkThumb($uploads);
        }

        return view('admin.views.uploadFile', [
            'type' => $type,
            'id' => $id,
            'name' => $name,
            'program' => $program,
            'hysform'   => $hysform,
            'entity'    => $entity,
            'program_id' => $program_id,
            'profileThumb' => $profileThumb,
            'submit' => $submit,
            'donor' => $the_donor,
            'slug'  => sha1(time()*time()),
            'upload'=> $upload,
            'box_access_token'=>$box_access_token,
            'box_folder_id'=>$box_folder_id,
            'emailsets' =>$emailsets,
            'years' => $years,
            'template_errors'=>$template_errors
        ]);
    }
        
    public function filesTable($type, $id)
    {
        $s = '';
        $n = 0;
        $sponsors = [];
        $entities = [];
        if ($type == 'entity') {
            $uploads = Entity::withTrashed()->find($id)->uploads;
            $e = new Entity;
            $sponsors = $e->getSponsors($id);
            $n = count($sponsors);
            if ($n>1) {
                $s = 's';
            }
        } elseif ($type == 'donor') {
            $uploads = Donor::find($id)->uploads;
            $e = new Entity;
            $entities= $e->getEntitiesFromDonor($id);
            $n = count($entities);
            if ($n>1) {
                $s= 'Entities';
            }
        }
        $box_exists = false;
        $upload = new Upload;
        $files = [];
        $links = [];
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
                'id' => $file->id,
                'file_name' => $file->file_name,
                'box_name'  => $file->box_name,
                'type' => $filetype,
                'permissions' => $permissions,
                'profile' => $file->profile,
                'created_at' => $file->created_at,
                'updated_at' => $file->updated_at,
                'link' => $upload->makeAWSlink($file),
                'thumb_link' => $upload->makeAWSlinkThumb($file),
                'entity_type'   => $type
            ];
        }
            
        return view('admin.views.filesTable')
            ->with([
                'files' => $files,
                'box_exists'=> $box_exists,
                'type' => $type,
                's' => $s,
                'n'=> $n,
                'id'=>$id,
                'sponsors'=>$sponsors,
                'entities' => $entities,
                'useBox'    =>$upload->useBox()
                        ]);
    }
        
// need to make this into a function so we can upload files from anywhere in the app    	
    public function postUploadFile($type, $id)
    {
        //ini_set('memory_limit', '3G');
        $input = Input::all();
            
        $rules = [
            'file' => 'mimes:pdf,doc,docx,xls,txt,zip,csv,jpg,jpeg,gif,png|max:3000',
        ];
         
        $validation = Validator::make($input, $rules);
         
        if ($validation->fails()) {
            return redirect('admin/upload_file/'.$type.'/'.$id.'')
                ->withErrors($validator)
                ->withInput();
        }
         
        $file = Input::file('file');

        //return var_dump(Input::all());

        $extension = $file->getClientOriginalExtension();
        $name = $file->getClientOriginalName();
        $thesha = sha1(time().time());
        $thumb_filename = $thesha.'_t_.'.$extension;
        $filename = $thesha.'.'.$extension;

        $sourceFile = $file->getRealPath();

        //$upload= new Upload;

        //$upload->uploadToBox(null,$file,$filename);

        //echo "RESULT:".$msg;

        $ext = strtolower($extension);
        if ($ext == 'png' or $ext == 'gif' or $ext == 'jpg' or $ext == 'jpeg') {
            $filetype = 'image';
                
            // resize image to 500px wide by proportional height, quality 70
            $img = Image::make($sourceFile)->resize(500, null, function ($constraint) {
                $constraint->aspectRatio();
            })->save($this->tempFileFolder().$filename, 70);
            $img = Image::make($sourceFile)->fit(300, '', 'top')->save($this->tempFileFolder().$thumb_filename, 70);

            //This saves a 300x300px thumbnail of the image in addition to the original 500px version
            //This is done so we can have thumbnail images be of uniform size on the frontend
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
                'ACL'    => 'public-read-write',
            ]);
                
            if ($filetype != 'doc') {
                $s3->putObject([
                'Bucket'     => 'hys',
                'Key'        => $thumb_filename,
                'SourceFile' => $sourceFileThumb,
                'ACL'    => 'public-read-write',
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
                $upload->entity_id = $id;
            } elseif ($type == 'donor') {
                $upload->donor_id = $id;
            }
            $upload->name = $filename;
            //The below command should perhaps be added
            //$upload->thumnail_name= $thumb_filename;
            $upload->file_name = $name;
            $upload->type = $filetype;
            $upload->permissions = 'public';
            $upload->save();
        }
    }
        
    // this function creates thumbnails of all existing image files on the server
    public function thumbExisting()
    {
        $uploads = Upload::all();
        foreach ($uploads as $u) {
            Queue::push('makeThumbnail', ['upload_id'=>$u->id,'tempFileFolder'=>$this->tempFileFolder()]);
        }
    }
        
    // this function will make a thumbnail of any image handed to it and then upload it to AWS
    public function makeThumbnail($filename, $file = null)
    {
        $upload = new Upload;

        return $upload->makeThumbnail($filename, $file);
    }
        
    public function csvImport($program_id)
    {
        $program = Program::find($program_id);
        return view('admin.views.csvImport')->with('program', $program);
    }
        
    public function postcsvImport($program_id)
    {
        ini_set("auto_detect_line_endings", "1");

        $input = Input::all();
            
        // need to create some validation, validator doesn't work here
        $file = Input::file('file');
        if (empty($file)) {
            return redirect('admin/csv_import/'.$program_id.'')
                ->with('alert', 'danger')
                ->with('message', 'There was no file uploaded.');
        }
            
        $extension = $file->getClientOriginalExtension();
        $name = $file->getClientOriginalName();
        $filename = sha1(time().time()).'.'.$extension;
        $sourceFile = Input::file('file')->getRealPath();
            
        try {
            $s3 = AWS::get('s3');
            $s3->putObject([
                'Bucket'     => 'hysfiles',
                'Key'        => $filename,
                'SourceFile' => $sourceFile,
                'ACL'    => 'public-read-write',
            ]);
        } catch (S3Exception $e) {
            return redirect('admin/csv_import/'.$program_id.'')
                ->with('alert', 'danger')
                ->with('message', 'There was an error uploading the file - ' . $e->getMessage());
        }
            
        $parser = \KzykHys\CsvParser\CsvParser::fromFile($file, ['limit' => 1]);
        $firstRow = $parser->parse();

        if (!isset($e)) {
            $program = Program::find($program_id);
            $settings = Setting::find($program->setting_id);
                
            switch ($input['import_type']) {
                case 'recipients':
                    $fields = Field::whereHysformId($program->hysform_id)->orderBy('field_order')->get();
                    $ps = json_decode($settings->program_settings);
                    $sponsorship_amount = explode(",", $ps->sponsorship_amount);
                    $number_spon = explode(",", $ps->number_spon);
                    $sp_num = explode(",", $ps->sp_num);
                        
                    $settings = ['program_type' => $ps->program_type, 'sponsorship_amount' => $sponsorship_amount, 'number_spon' => $number_spon, 'sp_num' => $sp_num];
                    break;
                        
                case 'donors':
                    $fields = Donorfield::whereHysformId($program->donor_hysform_id)->orderBy('field_order')->get();
                    break;
                        
                case 'relationships':
                    $entity_fields = Field::whereHysformId($program->hysform_id)->orderBy('field_order')->get();
                    $donor_fields = Donorfield::whereHysformId($program->donor_hysform_id)->orderBy('field_order')->get();
                        
                    return view('admin.views.csvRelationships', [
                        'entity_fields' => $entity_fields,
                        'donor_fields' => $donor_fields,
                        'first_row' => $firstRow,
                        'filename' => $filename,
                        'program_id' => $program_id,
                        'program' => $program,
                        'import_type' => $input['import_type']
                    ]);
                        
                    break;
                case 'payments':
                    $entity_fields = Field::whereHysformId($program->hysform_id)->orderBy('field_order')->get();
                    $donor_fields = Donorfield::whereHysformId($program->donor_hysform_id)->orderBy('field_order')->get();
                        
                    return view('admin.views.csvPayments', [
                        'entity_fields' => $entity_fields,
                        'donor_fields' => $donor_fields,
                        'first_row' => $firstRow,
                        'filename' => $filename,
                        'program_id' => $program_id,
                        'program' => $program,
                        'import_type' => $input['import_type']
                    ]);
                    break;
                case 'emails':
                    break;
            }
                                
            // check for auto increment id
            $aii = false;
            foreach ($fields as $field) {
                if ($field->field_type == 'hysCustomid') {
                    $aii = $field->field_key;
                }
            }
                
            return view('admin.views.csvColumns', [
                'fields' => $fields,
                'first_row' => $firstRow,
                'filename' => $filename,
                'program_id' => $program_id,
                'program' => $program,
                'import_type' => $input['import_type'],
                'settings' => $settings,
                'aii' => $aii
            ]);
        }
    }
        
    // process the import job for donors and recipients
    public function postProcessCSV($program_id)
    {
        $data = Input::all();
        $user = Sentry::getUser();
        $data['user_id'] = $user->id;
        $data['program_id'] = $program_id;
        $data['client_id'] = Session::get('client_id');
            
        // queue job
        Queue::push('importCsvToDatabase', $data);
                    
        return redirect('admin/manage_program')
            ->with('alert', 'success')
            ->with('message', 'Your import has been queued. You will receive an email when the job has finished.');
    }
        
    // process the import job for sponsorship relationships
    public function postProcessRelationshipsCSV($program_id)
    {
        $data = Input::all();
        $user = Sentry::getUser();
        $data['user_id'] = $user->id;
        $data['program_id'] = $program_id;
        $data['client_id'] = Session::get('client_id');

        // queue job
        Queue::push('importRelationshipsCsvToDatabase', $data);
                        
        return redirect('admin/manage_program')
            ->with('alert', 'success')
            ->with('message', 'Your import has been queued. You will receive an email when the job has finished.');
    }
        
    public function postProcessPaymentsCSV($program_id)
    {
        $data = Input::all();
        $user = Sentry::getUser();
        $data['user_id'] = $user->id;
        $data['program_id'] = $program_id;
        $data['client_id'] = Session::get('client_id');
            
        //queue job
        Queue::push('importPaymentsCsvToDatabase', $data);
            
        return redirect('admin/manage_program')
            ->with('alert', 'success')
            ->with('message', 'Your import has been queued. You will receive an email when the job has finished.');
    }
        
    public function generate_password()
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $password = substr(str_shuffle($chars), 0, 8);
            
        return $password;
    }
        
    //  from - http://darklaunch.com/2009/05/06/php-normalize-newlines-line-endings-crlf-cr-lf-unix-windows-mac
    public function normalize($string)
    {
        // Normalize line endings
        // Convert all line-endings to UNIX format
        $string = str_replace("\r\n", "\n", $string);
        $string = str_replace("\r", "\n", $string);
        // Don't allow out-of-control blank lines
        $string = preg_replace("/\n{2,}/", "\n\n", $string);
            
        return $string;
    }
            
    public function rotatePic($id)
    {
        $pic = Upload::find($id);
            
        // create Image from file, rotate 90 degrees clockwise, save in temp folder
        $data = file_get_contents('https://s3-us-west-1.amazonaws.com/hys/'.$pic->name.'');
        $img = Image::make($data)->rotate(-90)->save($this->tempFileFolder().$pic->name);
            
        // save it back to AWS
        try {
            $s3 = AWS::get('s3');
            $s3->putObject([
                'Bucket'     => 'hys',
                'Key'        => $pic->name,
                'SourceFile' => ''.$this->tempFileFolder().$pic->name.'',
                'ACL'    => 'public-read-write',
            ]);
        } catch (S3Exception $e) {
            echo "There was an error uploading the file.\n";
        }
            
        // make the thumbnail
        $this->makeThumbnail($pic->name, $pic);
        unlink($this->tempFileFolder().$pic->name);
            
        if (!empty($pic->entity_id)) {
            return redirect('admin/upload_file/entity/'.$pic->entity_id.'');
        } else {
            return redirect('admin/upload_file/donor/'.$pic->donor_id.'');
        }
    }
        
    public function makeProfile($id)
    {
        $up = new Upload;
        return $up->makeProfile($id);
    }

    public function makePlaceholder($id)
    {
        $pic = Upload::find($id);

        $entity = Entity::find($pic->entity_id);

        $program= Program::find($entity->program_id);

        $settings= Setting::find($program->setting_id);

        $program_settings = (array) json_decode($settings->program_settings);

        $placeholder = $pic->makeAWSlinkThumb($pic);

        $program_settings['placeholder']=$placeholder;

        $settings->program_settings = json_encode($program_settings);
        $settings->save();
            
        return redirect('admin/upload_file/entity/'.$pic->entity_id.'');
    }
        
    public function editFile($id)
    {
        $file = Upload::find($id);
            
        return view('admin.views.editFile')->with('file', $file);
    }
        
    public function postEditFile($id)
    {
        $data = Input::all();
            
        $file = Upload::find($id);
            
        if (Input::hasFile('file')) {
            $rules = [
                'file' => 'mimes:pdf,doc,docx,xls,txt,zip,csv,jpg,jpeg,gif,png|max:3000',
            ];
             
            $validation = Validator::make($data, $rules);
             
            if ($validation->fails()) {
                return response($validation->errors->first(), 400);
            }
                
            // handle the replacement file
            $newfile = Input::file('file');
            $extension = $newfile->getClientOriginalExtension();
            $name = $newfile->getClientOriginalName();
            $filename = sha1(time().time()).".{$extension}";
            $sourceFile = Input::file('file')->getRealPath();
                
            $ext = strtolower($extension);
            if ($ext == 'png' or $ext == 'gif' or $ext == 'jpg' or $ext == 'jpeg') {
                $filetype = 'image';
                    
            // resize image to 500px wide by proportional height, quality 70
                $img = Image::make($sourceFile)->resize(500, null, function ($constraint) {
                    $constraint->aspectRatio();
                })->save($this->tempFileFolder().$filename, 70);
                $sourceFile = $this->tempFileFolder().$filename;
            } else {
                $filetype = 'doc';
            }
                
            try {
                $s3 = AWS::get('s3');
                $s3->putObject([
                'Bucket'     => 'hys',
                'Key'        => $filename,
                'SourceFile' => $sourceFile,
                'ACL'    => 'public-read-write',
                ]);
            } catch (S3Exception $e) {
                echo "There was an error uploading the file.\n";
            }
                
            if ($filetype == 'image') {
                $this->makeThumbnail($filename, $file);
                unlink($this->tempFileFolder().$filename);
            }
                
            // delete the current file at AWS
            $result = $s3->deleteObjects([
                'Bucket' => 'hys',
                'Objects' => [
                    [
                        'Key' => $file->name,
                    ],
                ],
            ]);
                
            $file->name = $filename;
        }
            
            
        $file->file_name = $data['file_name'];
        $file->type = $data['type'];
        $file->permissions = $data['permissions'];
        $file->save();
            
        if (!empty($file->entity_id)) {
            $type = 'entity';
        } else {
            $type = 'donor';
        }
            
        return redirect('admin/upload_file/'.$type.'/'.$file->entity_id);
    }
        
    public function deleteFile($id)
    {
        $file = Upload::find($id);

        if (!count($file)) {
            return redirect('admin')
                ->with('message', 'File to be deleted could not be found. It has already been deleted.')
                ->with('alert', 'info');
        }

        if (!empty($file->entity_id)) {
            $type = 'entity';
            $entity_id = $file->entity_id;
        } else {
            $type = 'donor';
            $entity_id = $file->donor_id;
        }
        $file = Upload::where('id', $id)->delete();
            
        return redirect('admin/upload_file/'.$type.'/'.$entity_id);
    }

    public function postRecordUpload()
    {
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
                

            $up->client_id=Session::get('client_id');
            $up->file_name=$data['name'];
                
            if ($data['box_url']=='') { //Only post S3 filename to db if S3 js section is posting.
                $up->name=$data['hys_slug'].'.'.$data['hys_ext'];
            }
                
            $otherFiles=1;
            if ($data['hys_type']=='donor') {
                $up->donor_id= $data['hys_id'];
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

            if ($up->useBox()) {
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
                $this->makeThumbnail($up->name, $up);
            }

            //If this is the first upload and it's an image, make it the profile pic
            if ($up->type=='image'&&$otherFiles==0) {
                $this->makeProfile($up->id);
            }
        }

        return 'successsfully posted to box';
    }



    public function boxResponse()
    {
        $data= Input::all();
        $client_id= Session::get('client_id');
        $client= Client::find($client_id);

        $client_id= $client->box_client_id;
        $client_secret= $client->box_client_secret;

        $url= 'https://www.box.com/api/oauth2/token';

        $ch= curl_init();

        $data= [
            'grant_type'    =>'authorization_code',
            'code'          => $data['code'],
            'client_id'     => $client_id,
            'client_secret' => $client_secret
            ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $r = curl_exec($ch);

        curl_close($ch);

        $result=json_decode($r);
            
        if (isset($result->access_token)) {
            $client->box_access_token=$result->access_token;
            $client->box_refresh_token=$result->refresh_token;
            $client->save();

            return redirect('admin');
        }
    }

 //    public	function shareBoxFile($box_id) {

 //    	$client= Client::find(Session::get('client_id'));

    //    	$ch= curl_init();

    //    	$url='https://api.box.com/2.0/files/'.$box_id;

    //    	$data=array(
    //    		"shared_link" => array(
    //    			"access" => "open"));

    //    	//$data='{"shared_link": {"access": "open"}}';

    // 	curl_setopt($ch, CURLOPT_URL,$url);
    // 	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
 //    	'Authorization: Bearer '.$client->box_access_token));
 //    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    // 	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
 //    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);

 //    	$result= curl_exec($ch);

 //    	curl_close($ch);

 //    	//var_dump($result);
    // }
        
    private function isUrl($url)
    {
          return filter_var($url, FILTER_VALIDATE_URL);//if it's valid it return TRUE else FALSE
    }

    public function cullOrphanFiles()
    {
        $results= [];
        $s3 = AWS::get('s3');

        //Remove all deleted files!
        foreach (Uploads::onlyTrashed()->all() as $upload) {
            $img = explode('.', $upload->name);

            if (count($img)>1) {
                $thumb = $img[0].'_t_.'.$img[1];
                $results[] = $s3->deleteObjects([
                    'Bucket' => 'hys',
                    'Objects' => [
                        ['Key' => $thumb],
                        ['Key' => $upload->name]
                        ]]);

                $upload->forceDelete();
            }
        }

        //Remove files with missing entities and missing donors!
        foreach (Uploads::withTrashed()->all() as $upload) {
            $img = explode('.', $upload->name);
            if (count($img)>1) {
                $thumb = $img[0].'_t_.'.$img[1];
                if ($upload->entity_id!='') {
                    $entity=Entity::withTrashed()->find($upload->entity_id);
                    if ($entity==null) {
                        $results[] = $s3->deleteObjects([
                            'Bucket' => 'hys',
                            'Objects' => [
                                ['Key' => $thumb],
                                ['Key' => $upload->name]
                                ]]);
                                $upload->forceDelete();
                    }
                }
                if ($upload->donor_id!='') {
                    $donor=Donor::withTrashed()->find($upload->donor_id);
                    if ($donor==null) {
                        $results[] = $s3->deleteObjects([
                            'Bucket' => 'hys',
                            'Objects' => [
                                ['Key' => $thumb],
                                ['Key' => $upload->name]
                                ]]);
                                $upload->forceDelete();
                    }
                }
            }
        }
    }
}
