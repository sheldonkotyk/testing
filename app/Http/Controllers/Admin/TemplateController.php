<?php namespace App\Controllers\Admin;
 
    use Auth;
use BaseController;
use Form;
use Input;
use Redirect;
use Sentry;
use View;
use Setting;
use Session;
use Validator;
use Template;
use Upload;
use AWS;
 
use App\Http\Controllers\Controller;

class TemplateController extends Controller
{
    
    public function template()
    {
        $client_id = Session::get('client_id');
        $template = Template::whereClientId($client_id)->first();
        $pics = Upload::whereClientId($client_id)->where('type', 'template')->get();
            
        return view('admin.views.editTemplate')
            ->with('template', $template)
            ->with('pics', $pics);
    }
        
    public function postTemplate()
    {
        $data = Input::all();
        $client_id = Session::get('client_id');
        $template = Template::whereClientId($client_id)->first();
        if (!count($template)) {
            $template = new Template;
            $template->client_id = $client_id;
        }
        $template->css = $data['css'];
        $template->js = $data['js'];
        $template->html = $data['html'];
        $template->save();
            
        return redirect('admin/template');
    }
        
    public function postUploadPicToTemplate()
    {
        $input = Input::all();
            
        $rules = [
            'file' => 'mimes:jpg,jpeg,gif,png|max:3000',
        ];
         
        $validation = Validator::make($input, $rules);
         
        if ($validation->fails()) {
            return redirect('admin/template')
                ->withErrors($validator)
                ->withInput();
        }
            
        $file = Input::file('file');
        $extension = $file->getClientOriginalExtension();
        $name = $file->getClientOriginalName();
        $thesha = sha1(time().time());
        $filename = $thesha.'.'.$extension;

        $sourceFile = Input::file('file')->getRealPath();
            
        $ext = strtolower($extension);
            
        try {
            $s3 = AWS::get('s3');
            $s3->putObject([
                'Bucket'     => 'hys',
                'Key'        => $filename,
                'SourceFile' => $sourceFile,
                'ACL'    => 'public-read-write',
            ]);
        } catch (S3Exception $e) {
            return redirect('admin/template')
                ->with('alert', 'danger')
                ->with('message', "There was an error uploading the file.\n");
        }

        $upload = new Upload;
        $upload->client_id = Session::get('client_id');
        $upload->name = $filename;
        $upload->file_name = $name;
        $upload->type = 'template';
        $upload->permissions = 'public';
        $upload->save();
            
        return redirect('admin/template')
            ->with('alert', 'success')
            ->with('message', "File uploaded successfully.");
    }
        
    public function deleteTemplatePic($id)
    {
        $file = Upload::find($id);
            
        $s3 = AWS::get('s3');
        $result = $s3->deleteObjects([
            'Bucket' => 'hys',
            'Objects' => [
                [
                    'Key' => $file->name,
                ],
            ],
        ]);
            
        $file->forceDelete();
            
        return redirect('admin/template');
    }
}
