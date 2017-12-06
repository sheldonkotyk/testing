<?php

namespace App\Models;

use Aws\Laravel\AwsFacade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Intervention\Image\Facades\Image;

class Upload extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    private function tempFileFolder()
    {
            $public = public_path();
            return $path = "$public/temp/";
    }

    public function makeAWSlink($file)
    {
        if ($file==null) {
            return '';
        }
        $link = "https://s3-us-west-1.amazonaws.com/hys/{$file->name}";
        
        if (empty($file->name)) {
            return '';
        } else {
            return $link;
        }
    }

    // this function will make a thumbnail of any image handed to it and then upload it to AWS
    public function makeThumbnail($filename, $file = null)
    {
        $img = explode('.', $filename);


        $thumb_filename = $img[0].'_t_.'.$img[1];
        $url="https://s3-us-west-1.amazonaws.com/hys/";
        $data = file_get_contents($url.$filename);
        
        $img = Image::make($data)->fit(300, 300, null, 'top')->save($this->tempFileFolder().$thumb_filename, 90);
        
        try {
            $s3 = AWS::get('s3');
            $s3->putObject([
                'Bucket'     => 'hys',
                'Key'        => $thumb_filename,null,
                'SourceFile' => ''.$this->tempFileFolder().$thumb_filename.'',
                'ACL'    => 'public-read-write',
            ]);
            
            $result = "Success";
        } catch (S3Exception $e) {
            $result = "There was an error uploading the file.\n";
        }
        
        unlink($this->tempFileFolder().$thumb_filename);

        if ($file) {
            $file->thumbnail_exists = '1';
            $file->save();
        }

        
        
        return $result;
    }

    public function makeProfile($id)
    {
            $pic = Upload::find($id);
            $d = new Donor;
            $e = new Entity;

        if (!empty($pic->entity_id)) {
            $type = 'entity';
            $id = $pic->entity_id;
            $name= $d->getEntityName($id);
        } else {
            $type = 'donor';
            $id = $pic->donor_id;
            $name = $e->getDonorName($id);
        }

            // remove current profile designation
            $reset = Upload::where(''.$type.'_id', $id)->update(['profile' => 0]);

            $profileThumb = '';
        if (!empty($pic)) {
            $profileThumb = $pic->makeAWSlinkThumb($pic);
        }

        if ($pic->profile=='1') {
            Session::flash('message', 'Note: The Profile picture for '.$name['name'].' was unset.');
            Session::flash('alert', 'info');
        } else {
            Session::flash('message', 'Note: The Profile picture for '.$name['name'].'  was set to : <img src="'.$profileThumb.'" width="50px"> ');
            Session::flash('alert', 'info');
        }
            
            $pic->profile = 1;
            $pic->permissions = 'public';
            $pic->save();

        if (!empty($pic->entity_id)) {
            $the_e =  Entity::find($id);
            $the_e->reloadEntitiesToCache($the_e);
        }
            
            return redirect('admin/upload_file/'.$type.'/'.$id.'');
    }

    public function makeAWSlinkThumb($file)
    {

        //return var_dump($file);
        if ($file==null) {
            return '';
        }
        if (is_string($file)) {
            $thumb_name=explode('.', $file);

            $the_name ='';
            if (!empty($thumb_name[1])) {
                    $the_name=$thumb_name[0].'_t_.'.$thumb_name[1];
            } else {
                return $file;
            }

            return "https://s3-us-west-1.amazonaws.com/hys/{$the_name}";
        } else {
            if ($file->type=='image') {
                $thumb_name=explode('.', $file->name);

                if (!empty($thumb_name[1])) {
                    $the_name=$thumb_name[0].'_t_.'.$thumb_name[1];
                } else {
                    return $file->file_name;
                }

                if (!empty($file->thumbnail_exists)) {
                    $link = "https://s3-us-west-1.amazonaws.com/hys/{$the_name}";
                } else {
                    $link = '';
                }

                return $link;
            } else {
                return $file->file_name;
            }
        }
    }

    //This function checks the client info to see if Box is being used.
    public function useBox($client_id = null)
    {
        if ($client_id==null) {
            $client_id=Session::get('client_id');
        }

        $client=Client::find($client_id);

        if (!empty($client->box_client_id)&&!empty($client->box_client_secret)) {
            return true;
        } else {
            return false;
        }
    }

    //This function tries to grab the root folder view from box based on the access token.
    //If it can see the root folder, then the user is considered 'logged-in'
    public function isBoxLoggedIn($client_id = null)
    {
        if ($client_id==null) {
            $client_id=Session::get('client_id');
        }

        $client=Client::find($client_id);

        if (empty($client->box_access_token)) {
            return false;
        }

        $ch= curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://www.box.com/api/2.0/folders/0");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer '.$client->box_access_token]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result= curl_exec($ch);

        curl_close($ch);

        if (empty($result)) {
            if ($this->refreshBoxToken($client_id)) {
                return true;
            } else {
                return false;
            }
        } else {
            return $client->box_access_token;
        }
    }

    public function refreshBoxToken($client_id = null)
    {
        if ($client_id==null) {
            $client_id=Session::get('client_id');
        }

        $client_id= Session::get('client_id');

        if (empty($client_id)) {
            return false;
        }

        $client= Client::find($client_id);

        $client_id= $client->box_client_id;
        $client_secret= $client->box_client_secret;
        $client_refresh_token= $client->box_refresh_token;

        $url= 'https://www.box.com/api/oauth2/token';

        $ch= curl_init();


        $data= [
            'grant_type'    =>'refresh_token',
            'refresh_token' => $client_refresh_token,
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
            return true;
        } else {
            return false;
        }
    }

    //This function attempts to log the user in
    public function logBoxIn($client_id = null, $redirect_uri)
    {

        $redirect_uri=URL::to('admin/box_response');
        
        if ($client_id==null) {
            $client_id=Session::get('client_id');
        }

        if (empty($client_id)) {
            return "Unable to log into Box.com, Contact your administrator and ask them to upload a file from the administrator side to Box. This will rectify the problem.";
        }

        $client=Client::find($client_id);

        $box_authorize_uri= 'https://www.box.com/api/oauth2/authorize?response_type=code&client_id='.$client->box_client_id.'&state=security_token1&redirect_uri='.$redirect_uri;

        return Redirect::away($box_authorize_uri);
    }


    //This function uploads a file to BOX.com
    public function uploadToBox($client_id = null, $file, $filename)
    {
        
        if ($client_id==null) {
            $client_id=Session::get('client_id');
        }

        if (empty($client_id)) {
            return "Unable to log into Box.com, Contact your administrator and ask them to upload a file from the administrator side to Box. This will rectify the problem.";
        }

        $client=Client::find($client_id);

        $ch= curl_init();
        $url = "https://upload.box.com/api/2.0/files/content";

        $cfile=curl_file_create($file->getRealPath(), $file->getClientOriginalExtension(), $filename);

        $data= ['filename'=>$cfile,'folder_id'=>'1957712944'];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer '.$client->box_access_token]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        curl_close($ch);

        return json_decode($result);
    }

    public function createBoxFolder($program_id, $client_id)
    {
        $client=Client::find($client_id);

        $program = Program::find($program_id);

        $ch= curl_init();
        $url = "https://api.box.com/2.0/folders";

        $data= json_encode(['name'=>$program->name.' [id-'.$program->id.']','parent' => ['id' => '0']]);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer '.$client->box_access_token]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        curl_close($ch);

        $d_result= json_decode($result);

        if (isset($d_result->id)) {
            //var_dump($d_result);
            $program->box_folder_id=$d_result->id;
            $program->save();
            return $program->box_folder_id;
        } elseif ($d_result->type=='error'&&$d_result->code=='item_name_in_use') {
            $program->box_folder_id= $d_result->context_info->conflicts[0]->id;
            $program->save();

            $result=$this->retrieveBoxFolderId($program_id, $client_id);

            if ($result) {
                return true;
            } else {
                    Session::flash('message', Session::get('message').'<br/>Box.com Error: The folder "'.$program->name.' [id-'.$program->id.']" already exists in your Box.com account cannot be connected to this program.<br/> To fix this, login to your Box.com account and change the name of the folder.');
                    Session::flash('alert', 'danger');
                    $program->box_folder_id= '';
                    $program->save();
            }
                        
            return false;
        } else {
            //Uncaught Error.
            Session::flash('message', Session::get('message').'<br/>Box.com Error: '.var_dump($d_result));
            Session::flash('alert', 'danger');
            return false;
        }
    }


    public function retrieveBoxFolderId($program_id, $client_id)
    {
        $client=Client::find($client_id);

        $program = Program::find($program_id);

        $ch= curl_init();
        $url = "https://api.box.com/2.0/folders/".$program->box_folder_id;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer '.$client->box_access_token]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        curl_close($ch);

        $result= json_decode($result);

        if (isset($result->id)) {
            return $result->id;
        } elseif ($result->type=='error'&&$result->code=='item_name_in_use') {
            Session::flash('message', Session::get('message').'<br/>Box.com Error Retrieving folder: '.$result->message);
            Session::flash('alert', 'danger');
            return false;
        } elseif ($result->type=='error'&&$result->code=='trashed') {
            Session::flash('message', Session::get('message').'<br/>Box.com Error: '. $result->message .'.<br/> The folder "'.$program->name.' [id-'.$program->id.']" has been trashed. You must Login to Box.com and either<br/> 1. Restore the trashed folder <br/><strong>OR</strong><br/> 2. Remove the folder completely by deleting it from your Box.com trash.');
            Session::flash('alert', 'danger');
            return false;
        } elseif ($result->type=='error'&&$result->code=='not_found') {
            Session::flash('message', Session::get('message').'<br/>Note: The Box.com Folder "'.$program->name.' [id-'.$program->id.']" was not found.<br/> This folder has been automatically recreated in your Box account to store files for this program.');
            Session::flash('alert', 'info');

            $program->box_folder_id='';
            $program->save();

            $this->createBoxFolder($program_id, $client_id);

            return false;
        } else {
            //Uncaught Error.
            Session::flash('message', Session::get('message').'<br/>Box.com Error: '.var_dump($result));
            Session::flash('alert', 'danger');
            return false;
        }
    }

    public function createBoxDonorFolder($hysform_id, $client_id)
    {
        $client=Client::find($client_id);

        $hysform = Hysform::find($hysform_id);

        $ch= curl_init();
        $url = "https://api.box.com/2.0/folders";

        $data= json_encode(['name'=>$hysform->name.' [id-'.$hysform->id.']','parent' => ['id' => '0']]);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer '.$client->box_access_token]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        curl_close($ch);

        $d_result= json_decode($result);

        if (isset($d_result->id)) {
            $hysform->box_folder_id=$d_result->id;
            $hysform->save();
            return $hysform->box_folder_id;
        } elseif ($d_result->type=='error'&&$d_result->code=='item_name_in_use') {
            $hysform->box_folder_id= $d_result->context_info->conflicts[0]->id;
            $hysform->save();

            $result=$this->retrieveBoxFolderId($program_id, $client_id);

            if ($result) {
                return $result;
            } else {
                    Session::flash('message', Session::get('message').'<br/>Box.com Error: The folder "'.$hysform->name.' [id-'.$hysform->id.']" already exists in your Box.com account cannot be connected to this program.<br/> To fix this, login to your Box.com account and change the name of the folder.');
                    Session::flash('alert', 'danger');
                    $hysform->box_folder_id= '';
                    $hysform->save();
            }
                        
            return false;
        } else {
            //Uncaught Error.
            Session::flash('message', Session::get('message').'<br/>Box.com Error: '.var_dump($d_result));
            Session::flash('alert', 'danger');
            return false;
        }
    }

    public function retrieveBoxDonorFolderId($hysform_id, $client_id)
    {

        $client=Client::find($client_id);

        $hysform = Hysform::find($hysform_id);

        $ch= curl_init();
        $url = "https://api.box.com/2.0/folders/".$hysform->box_folder_id;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer '.$client->box_access_token]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        curl_close($ch);

        $result= json_decode($result);

        if (isset($result->id)) {
            return $result->id;
        } elseif (isset($result)&&$result->type=='error'&&$result->code=='item_name_in_use') {
            Session::flash('message', Session::get('message').'<br/>Box.com Error Retrieving folder: '.$result->message);
            Session::flash('alert', 'danger');
            return false;
        } elseif (isset($result)&&$result->type=='error'&&$result->code=='trashed') {
            Session::flash('message', Session::get('message').'<br/>Box.com Error: '. $result->message .'.<br/> The folder "'.$hysform->name.' [id-'.$hysform->id.']" has been trashed. You must Login to Box.com and either<br/> 1. Restore the trashed folder <br/><strong>OR</strong><br/> 2. Remove the folder completely by deleting it from your Box.com trash.');
            Session::flash('alert', 'danger');
            return false;
        } elseif (isset($result)&&$result->type=='error'&&$result->code=='not_found') {
            Session::flash('message', Session::get('message').'<br/>Note: The Box.com Folder "'.$hysform->name.' [id-'.$hysform->id.']" was not found.<br/> This folder has been automatically recreated in your Box account to store files for Donors.');
            Session::flash('alert', 'info');

            $hysform->box_folder_id='';
            $hysform->save();

            return $this->createBoxDonorFolder($hysform_id, $client_id);
        } else {
            //Uncaught Error.
            Session::flash('message', Session::get('message').'<br/>Box.com Error: '.var_dump($result));
            Session::flash('alert', 'danger');
            return false;
        }
    }

    public function getBoxFolderId($program_id, $client_id = null)
    {
        if ($client_id==null) {
            $client_id=Session::get('client_id');
        }

        $program = Program::find($program_id);

        if ($program->box_folder_id==0) {
            return $this->createBoxFolder($program_id, $client_id);
        } else {
            return $this->retrieveBoxFolderId($program_id, $client_id);
        }
        
        return false;
    }

    public function getBoxDonorFolderId($hysform_id, $client_id = null)
    {
        if ($client_id==null) {
            $client_id=Session::get('client_id');
        }

        $hysform = Hysform::find($hysform_id);

        if ($hysform->box_folder_id==0) {
            return $this->createBoxDonorFolder($hysform_id, $client_id);
        } else {
            return $this->retrieveBoxDonorFolderId($hysform_id, $client_id);
        }
        
        return false;
    }
}
