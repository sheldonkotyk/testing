<?php  namespace App\Controllers\Admin;
 
    use Auth;
use BaseController;
use Form;
use Input;
use Redirect;
use Sentry;
use View;
use RedisL4;
use MailchimpWrapper;
use Validator;
use Session;
use Cache;
use Donor ;
use URL;
use DB;
use Hysform;
use Field;
use Donorfield;
use Group;
use Donation;
use Emailsetting;
use Config;
use Emailset;
    
use App\Http\Controllers\Controller;

class HysformController extends Controller
{
    
    public function createForm($default_type = 'entity')
    {
        return view('admin.views.createForm')->with(['default_type'=>$default_type]);
    }
        
    public function postCreateForm()
    {
        $data = Input::all();
            
        $rules = [
            'name' => 'required|min:5',
            'type' => 'required'
            ];
            
        $validator = Validator::make($data, $rules);
            
        if ($validator->passes()) {
            $hysform = new Hysform;
            $hysform->client_id = Session::get('client_id');
            $hysform->name = $data['name'];
            $hysform->type = $data['type'];
/*
            if($hysform->type=='donor')
                $hysform->prefix= $data['prefix'];
*/
            $hysform->save();
                
            return redirect('admin/add_form_field/'.$hysform->id.'/'.$hysform->type.'');
        }
            
        return view('admin.views.createForm')
            ->withErrors($validator)
            ->withInput();
    }
        
    public function manageForm($hysform_id)
    {

        $hysform = Hysform::where('client_id', Session::get('client_id'))->find($hysform_id);
        if (count($hysform)==0) {
            return "Error: Donor Form Not Found.";
        }

        $fields = [];
        if ($hysform->type == 'donor') {
            $fields = Donorfield::where('hysform_id', $hysform_id)->orderBy('field_order')->get();
        } else {
            $fields = Field::where('hysform_id', $hysform_id)->orderBy('field_order')->get();
        }

        $type_name=$hysform->getFormType($hysform_id);

        $emailsettings=Emailsetting::where('client_id', $hysform->client_id)->first();
        $d = new Donor;
        $mailchimp_list_name = $d->getMailchimpListName($emailsettings, $hysform);
    //		print_r($fields->toArray());

        $donation= new Donation;
        $gateway= $donation->checkUseCC(Session::get('client_id'));
            
        return view('admin.views.manageForm', [
            'fields' => $fields,
            'hysform' => $hysform,
            'type_name' => $type_name,
            'gateway'   => $gateway,
            'mailchimp_list_name' => $mailchimp_list_name


        ]);
    }
        
    public function forms()
    {
        $hysforms = Hysform::where('client_id', Session::get('client_id'))->get();

        return view('admin.views.forms')->with('hysforms', $hysforms);
    }

    public function entity_forms()
    {
        $hysforms = Hysform::where('client_id', Session::get('client_id'))->get();
            
        return view('admin.views.forms')->with('hysforms', $hysforms);
    }

    public function donor_forms()
    {
        $hysforms = Hysform::where('client_id', Session::get('client_id'))->get();
            
        return view('admin.views.forms')->with('hysforms', $hysforms);
    }

        
    public function removeForm($hysform_id)
    {
        $hysform = Hysform::find($hysform_id);
        $hysform->delete();
            
        return redirect('admin/forms');
    }
        
    public function editForm($hysform_id)
    {

        $emailsettings=[];
            
        $hysform = Hysform::where('client_id', Session::get('client_id'))->find($hysform_id);
        if (count($hysform)==0) {
            return "Error: Donor Form Not Found.";
        }
            
        $notify = json_decode($hysform->notify);
        $mailchimp_list_name = false;

        $type_name=$hysform->getFormType($hysform_id);

        $lists = [''=>'None'];

        if ($hysform->type=='donor') {
            $emailsettings = Emailsetting::whereClientId(Session::get('client_id'))->first();
            $d = new Donor;
            $mailchimp_lists= $d->getMailchimpListName($emailsettings);

            $mailchimp_list_name = $d->getMailchimpListName($emailsettings, $hysform);

            if (!empty($mailchimp_lists)) {
                $lists = array_merge([''=>'None'], $mailchimp_lists);
            }
        }

        return view('admin.views.editForm', [
            'hysform' => $hysform,
            'notify' => $notify,
            'type_name' =>$type_name,
            'lists' => $lists,
            'mailchimp_list_name' => $mailchimp_list_name,
            'emailsettings' => $emailsettings
        ]);
    }
        
