<?php namespace App\Controllers\Admin;
 
    use Auth;
use BaseController;
use Form;
use Input;
use Redirect;
use Sentry;
use View;
use Session;
use Validator;
use Emailset;
use Emailtemplate;
use Donor;
use Program;
use RedisL4;
use Entity;
use Field;
use Donorfield;
 
use App\Http\Controllers\Controller;

class EmailController extends Controller
{
        
    public function emailManage()
    {
        $emailsets = Emailset::whereClientId(Session::get('client_id'))->get();

        $t = new Emailtemplate;
        $template_errors = $t->templateErrors($emailsets);
            
        $t_array = $t->getTemplateArray();

        return view('admin.views.manageEmail')->with(['emailsets'=> $emailsets,'template_errors' => $template_errors,'t_array'=>$t_array]);
    }
        
    public function addEmailset()
    {
        return view('admin.views.addEmailset');
    }
        
    public function postAddEmailset()
    {
        $data = Input::all();
            
        $rules = [
            'name' => 'required',
            'from' => 'required|email',
        ];
            
        $validator = Validator::make($data, $rules);
        
        if ($validator->passes()) {
            $emailset = new Emailset;
            $emailset->client_id = Session::get('client_id');
            $emailset->name = $data['name'];
            $emailset->from = $data['from'];
            $emailset->save();
                
            Session::flash('message', 'Email Set Saved.');
            Session::flash('alert', 'success');
            return redirect('admin/email');
        }
            
        Session::flash('message', 'There was a problem with your submission. Please see below for details');
        Session::flash('alert', 'danger');
        return redirect('admin/add_emailset')
            ->withErrors($validator)
            ->withInput();
    }
        
    public function editEmailset($emailset_id)
    {


        $emailset = Emailset::where('client_id', Session::get('client_id'))->find($emailset_id);
        if (count($emailset)==0) {
            return "Error: Emailset could not be found.";
        }

        return view('admin.views.editEmailset')->with('emailset', $emailset);
    }
        
    public function postEditEmailset($emailset_id)
    {
        $data = Input::all();

        $emailset = Emailset::where('client_id', Session::get('client_id'))->find($emailset_id);
        if (count($emailset)==0) {
            return "Error: Emailset could not be found.";
        }
            
        $rules = [
            'name' => 'required',
            'from' => 'required|email',
        ];
            
        $validator = Validator::make($data, $rules);
        
        if ($validator->passes()) {
            $emailset = Emailset::find($emailset_id);
            $emailset->name = $data['name'];
            $emailset->from = $data['from'];
            $emailset->save();
                
            Session::flash('message', 'Email Set Saved.');
            Session::flash('alert', 'success');
            return redirect('admin/email');
        }
            
        Session::flash('message', 'There was a problem with your submission. Please see below for details');
        Session::flash('alert', 'danger');
        return redirect('admin/edit_emailset/'.$emailset_id.'')
            ->withErrors($validator)
            ->withInput();
    }
        
    public function removeEmailset($emailset_id)
    {

        $emailset = Emailset::where('client_id', Session::get('client_id'))->find($emailset_id);
        if (count($emailset)==0) {
            return "Error: Emailset could not be found.";
        }

        $emailset = Emailset::find($emailset_id);
        $emailset->delete();
            
        $emailtemplates = Emailtemplate::whereEmailsetId($emailset_id)->get();
        foreach ($emailtemplates as $et) {
            $et->delete();
        }
            
        return redirect('admin/email')
            ->with('message', 'Auto Email Set removed successfully')
            ->with('alert', 'success');
    }
        
