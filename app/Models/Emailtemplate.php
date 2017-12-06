<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;

class Emailtemplate extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    
    
    /**
     * returen the donor profile for parsing.
     *
     * @access public
     * @param mixed $donor_id
     * @return void
     */
    public function getDonor($donor_id)
    {
        $redis = RedisL4::connection();
        $donor = Donor::find($donor_id);
        $donor_profile = [];

        if (count($donor)) {
            $donor_profile = json_decode($donor->json_fields, true);
        }

        return $donor_profile;
    }
    
    
    /**
     * returns the profile of the entity for parsing.
     *
     * @access public
     * @param mixed $entity_id
     * @return void
     */
    public function getEntity($entity_id)
    {
        $redis = RedisL4::connection();
        $entity_profile = [];

        $entity = Entity::find($entity_id);

        if (count($entity)) {
            $entity_profile= json_decode($entity->json_fields, true);
        }

        
        return $entity_profile;
    }
        
    
    /**
     * Check for do not email list happens before sending to this function.
     *  $details is an array of arrays of all info that will be sent through parseShortCodes
     *  $trigger tells what template to use
     *  $to is an array of type (donor or admin), name, email address, id (if donor)
     *
     * @access public
     * @param array $details
     * @param mixed $trigger
     * @param array $to
     * @return void
     */
    public function sendEmail($emailset_id, array $details, $trigger, array $to, $debugging_client_id = null)
    {
        
        $data = [];
        
        $data['emailset'] = Emailset::find($emailset_id);
        $data['emailtemplate'] = Emailtemplate::whereEmailsetId($emailset_id)->where('trigger', $trigger)->first();

        
 
        //If (there is an email template) and ( the email address exists and is set) and (the emailtemplate is not disabled!)
        if (count($data['emailtemplate']) && $data['emailtemplate']->disabled == 0) {
            //This will display an error in the log if the email gets sent via the wrong client's emailset.

            if ($debugging_client_id!=null&&$data['emailtemplate']->client_id!=$debugging_client_id) {
                Log::warning('Mail Error! : The client that initiated the email ('.$debugging_client_id.') is utilizing the emailtemplate of a different client. Emailset_id: '.$emailset_id.' belongs to client_id: '.$data['emailtemplate']->client_id.' This happened because the wrong emailset_id was submitted to the Emailtemplate->sendEmail function.'. var_export(['details'=>$details,'trigger'=>$trigger,'to'=>$to]));
            }
            
            if ($to['type'] != 'admin' && (!isset($to['email']) || empty($to['email']) )) {
                Session::flash('message', Session::get('message').'<br/>'.'Error: Email not found. <a href="'.URL::to('admin/manage_program').'">Add Text to it here.</a>.');
                Session::flash('alert', 'danger');
                return false;
            }
            
            $data['client_id'] = $data['emailtemplate']->client_id;
            //die( var_dump($data['client_id']));
            //$data['emailsetting'] = Emailsetting::where('client_id', $data['emailtemplate']->client_id)->first();
            
            //die( var_dump( Log::error($data['emailsetting'])) );
            
            $data['data']['subject'] = $data['emailtemplate']->subject;
            $data['data']['body'] = $data['emailtemplate']->message;
            $data['data']['to_name'] = $to['name'];
            $data['data']['to_email'] = $to['email'];
            $data['data']['to'] = $to['type']; // this is used in the template for the opt out
            
            if (!empty($data['data']['body'])) {
                foreach ($details as $detail) {
                    $data['data']['subject'] = $this->parseShortCodes($detail, $data['data']['subject']);
                    $data['data']['body'] = $this->parseShortCodes($detail, $data['data']['body']);
                }
                
                if ($to['type'] == 'donor') {
                    $data['data']['id'] = $to['id'];
                    $data['template'] = 'emails.donorEmailTemplate';
                }
                
                if ($to['type'] == 'admin') {
                    $emails = Emailtemplate::whereEmailsetId($emailset_id)->where('trigger', $trigger)->pluck('to');
    
                    if ($emails == '') {
                        return false;
                    }
                    
                    $data['data']['to_email'] = explode(',', $emails);
                    $data['template'] = 'emails.adminEmailTemplate';
                }
            }
            
            Queue::push('sendEmailQueue', $data);
            
            // Log::info('email pushed to queue');

            // Session::flash('message', 'The message has been queued. ');
            // Session::flash('alert', 'success');
        } else {
            Session::flash('message', Session::get('message').'<br/>'.'Error: Email Template not set up.');
            Session::flash('alert', 'danger');
            return false;
        }
        
        return true;
    }
    
    
    /**
     * parse the shortcodes for emails. uses the array key as the short code.
     *
     * @access public
     * @param mixed $data
     * @param mixed $text
     * @return void
     */
    public function parseShortcodes($data, $text)
    {
        if (!empty($data)) {
            // Parse the short codes
            foreach ($data as $k => $v) {
                $text = str_replace('['.$k.']', $v, $text);
            }
        }
        return $text;
    }

    public function templateErrors($emailsets)
    {

        $templates_array = $this->getTemplateArray();

        foreach ($emailsets as $s) {
            foreach (Emailtemplate::where('emailset_id', $s->id)->get() as $t) {
                if (empty($t->subject)) {
                    $template_errors[$s->id][$t->trigger][]='<span class="label label-danger">No Subject</span>';
                }
                if (empty($t->message)) {
                    $template_errors[$s->id][$t->trigger][]='<span class="label label-danger">No Email Message Body</span>';
                }

                if ($templates_array[$t->trigger]['to']==true) {
                    if (empty($t->to)) {
                        $template_errors[$s->id][$t->trigger][]='<span class="label label-danger">No "To Email Address" </span>';
                    }
                }

                foreach ($templates_array[$t->trigger]['required'] as $required) {
                    if (strpos($t->message, '['.$required.']') == false) {
                        $template_errors[$s->id][$t->trigger][]='<span class="label label-danger"> ['.$required.'] shortcode is missing </span>';
                    }
                }
                

                if (empty($template_errors[$s->id][$t->trigger])) {
                    $template_errors[$s->id][$t->trigger][]='<span class="glyphicon glyphicon-ok text-success"></span>';
                } else {
                    $an_s = 's';
                    $count= count($template_errors[$s->id][$t->trigger]);
                    if ($count==1) {
                        $an_s = '';
                    }

                    array_unshift($template_errors[$s->id][$t->trigger], '</span> <span class="label label-warning">'.$count.' Warning'.$an_s.'</span>');
                }

                if ($t->disabled==1) {
                        //If the email is disabled, don't dispay errors.
                        $template_errors[$s->id][$t->trigger]=[];
                        $template_errors[$s->id][$t->trigger][]='<span class="label label-info">Email Disabled </span>';
                }
            }

            foreach ($templates_array as $k => $template_name) {
                if (!isset($template_errors[$s->id][$k])) {
                    $template_errors[$s->id][$k][] ='</span> <span class="label label-danger"> Email Blank</span>';
                }
            }
        }

        return $template_errors;
    }