    public function postEditForm($hysform_id)
    {
        $data = Input::all();
        $rules = [
            'name' => 'required|min:5',
            ];
        $message = '';
        $alert = '';
            
        $validator = Validator::make($data, $rules);
            
        if ($validator->passes()) {
            $hysform = Hysform::find($hysform_id);
            $hysform->name = $data['name'];
                
            if ($hysform->type=='donor') {
                if (isset($data['can_donor_modify_amount'])) {
                    $hysform->can_donor_modify_amount=$data['can_donor_modify_amount'];
                } else {
                    $hysform->can_donor_modify_amount= '';
                }

                if (isset($data['forgive_missed_payments'])) {
                    $hysform->forgive_missed_payments=1;
                } else {
                    $hysform->forgive_missed_payments=0;
                }

                $hysform->hide_payment = isset($data['hide_payment']) ? 'hidden': '';

                if (!empty($data['mailchimp_list_id'])) {
                    if ($hysform->mailchimp_list_id!= $data['mailchimp_list_id']) {
                        $hysform->mailchimp_list_id= $data['mailchimp_list_id'];

                        //Sync all of Donors table to Mailchimp

                        $donors= Donor::where('hysform_id', $hysform_id)->get();
                        $hysform->save();
                        $d= new Donor;
                        $result = $d->syncDonorsToMailchimp($donors);

                        $message = '<strong>'.$hysform->name.'</strong>' . ' is now connected with your mailchimp list <strong>'.$result['name'].'</strong><br>'
                        .$result['add_count']. ' Donors added to <strong>'.$result['name'].'</strong><br>'
                        .'Any Donors added in the future to <strong>'.$hysform->name.'</strong> will be automatically synced to <strong>'.$result['name'].'</strong><br>';

                        $alert = 'success';
                        if ($result['error_count']>0) {
                            Cache::forget('showalldonors-'.$hysform_id);

                            $hysform->save();

                            $errors = '<table><thead><tr><th>Error Code</th><th>Email</th><th>Error Message</th></tr></thead><tbody>';
                            foreach ($result['errors'] as $error) {
                                $errors.='<tr>';
                                    
                                $errors.='<td>';
                                $errors.=$error['code'];
                                $errors.='</td>';

                                $errors.='<td>';
                                $errors.=$error['email']['email'];
                                $errors.='</td>';

                                $errors.='<td>';
                                $errors.=$error['error'];
                                $errors.='</td>';
                                    
                                $errors.='</tr>';
                            }
                            $errors = $errors.='</tbody></table>';

                            Session::set('error', $result['error_count']. ' Errors Total upon syncing <strong>'.$hysform->name.'</strong> to <strong>'.$result['name'].'</strong><br> '. $errors);
                            Session::set('error-alert', 'warning');

                            return redirect('admin/edit_form/' . $hysform_id)
                            ->with('message', 'Mailchimp list <strong>'.$result['name'].'</strong> is now synced with <strong>'.$hysform->name.'</strong>. <br>'
                                .$result['add_count']. ' Donors added to <strong>'.$result['name'].'</strong><br>'
                                .'Note: Any Donors added in the future to <strong>'.$hysform->name.'</strong> will be automatically synced to <strong>'.$result['name'].'</strong><br>'
                                .$result['error_count']. ' Errors total upon syncing. <a href="'.URL::to('admin/error').'">Click here to view errors.</a>')
                            ->with('alert', 'warning');
                        }
                    }
                } else {
                    if (!empty($hysform->mailchimp_list_id)) {
                        $d = new Donor;
                        $emailsettings= Emailsetting::where('client_id', $hysform->client_id)->first();
                        $list_name = $d->getMailchimpListName($emailsettings, $hysform);
                        if ($list_name==false) {
                            $list_name = '';
                        }

                        $message= $message. 'Mailchimp list <strong>'.$list_name.'</strong> was successfully disconnected from <strong> '.$hysform->name.'</strong><br>'
                        .'Note: This merely disconnects the syncing feature, any previously synced emails will remain in your Mailchimp list';
                        $alert = "success";
                    }
                    $hysform->mailchimp_list_id= $data['mailchimp_list_id'];
                }

                $hysform->prefix= $data['prefix'];
            }

            Cache::forget('showalldonors-'.$hysform_id);

            $hysform->save();
                
            return redirect('admin/edit_form/'.$hysform_id)
            ->with('message', $message)
            ->with('alert', $alert);
        }
            
        return redirect('admin/edit_form/' . $hysform_id)
            ->withErrors($validator)
            ->withInput()
            ->with('message', 'There was an error with your submission. See below for details.')
            ->with('alert', 'danger');
    }

    public function changeDefaultEmailset($hysform_id, $emailset_id)
    {
        $emailset = Emailset::find($emailset_id);
        $hysform = Hysform::find($hysform_id);

        if (count($emailset)&&count($hysform)) {
            //Forget the donors menu counts because this function changes the menu.
            $key = 'showalldonors-'.$hysform_id;
            Cache::forget($key);

            $hysform->default_emailset_id= $emailset_id;
            $hysform->save();
            return Redirect::back()->
            with('alert', 'info')->
            with('message', '<strong>'.$emailset->name.'</strong> is now the default Emailset for <strong>'.$hysform->name.'</strong> <br>
					Note: This only applies to the Notify Donor of Setup Account and Year End Statement emails found on this page.');
        }

        return Redirect::back()->
            with('alert', 'danger')->
            with('message', 'Error: The Default Emailset could not be changed.');
    }
}