    public function editEmailtemplate($emailset_id, $trigger)
    {
        $emailtemplate = Emailtemplate::whereClientId(Session::get('client_id'))->whereEmailsetId($emailset_id)->whereTrigger($trigger)->first();
        $emailset = Emailset::where('client_id', Session::get('client_id'))->find($emailset_id);
        if (count($emailset)==0) {
            return "Error: Emailset could not be found.";
        }
            
        $e =new Emailtemplate;
        $emailsets = Emailset::where('id', $emailset_id)->get();
        $template_errors = $e->templateErrors($emailsets);

        if (!$emailtemplate) {
            $emailtemplate = [];
        }
            
        $program = Program::whereEmailsetId($emailset_id)->first();
        $hysform = [];
        $donor_hysform = [];
        if (count($program)) {
            if ($program->hysform_id != 0) {
                if ($trigger!='pay_receipt'&&$trigger!='notify_donor'&&$trigger!='pay_remind'&&$trigger!='pay_fail'&&$trigger!='pay_fail_admin') {
                    $hysform = Field::whereHysformId($program->hysform_id)->where('permissions', 'public')->get();
                }
            }
            if ($program->donor_hysform_id != 0) {
                $donor_hysform = Donorfield::whereHysformId($program->donor_hysform_id)->where('permissions', 'public')->get();
            }
        }
            
        $t_array = $e->getTemplateArray();

        return view('admin.views.editEmailtemplate', [
            'emailtemplate' => $emailtemplate,
            'emailset' => $emailset,
            'to' => $t_array[$trigger]['to'],
            'title' => $t_array[$trigger]['title'],
            'shortcodes' => $t_array[$trigger]['shortcodes'],
            'hysform' => $hysform,
            'donor_hysform' => $donor_hysform,
            'trigger'   => $trigger,
            'template_errors' => $template_errors,
        ]);
    }
        
    public function postEditEmailtemplate($emailset_id, $trigger)
    {
        $data = Input::all();

        if (empty($data['subject'])) {
            $data['subject']= '';
        }
        if (empty($data['message'])) {
            $data['message']= '';
        }

        if (!empty($data['id'])) {
            $emailtemplate = Emailtemplate::find($data['id']);
        } else {
            $emailtemplate = new Emailtemplate;
            $emailtemplate->client_id = Session::get('client_id');
            $emailtemplate->emailset_id = $emailset_id;
            $emailtemplate->trigger = $trigger;
        }
            
        if (!empty($data['to'])) {
            $emailtemplate->to = $data['to'];
        }
        //If the client disables the email template
        if (!empty($data['disabled'])) {
            $emailtemplate->disabled = 1;
        } //Otherwise the email template is enabled
        else {
            $emailtemplate->disabled = 0;
        }
            

        $emailtemplate->subject = $data['subject'];
        $emailtemplate->message = $data['message'];
        $emailtemplate->save();
            
        Session::flash('message', 'Auto Email Saved.');
        Session::flash('alert', 'success');
        return redirect('admin/email');
    }
        
    public function removeEmailtemplate($template_id)
    {
    }
        
    public function optOut($who, $id)
    {
        // right now we only opt out donors so $who isn't used
        $donor = Donor::find($id);
        $donor->do_not_email = 1;
        $donor->save();
            
        return view('frontend.views.optOut')->with('email', $donor->email);
    }
        
    public function sendEmail($program_id, $trigger, $type = 'donor', $donor_id = null, $entity_id = null)
    {
        $e = new Emailtemplate;
            
        $program = Program::find($program_id);
        $details['entity'] = $e->getEntity($entity_id);
        $details['donor'] = $e->getDonor($donor_id);
        $redis = RedisL4::connection();
        $entity = new Entity;
        $donor = $entity->getDonorName($donor_id);
        $to = ['type' => 'donor', 'name' => $donor['name'], 'email' => $donor['email'], 'id' => $donor_id];
        $email = $e->sendEmail($program->emailset_id, $details, $trigger, $to);
                        
        if (Session::get('redirect')) {
            $redirect = Session::get('redirect');
            Session::forget('redirect');
            return redirect($redirect)->with('message', 'Update Sent')->with('alert', 'success');
        } else {
            return redirect('admin');
        }
    }

    public function autoEmailS3Upload()
    {
        $S3_KEY = '';
        $S3_SECRET = '';
        $S3_BUCKET = '';

        $S3_URL = 'http://s3.amazonaws.com/';

        // expiration date of query
        $tempFile = $_FILES['file']['tmp_name'];
        $filename = $_FILES['file']['name'];

        $s3 = new A2S3();
        $s3->putObject([
        'Bucket' => $S3_BUCKET,
        'Key'    => $filename,
        'Body'   => fopen($tempFile, 'r+'),
        'ACL'    => 'public-read',
        ]);

        $array = [
        'filelink' => 'http://'.$S3_BUCKET.$filename
        ];

        echo stripslashes(json_encode($array));
    }
}