//This is now where the definitions of the emailTemplates live.
    public function getTemplateArray()
    {
        return $templates =  [
            'new_donor'=> [
                    'title'=> "New Donor Signup",
                    'to'=>false,
                    'shortcodes'=>['username','date'],
                    'required'=>[]],
            'new_donor_admin'=>[
                    'title'=> 'Notify Admin of New Sponsorship',
                    'to'=>true,
                    'shortcodes'=>['designations', 'total_amount', 'method', 'frequency', 'date', 'donor_email'],
                    'required'=>['designations', 'total_amount']],
            'notify_donor'=>[
                    'title'=> 'Notify Donor of Account Setup',
                    'to'=>false,
                    'shortcodes'=>['username','email', 'password'],
                    'required'=>['username','password']],
            'sp_email'=>[
                    'title'=> 'Notify Admin of Message from Sponsor',
                    'to'=>true,
                    'shortcodes'=>['subject','body'],
                    'required'=>['subject','body']],
            'recip_email'=>[
                    'title'=> 'Notify Sponsor of Message from Recipient',
                    'to'=>false,
                    'shortcodes'=>['subject','login_link','url'],
                    'required'=>['subject','login_link']],
            'profile_update'=>[
                    'title'=> 'Notify Sponsor of Profile Update',
                    'to'=>false,
                    'shortcodes'=>[],
                    'required'=>[]],
            'pay_remind'=>[
                    'title'=> 'Donor Payment Reminder',
                    'to'=>false,
                    'shortcodes'=>['designation_name', 'amount','method','frequency'],
                    'required'=>['designation_name', 'amount','method','frequency']],
            'pay_receipt'=>[
                    'title'=> 'Donor Payment Receipt',
                    'to'=>false,
                    'shortcodes'=>['designations', 'total_amount', 'method', 'date'],
                    'required'=>['designations', 'total_amount','method', 'date']],
            'pay_fail'=>[
                    'title'=> 'Donor Payment Failed',
                    'to'=>false,
                    'shortcodes'=>['designation_name', 'amount', 'method','frequency','error'],
                    'required'=>['designation_name', 'amount', 'method','frequency','error']],
            'year_end_statement'=>[
                    'title'=> 'Donor Year End Statement',
                    'to'=>false,
                    'shortcodes'=>['date','year','year_end_total','donations_table'],
                    'required'=>['year','year_end_total','donations_table']],
            'pay_fail_admin'=>[
                    'title'=> 'Notify Admin of Failed Payment',
                    'to'=>true,
                    'shortcodes'=>['designation_name', 'amount', 'method','frequency','error'],
                    'required'=>['designation_name','amount','method','frequency','error']]];
    }
}
