<?php namespace App\Controllers\Frontend;
 
    use Auth;
use BaseController;
use Form;
use Input;
use Validator;
use Redirect;
use Sentry;
use View;
use Client;
use Mail;
use Session;
use Program;
use Group;
use Hysform;
use Donorfield;
use Hash;
use Donor;
use Setting;
use Entity;
use Field;
use Upload;
use RedisL4;
use DonorEntity;
use Commitment;
use Donation;
use Emailtemplate;
use Designation;
use DateTime;
use DB;
use Carbon;
use URL;
 
use App\Http\Controllers\Controller;

class DonorSignupController extends Controller
{
    

    //Makes Signup page with Redis fields, followed by Username, Email and password supplied to MySQL
    public function signUpDonor($client_id, $program_id, $session_id = null)
    {

        $redis = RedisL4::connection();
        if ($session_id==null||$redis->exists($session_id)!=1) {
            $session_id=$this->createFrontendSession();
        }

        //Get the form that has the fields we need
        $hysform_id = Program::find($program_id)->donor_hysform_id;
            
        //Get client added fields from Redis
        $fields = Donorfield::where('client_id', $client_id)->where('hysform_id', $hysform_id)->where('permissions', '=', 'public')->orderBy('field_order')->get();

        return view('frontend.views.DonorSignup', [
            'session_id'    => $session_id,
            'client_id'     => $client_id,
            'program_id'    => $program_id,
            'fields'        => $fields]);
    }
        

    function deleteOrder($session_id, $redis)
    {

        $redis->del($session_id.':saved_entity_id');
        $redis->del($session_id.':saved_designations');
        $redis->del($session_id.':saved_entity_frequency');
        $redis->del($session_id.':saved_designation_frequency');
    }

    function createFrontendSession()
    {
      
        $time = time(); // get current unix time
        $session_id = sha1($time . 'EVERSP'); // encrypt and salt it for fun
           
        $redis = RedisL4::connection();
                
        $redis->hset($session_id, 'logged_in', 'false');
            
        $redis->expire($session_id, 3600);
         
        return $session_id;
    }

    //Makes a session for storing the current logged in Donor. Sends the session_id and donor_id
    function createDonorSession($session_id, $donor_id)
    {
        $redis = RedisL4::connection();

        $donor= Donor::find($donor_id);

        $donor->last_login= new DateTime;
        $donor->save();
            
        $old_session_id=$session_id;
            
        $time = time(); // get current unix time
        $session_id = sha1($time . 'EVERSP'); // encrypt and salt it for fun
            

        $entities= $redis->hgetall($old_session_id.':saved_entity_id');
        $designations = $redis->hgetall($old_session_id.':saved_designations');
        $en_frequency = $redis->hgetall($old_session_id.':saved_entity_frequency');
        $d_frequency = $redis->hgetall($old_session_id.':saved_designation_frequency');
        $messages=$redis->hgetall($old_session_id.':messages');

        if (!empty($entities)) {
            $redis->hmset($session_id.':saved_entity_id', $entities);
            $redis->expire($session_id.':saved_entity_id', 3600);
        }
        if (!empty($designations)) {
            $redis->hmset($session_id.':saved_designations', $designations);
            $redis->expire($session_id.':saved_designations', 3600);
        }
        if (!empty($en_frequency)) {
            $redis->hmset($session_id.':saved_entity_frequency', $en_frequency);
            $redis->expire($session_id.':saved_entity_frequency', 3600);
        }
        if (!empty($d_frequency)) {
            $redis->hmset($session_id.':saved_designation_frequency', $d_frequency);
            $redis->expire($session_id.':saved_designation_frequency', 3600);
        }
        if (!empty($messages)) {
            $redis->hmset($session_id.':messages', $messages);
            $redis->expire($session_id.':messages', 3600);
        }

        $this->deleteOrder($old_session_id, $redis);
        //Send Session Info to Redis //Allows Donor to stay logged in for one hour

        $redis->hset($session_id, "logged_in", "true");
        $redis->hset($session_id, "donor_id", $donor_id);
        
        $redis->expire($session_id, 3600);
            
        return $session_id;
    }

    public function checkoutUpdateAmount($client_id, $program_id, $entity_id, $amount, $currency, $session_id = null)
    {
        $redis = RedisL4::connection();

        if ($session_id==null||$redis->exists($session_id)!=1) {
            $session_id=$this->createFrontendSession();
        }

        $redis->hset($session_id.':saved_entity_id', $entity_id, $amount);
        $redis->expire($session_id.':saved_entity_id', 3600);

        $d= new Donor;

        $name= $d->getEntityName($entity_id, true);

        $redis->hset($session_id.':messages', '1', 'Amount for <strong>'.$name['name'].'</strong> was changed to '.$currency.$amount);
        $redis->expire($session_id.':messages', 3600);

        return redirect('/frontend/order/'.$client_id.'/'.$program_id.'/'.$session_id);
    }

    public function checkoutUpdateFrequency($client_id, $program_id, $entity_id, $frequency, $session_id = null)
    {
        $redis = RedisL4::connection();

        if ($session_id==null||$redis->exists($session_id)!=1) {
            $session_id=$this->createFrontendSession();
        }

        $redis->hset($session_id.':saved_entity_frequency', $entity_id, $frequency);
        $redis->expire($session_id.':saved_entity_frequency', 3600);
        $d= new Donor;

        $name= $d->getEntityName($entity_id, true);

        $redis->hset($session_id.':messages', '1', 'Payment schedule for <strong>'.$name['name'].'</strong> was changed to <strong>'.$d->getFrequency($frequency).'</strong>.');


        $redis->expire($session_id.':messages', 3600);
        return redirect('/frontend/order/'.$client_id.'/'.$program_id.'/'.$session_id);
    }

    public function checkoutUpdateDesignationFrequency($client_id, $program_id, $designation_id, $frequency, $session_id = null)
    {
        $redis = RedisL4::connection();

        if ($session_id==null||$redis->exists($session_id)!=1) {
            $session_id=$this->createFrontendSession();
        }

        $redis->hset($session_id.':saved_designation_frequency', $designation_id, $frequency);
        $redis->expire($session_id.':saved_designation_frequency', 3600);

        $d= Designation::where('client_id', $client_id)->find($designation_id)->get()->first();

        $donor =new Donor;

        $redis->hset($session_id.':messages', '1', 'Schedule for <strong>'.$d->name.'</strong> was changed to '.$donor->getFrequency($frequency));
        $redis->expire($session_id.':messages', 3600);
        return redirect('/frontend/order/'.$client_id.'/'.$program_id.'/'.$session_id);
    }

    public function checkoutAddDesignation($client_id, $program_id, $currency, $session_id = null)
    {
        $redis = RedisL4::connection();

        if ($session_id==null||$redis->exists($session_id)!=1) {
            $session_id=$this->createFrontendSession();
        }

        $data=Input::all();

        $rules = [
            'designation_amount' => ['numeric','min:1.00','required'],
        ];  //Minimum designation is 1 dollar
           
        $validator = Validator::make($data, $rules);
            
        if ($validator->passes()) {
            $d_id=$data['designation'];
            $d_amount=$data['designation_amount'];
            $redis->hset($session_id.':saved_designations', $d_id, $d_amount);
            $redis->hset($session_id.':saved_designation_frequency', $d_id, 5); //This makes One-Time the default
            $redis->expire($session_id.':saved_designation_frequency', 3600);
            $redis->expire($session_id.':saved_designations', 3600);

                

            $redis->hset($session_id.':messages', '1', 'Gift of '.$currency.$d_amount.' was added');
            $redis->expire($session_id.':messages', 3600);
        }
        return redirect('/frontend/order/'.$client_id.'/'.$program_id.'/'.$session_id)
            ->withErrors($validator);
    }

    public function checkoutRemoveDesignation($client_id, $program_id, $designation_id, $currency, $session_id = null)
    {

        $redis = RedisL4::connection();

        if ($session_id==null||$redis->exists($session_id)!=1) {
            $session_id=$this->createFrontendSession();
        }

        $amount=$redis->hget($session_id.':saved_designations', $designation_id);
        $redis->hdel($session_id.':saved_designations', $designation_id);
        $redis->hdel($session_id.':saved_designation_frequency', $designation_id);

        $redis->hset($session_id.':messages', '1', 'Gift of '.$currency.$amount.' was removed from your order.');
        $redis->expire($session_id.':messages', 3600);

        if ($redis->exists($session_id.':saved_entity_id')==1) {
            return redirect('/frontend/order/'.$client_id.'/'.$program_id.'/'.$session_id);
        } else {
            return redirect('/frontend/view_all/'.$client_id.'/'.$program_id.'/'.$session_id);
        }
    }

    public function checkoutRemoveEntity($client_id, $program_id, $entity_id, $session_id = null)
    {
            

        $redis = RedisL4::connection();

        if ($session_id==null||$redis->exists($session_id)!=1) {
            $session_id=$this->createFrontendSession();
        }

        $amount= $redis->hget($session_id.':saved_entity_id', $entity_id);
        $frequency=$redis->hget($session_id.':saved_entity_frequency', $entity_id);

        $redis->hdel($session_id.':saved_entity_id', $entity_id);
        $redis->hdel($session_id.':saved_entity_frequency', $entity_id);
        $d= new Donor;

        $name= $d->getEntityName($entity_id, true);

        $redis->hset($session_id.':messages', '1', '<a href="'.URL::to('frontend/view_entity', [$client_id,$program_id,$name['id'],$session_id]).'"><strong>'.$name['name'].'</strong></a> was removed from your order. If you didn\'t mean to do this:  <a class="btn btn-default" href="'.URL::to('frontend/save_entity', [$client_id,$program_id,$entity_id,$session_id,$amount,$frequency]).'"><span class="glyphicon glyphicon-repeat"></span> Undo</a>');
        $redis->expire($session_id.':messages', 3600);

        if ($redis->exists($session_id.':saved_entity_id')==1) {
            return redirect('/frontend/order/'.$client_id.'/'.$program_id.'/'.$session_id);
        } else {
            return redirect('/frontend/view_all/'.$client_id.'/'.$program_id.'/'.$session_id);
        }
    }



    public function checkout($client_id, $program_id, $session_id = null)
    {

        $redis = RedisL4::connection();

        $program = new Program;
        $program_ids= $program->getPrograms($client_id, $program_id);
            
        $program=Program::find($program_ids[0]);
  
        $donor_id =$redis->hget($session_id, 'donor_id');

        $d = new Donor;
        $donation= new Donation;

        if ($session_id==null||$redis->exists($session_id)!=1) {
            $session_id=$this->createFrontendSession();
        }

        $redis->expire($session_id, 3600);
        $redis->expire($session_id.':saved_entity_id', 3600);
        $redis->expire($session_id.':saved_entity_frequency', 3600);
        $redis->expire($session_id.':saved_designations', 3600);
        $redis->expire($session_id.':saved_designation_frequency', 3600);

        $entities=[];

        $frequencies= $redis->hgetall($session_id.':saved_entity_frequency');
        
        $d_frequencies= $redis->hgetall($session_id.':saved_designation_frequency');

        $entities = $redis->hgetall($session_id.':saved_entity_id');

        $saved_designations=$redis->hgetall($session_id.':saved_designations');

        if ($entities==[]) { //This kicks the user back to the view page if their order is empty
            return redirect('/frontend/view_all/'.$client_id.'/'.$program_id.'/'.$this->createFrontendSession().'');
        }

        foreach ($entities as $id => $entity) {
            $entity= Entity::where('client_id', $client_id)->find($id);
            $tmp_name=$d->getEntityName($id, true);
            $titles[$id]=$tmp_name['name'];
            if ($program->link_id!=0) {
                $temp_program_settings = (array) json_decode(Setting::find($program->setting_id)->program_settings);
            } else {
                $temp_program= Program::find($entity->program_id);
                $temp_program_settings = (array) json_decode(Setting::find($temp_program->setting_id)->program_settings);
            }
            if ($entity->sp_amount!='') {
                $amount_permissions[$id]=['amount' => $entity->sp_amount];
            } else {
                $amount_permissions[$id]=['amount' => null];
            }

            $amount_permissions[$id]['hide_frequency']= isset($temp_program_settings['hide_frequency']) ? $temp_program_settings['hide_frequency'] : '' ;

            if ($temp_program_settings['program_type']=='contribution') {
                $amount_permissions[$id]['program_type']='contribution';
                $amount_permissions[$id]['sp_amount']=explode(',', $temp_program_settings['sponsorship_amount']);
            }

            if ($temp_program_settings['program_type']=='number') {
                $amount_permissions[$id]['program_type']='number';
            }

            if ($temp_program_settings['program_type']=='funding') {
                $amount_permissions[$id]['program_type']='funding';
                $amount_permissions[$id]['sp_amount']=explode(',', $temp_program_settings['sponsorship_amount']);
            }
            if ($temp_program_settings['program_type']=='one_time') {
                $amount_permissions[$id]['program_type']='one_time';
                $amount_permissions[$id]['sp_amount']=explode(',', $temp_program_settings['sponsorship_amount']);
            }
        }

        foreach ($entities as $entity_id => $sp_amount) {
            $hashes[$entity_id] = 'id:'.$entity_id;
        }

        if (isset($hashes)) {
            foreach ($hashes as $k => $hash) {
                $profiles[$k] = $redis->hgetall($hash);
            }
        }
            
        $hysform_id = $program->donor_hysform_id;
                    

        $signup_fields = Donorfield::where('client_id', $client_id)->where('hysform_id', $hysform_id)->where('permissions', '=', 'public')->orderBy('field_order')->get();

        // //This gets the previous
        // foreach($signup_fields as $k => $field)
        // {
        // 		$signup_values[$k]=Input::get($field->field_key);
        // }
        // //var_dump (Input::all());
            

        //This is for the dropdown list of values for the donor to select from
        $program_settings = (array) json_decode(Setting::find($program->setting_id)->program_settings);

        $disable_program_link='';
        if (isset($program_settings['disable_program_link'])) {
            $disable_program_link=$program_settings['disable_program_link'];
        }
            

        $text_checkout=Setting::where('client_id', $client_id)->find($program->setting_id)->text_checkout;
            
        if (empty($program_settings['currency_symbol'])) {
            $program_settings['currency_symbol']='$';
        }

        if ($program_settings['program_type'] == 'contribution') {
            $sp_amount = explode(',', $program_settings['sponsorship_amount']);
            $vars = ['symbol' => $program_settings['currency_symbol']];
            $vars['program_type']='contribution';
        }
            
        if ($program_settings['program_type'] == 'number') {
            $vars = ['symbol' => $program_settings['currency_symbol']];
            $vars['program_type']='number';
        }
        if ($program_settings['program_type'] == 'funding') {
            $sp_amount = explode(',', $program_settings['sponsorship_amount']);
            $vars = ['symbol' => $program_settings['currency_symbol']];
            $vars['program_type']='funding';
        }
        if ($program_settings['program_type'] == 'one_time') {
            $sp_amount = explode(',', $program_settings['sponsorship_amount']);
            $vars = ['symbol' => $program_settings['currency_symbol']];
            $vars['program_type']='one_time';
        }

            

        $upload=new Upload;

        $num_of_sponsorships=0;
        foreach ($entities as $id => $amount) {
            $num_of_sponsorships++;
            $uploads = Entity::find($id)->uploads;
                
            if (isset($uploads)) {
                    $links=null;
                foreach ($uploads as $file) {
                        //This if statment only sends the view the profile pics
                    if ($file->profile==1) {
                        $links[] = $upload->makeAWSlinkThumb($file);
                    }
                }
                    
                if (isset($links)) {
                    $profilePics[$id]=$links;
                } else {
                    if (isset($program_settings['placeholder'])&&$program_settings['placeholder']!='') {
                        $profilePics[$id][]=$program_settings['placeholder'];
                    } else {
                        $profilePics[$id][]=URL::to('/images/placeholder.gif');
                    }
                }
            }
        }

        $type = false;
        if (isset($program_settings['program_type'])) {
            $type=$program_settings['program_type'];
        }

        $frequency_options=$d->getFrequencies($type);
        $d_frequency_options=$d->getDesignationFrequencies();

        $desigs = Designation::whereClientId($client_id)->get();

        $designations = null;
        foreach ($desigs as $d) {
            $hysform_ids = json_decode($d->hysforms, true);
            if (isset($hysform_ids)) {
                if (is_array($hysform_ids)) {
                    if (in_array($hysform_id, $hysform_ids)) {
                        $designations[$d->id] = $d;
                    }
                } else {
                    if ($hysform_id == $hysform_ids) {
                        $designations[$d->id] = $d;
                    }
                }
            }
        }

        $dntns = new Donation;
        
        $login_box='';
        if (isset($program_settings['login_box'])) {
            $login_box=$program_settings['login_box'];
        }



        $designations_allowed='';
        if (isset($program_settings['designations'])) {
            $designations_allowed=$program_settings['designations'];
        }
            

            
        $donor = new Donor;

        $payment_array=$donor->getPaymentOptions($program->id, $client_id);

        if (empty($payment_array['default_payment_method'])) {
            $redis->hset($session_id.':messages', 'error', 'Error: Administrator must enable a payment method in program settings.');
            $redis->expire($session_id.':messages', 3600);

            return redirect('/frontend/view_all/'.$client_id.'/'.$program_id.'/'.$session_id.'');
        }

        $t_e= new Entity;
        $total=$t_e->getTotal($session_id);

        $months = ['01'=>'01 - January','02'=>'02 - February','03'=>'03 - March','04'=>'04 - April','05'=>'05 - May','06'=>'06 - June','07'=>'07 - July','08'=>'08 - August','09'=>'09 - September','10'=>'10 - October','11'=>'11 - November','12'=>'12 - December'];
            
        $today=Carbon::now();
        $i = 0;
        while ($i<10) {
            $years[$today->year+$i] = $today->year+$i;
            $i++;
        }

        $frequency_array = [];

        foreach ($frequencies as $k => $f) {
            if (isset($frequency_array[$frequency_options[$f]])) {
                $frequency_array[$frequency_options[$f]]++;
            } else {
                $frequency_array[$frequency_options[$f]]=1;
            }
        }
        foreach ($d_frequencies as $k => $f) {
            if (isset($frequency_array[$d_frequency_options[$f]])) {
                $frequency_array[$d_frequency_options[$f]]++;
            } else {
                $frequency_array[$d_frequency_options[$f]]=1;
            }
        }

        foreach ($frequency_array as $k => $f) {
            $frequency_text[]= $f.' paid '.$k;
        }

        if (count($frequency_text)==0) {
            $frequency_text= '';
        } elseif (count($frequency_text)==1) {
            $frequency_text=array_pop($frequency_text);
        } else {
            $last_element=array_pop($frequency_text);
            $second_to_last=array_pop($frequency_text);
            array_push($frequency_text, $second_to_last .' <br> '.$last_element);
            $frequency_text= implode(', ', $frequency_text);
        }

        $hide_frequency = true;
        foreach ($amount_permissions as $p) {
            if ($p['hide_frequency']=='hidden') {
                $hide_frequency= false;
            }
        }


        $vars = [
            'session_id'        => $session_id,
            'client_id'         => $client_id,
            'program_id'        => $program_id,
            'entities'          => $entities,
            'amount_permissions'=> $amount_permissions,
            'signup_fields'     => $signup_fields,
            'text_checkout'     => $text_checkout,
            'profilePics'       => $profilePics,
            'titles'            => $titles,
            'frequencies'       => $frequencies,
            'd_frequencies'     => $d_frequencies,
            'frequency_options' => $frequency_options,
            'd_frequency_options'   => $d_frequency_options,
            'vars'              => $vars,
            'useCC'             => $payment_array['useCC'],
            'isDonorCardActive'=> $dntns->isDonorCardActive($donor_id, $client_id),
            'designations'      => $designations,
            'saved_designations'=> $saved_designations,
            'total'             => $total,
            'login_box'         => $login_box,
            'num_of_sponsorships'   => $num_of_sponsorships,
            'designations_allowed'  => $designations_allowed,
            'payment_options'       => $payment_array['payment_options'],
            'hide_payment_method'   => $payment_array['hide_payment_method'],
            'default_payment_method'=> $payment_array['default_payment_method'],
            'months'                => $months,
            'years'                 => $years,
            'num_sponsorships'      => count($entities)+count($saved_designations),
            'frequency_text'        => $frequency_text,
            'disable_program_link'  => $disable_program_link,
            'currency_symbol'       => $program_settings['currency_symbol'],
            'hide_frequency'        => $hide_frequency,
            ];

        return view('frontend.views.checkOut', $vars);
    }

    public function loggedInCheckout($client_id, $program_id, $entity_id, $session_id = null)
    {

        $redis=RedisL4::connection();
            
        $logged_in=$redis->hget($session_id, 'logged_in');

        $sp_amount=Input::get('sponsorship_amount');

        $method= Input::get('method');

        $frequency= Input::get('frequency');

        $data=Input::all();

        $rules = [
            'sponsorship_amount' => 'required|numeric|min:5',
            'method' => 'required',
            'frequency' => 'required'
        ];
           
        $validator = Validator::make($data, $rules);

        if ($validator->passes()) {
            if ($logged_in=='true') {
                //This clears the session of previously viewed entities and designations
                $redis->del($session_id.':saved_designations');
                $redis->del($session_id.':saved_designation_frequency');
                $redis->del($session_id.':saved_entity_id');
                $redis->del($session_id.':saved_entity_frequency');

                $redis->hset($session_id.':saved_entity_id', $entity_id, $sp_amount);
                $redis->expire($session_id.':saved_entity_id', 3600);
                $redis->hset($session_id.':saved_entity_frequency', $entity_id, $frequency);
                $redis->expire($session_id.':saved_entity_frequency', 3600);

                $donor_id=$redis->hget($session_id, 'donor_id');
                $donation= new Donation;
                $stripe_id= $donation->getDonorStripeId($donor_id, $client_id);

                if ($stripe_id==false&&$method=='3') {
                    return redirect('frontend/order/'.$client_id.'/'.$program_id.'/'.$session_id);
                }
                    

                $d= new Donor;

                $program= new Program;
                $program_ids=$program->getPrograms($client_id, $program_id);

                $this->postAddSponsorships($client_id, $program_ids, $entity_id, $donor_id, $sp_amount, $frequency, 1, $method);

                $d->addDonationOrder($session_id, $program_ids[0], $client_id, $donor_id, 'method');

                $name= $d->getEntityName($entity_id, true);

                if ($frequency=='5') {
                    $redis->hset($session_id.':messages', '1', '  Donation for <strong>'.$name['name'].'</strong> successfully made!');
                } else {
                    $redis->hset($session_id.':messages', '1', '  Sponsorship for <strong>'.$name['name'].'</strong> successfully added!');
                }
                    
                $redis->expire($session_id.':messages', 3600);

                $this->deleteOrder($session_id, $redis);
                return redirect('frontend/donor_view/'.$client_id.'/'.$program_id.'/'.$session_id);
            }
        } else {
            return redirect('frontend/view_entity/'.$client_id.'/'.$program_id.'/'.$entity_id.'/'.$session_id)
            ->withErrors($validator)
            ->withInput();
        }
    }
        
    public function postAddDonor($client_id, $program_id, $session_id = null)
    {

        $program = Program::find($program_id);

        $hysform_id = $program->donor_hysform_id;
            
        $data = Input::all();
            
        unset($data['_token']);

        $rules = [
            'username' => 'unique:donors|min:5',
            'email' => 'email|required|unique:donors,email,NULL,id,client_id,'.$client_id,
            'password' => 'min:5'
        ];
           
        $validator = Validator::make($data, $rules);
            
        if ($validator->passes()) {
            $password = Hash::make($data['password']);
            unset($data['password']);
                
            $donor = new Donor;
            $donor->client_id = $client_id;
            $donor->hysform_id = $hysform_id;
            $donor->username = $data['username'];
            $donor->email = $data['email'];
            $donor->password = $password;
            $donor->who_added= json_encode(['type'=>'donor','method'=>'individual','id'=>'']);
                

            $hysform= Hysform::find($hysform_id);
            $count = $hysform->counter + 1;
            $hysform->counter = $count;
            $hysform->save();

            $fields = Donorfield::whereHysformId($hysform_id)->get();
            foreach ($fields as $field) {
                $field_types[$field->field_key] = $field->field_type;
            }
                
            unset($data['email']);
            unset($data['username']);
                
            $hash = 'donor:id:'.$donor->id;
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

                    
            if (!empty($profile)) {
                $donor->json_fields =json_encode($profile);
            }

            $donor->save();

            //Reload the Cache entry for this donor!
            $donor->reloadDonorsToCache($donor);

            //Try to sync this donor to Mailchimp
            $donor->syncDonorsToMailchimp($donor);

            //Email Admin on Donor signup
            $to = ['type' => 'admin','email' => '' ,'name' => 'admin'];
             
            $email= new Emailtemplate;

            $details['donor'] = $email->getDonor($donor->id);

            $details['donation'] = [
                'designations'  => 'None',
                'total_amount'  => '0',
                'method'        => '',
                ];

            $details['other'] = ['date'=> Carbon::now()->toFormattedDateString(),'donor_email'=>$donor->email];
                
            $adminEmailSent = $email->sendEmail($program->emailset_id, $details, 'new_donor_admin', $to);

            //Email Donor on signup

            $to = ['type' => 'donor','email' => $donor->email ,'name' => 'username','id'=> $donor->id];
             
            $email = new Emailtemplate;

            $details['donor'] = $email->getDonor($donor->id);

            $details['other'] = ['date'=> Carbon::now()->toFormattedDateString(),'username'=>$donor->username];
                
            $adminEmailSent = $email->sendEmail($program->emailset_id, $details, 'new_donor', $to);

            // need error handling

            $session_id=$this->createDonorSession($session_id, $donor->id);
                
            $redis = RedisL4::connection();
            $redis->hset($session_id.':messages', '1', "Welcome, your donor account was successfully added!".$this->getScrollMessage());
            $redis->expire($session_id.':messages', 3600);

            return redirect('frontend/donor_view/'.$client_id.'/'.$program_id.'/'.$session_id)
                ->withInput();
        }
            
        return redirect('frontend/signup_donor/'.$client_id.'/'.$program_id)
            ->withErrors($validator)
            ->withInput();
    }


    public function postCheckoutSignup($client_id, $program_id, $session_id = null)
    {
                    
        $program = Program::find($program_id);

        $hysform_id = $program->donor_hysform_id;
            
        $data = Input::all();
        unset($data['_token']);
            
        $rules = [
            'signup_username' => 'unique:donors,username|min:5|required',
            'email' => 'email|required|unique:donors,email,NULL,id,client_id,'.$client_id,
            'signup_password' => 'min:5|required'
        ];

        $cc_rules=[];

        if ($data['method_signup']=='3') {
            $cc_rules= [
                'firstName' => 'required',
                'lastName' => 'required',
                'number' => 'required',
                'cvv' => 'required',
                'expiryMonth' => 'required',
                'expiryYear' => 'required'
                ];
        }
           
        $validator = Validator::make($data, array_merge($rules, $cc_rules));
            
        if ($validator->passes()) {
            $password = Hash::make($data['signup_password']);
            unset($data['signup_password']);
                
            $donor = new Donor;
            $donor->client_id = $client_id;
            $donor->hysform_id = $hysform_id;
            $donor->username = $data['signup_username'];
            $donor->email = $data['email'];
            $donor->password = $password;
            $donor->who_added= json_encode(['type'=>'donor','method'=>'individual','id'=>'']);
            $donor->save();

                
            $hysform= Hysform::find($hysform_id);
            $count = $hysform->counter + 1;
            $hysform->counter = $count;
            $hysform->save();

            $counter = $hysform->prefix.$count;
            $fields = Donorfield::whereHysformId($hysform_id)->get();
            foreach ($fields as $field) {
                $field_types[$field->field_key] = $field->field_type;

                if ($field->field_type=='hysCustomid') {
                    $data[$field->field_key]='';
                }
            }
                

            unset($data['email']);
            unset($data['signup_username']);

                
                
            $hash = 'donor:id:'.$donor->id;
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
                if (isset($field_types[$k])) {
                    if ($field_types[$k] == 'hysCustomid') {
                        $v = $counter;
                    }
                        
                    $profile[$k] = "$v";
                }
            }
                
                    
            // need error handling
            
            $redis = RedisL4::connection();
            // $redis->hmset($hash, $profile);
            $donor->json_fields=json_encode($profile);
            $donor->save();

            //Reload the Cache entry for this donor!
            $donor->reloadDonorsToCache($donor);

            $donor->syncDonorsToMailchimp($donor);

            $d= new Donor;
            
            $program= new Program;
            $program_ids=$program->getPrograms($client_id, $program_id);

            $result=$d->addDonationOrder($session_id, $program_ids[0], $client_id, $donor->id, 'method_signup');

            $redis->hset($session_id.':messages', '1', "Welcome, you successfully logged in!");
            $redis->expire($session_id.':messages', 3600);
            if ($result==true) {
                foreach ($redis->hgetall($session_id.':saved_entity_id') as $entity_id => $sp_amount) {
                    $name= $d->getEntityName($entity_id, true);

                    $frequency=$redis->hget($session_id.':saved_entity_frequency', $entity_id);

                    if ($frequency!=5) {
                        $redis->hset($session_id.':messages', $entity_id, 'Sponsorship for <strong>'.$name['name'].'</strong> successfully added!'.$this->getScrollMessage());
                        $redis->expire($session_id.':messages', 3600);
                        $this->postAddSponsorships($client_id, $program_ids, $entity_id, $donor->id, $sp_amount, $redis->hget($session_id.':saved_entity_frequency', $entity_id), 1, $data['method_signup']);
                    }
                }

                foreach ($redis->hgetall($session_id.':saved_designations') as $designation_id => $sp_amount) {
                    $frequency=$redis->hget($session_id.':saved_designation_frequency', $designation_id);

                    if ($frequency!=5) {
                        $temp_desig=Designation::find($designation_id)->pluck('name');
                        $redis->hset($session_id.':messages', 'd'.$designation_id, 'Commitment for <strong>'.$temp_desig.'</strong> successfully added!'.$this->getScrollMessage());
                        $redis->expire($session_id.':messages', 3600);
                        $this->postAddSponsorships($client_id, $program_ids, $designation_id, $donor->id, $sp_amount, $frequency, 2, $data['method_signup']);
                    }
                }
            }
                
            $session_id=$this->createDonorSession($session_id, $donor->id);
            if ($result==false) {
                return redirect('/frontend/order/'.$client_id.'/'.$program_id.'/'.$session_id)
                ->withErrors($validator)
                ->withInput();
            }

            $this->deleteOrder($session_id, $redis);


            return redirect('frontend/donor_view/'.$client_id.'/'.$program_id.'/'.$session_id);
        }



        $failed = $validator->failed();
        $message = '';

        if (isset($failed['email'])) {
            $message[] = 'Error: The Email address <strong>'.$data['email'].'</strong> is already being used.';
        }
        if (isset($failed['signup_username'])) {
            $message[] = 'Error: The username <strong>'.$data['signup_username'].'</strong> is already being used.';
        }
        if (!empty($message)) {
            $redis = RedisL4::connection();
            $program = Program::find($program_id);
            $program_settings = (array) json_decode(Setting::find($program->setting_id)->program_settings);
            $redis->hset($session_id.':messages', 'error', implode('<br>', $message));
                
            $extra = 'Tip: If you already have an account, input your username and password in the "Existing Sponsors" box then click "Login and Sponsor."';
            if ($program_settings['login_box']=='1') {
                $redis->hset($session_id.':messages', 'Tip', $extra);
            }
            $redis->expire($session_id.':messages', 3600);
        }

        return redirect('/frontend/order/'.$client_id.'/'.$program_id.'/'.$session_id)
            ->withErrors($validator)
            ->withInput();
    }

    public function postCheckoutLogin($client_id, $program_id, $session_id = null)
    {
                    
        $data = Input::all();
        $redis= RedisL4::connection();
        $donor_id= $redis->hget($session_id, 'donor_id');
        if (isset($donor_id)) {
            $donor=Donor::find($donor_id);
        }

        //return var_dump($donor_id);

        unset($data['_token']);
        
        $rules = [
            'login_username' => 'exists:donors,username,client_id,'.$client_id,
            'login_password' => 'required'
        ];
           
        $validator = Validator::make($data, $rules);
            
        if ($validator->passes()||isset($donor_id)) {
            $method=Input::get('method_login');

            $method_signed_in = Input::get('method_signed_in');

            if ($method_signed_in!=null) {
                $method= $method_signed_in;
                Input::merge(['method_login' => $method_signed_in]);
            }

            if (!isset($donor_id)) {
                $password=$data['login_password'];
                $username=$data['login_username'];
                unset($data['login_password']);
                
                $donor = Donor::where('username', '=', $username)->get()->first();

                if (!isset($donor)) {
                    $redis->hset($session_id.':messages', 'error', 'Error: Account Does Not Exist');
                    $redis->expire($session_id.':messages', 3600);
                    return redirect('frontend/order/'.$client_id.'/'.$program_id.'/'.$session_id)
                        ->withErrors($validator)
                        ->withInput();
                }

                if (Hash::check($password, $donor->password)) {
                    $donor_id=$donor->id;
                } else {
                    $redis->hset($session_id.':messages', 'error', 'Error: Invalid Username or Password');
                    $redis->expire($session_id.':messages', 3600);
                    return redirect('/frontend/order/'.$client_id.'/'.$program_id.'/'.$session_id)
                        ->withErrors($validator)
                        ->withInput();
                }
            }
                
            if (isset($donor_id)) {
                $donation = new Donation;

                $stripe_id= $donation->getDonorStripeId($donor->id, $client_id);

                if ($stripe_id==false&&$method=='3'&&Input::get('page')!='cc') {
                    $session_id=$this->createDonorSession($session_id, $donor->id);
                    return redirect('/frontend/order/'.$client_id.'/'.$program_id.'/'.$session_id);
                }


                //$redis = RedisL4::connection();
                    
                $session_id=$this->createDonorSession($session_id, $donor->id);
                    
                $result=$donor->addDonationOrder($session_id, $program_id, $client_id, $donor->id, 'method_login');
                if ($result==true) {
                    $donor= Donor::find($donor_id);
                    //Reload the Cache entry for this donor!
                    $donor->reloadDonorsToCache($donor);
                        
                    foreach ($redis->hgetall($session_id.':saved_entity_id') as $entity_id => $sp_amount) {
                        $name= $donor->getEntityName($entity_id, true);

                        if ($method_signed_in==null) {
                            $redis->hset($session_id.':messages', 'e'.$entity_id, 'Sponsorship for <strong>'.$name['name'].'</strong> successfully added!'.$this->getScrollMessage());
                        } else {
                            $redis->hset($session_id.':messages', 'e'.$entity_id, 'Sponsorship for <strong>'.$name['name'].'</strong> successfully added!');
                        }
                        $redis->expire($session_id.':messages', 3600);
                        $this->postAddSponsorships($client_id, $program_id, $entity_id, $donor->id, $sp_amount, $redis->hget($session_id.':saved_entity_frequency', $entity_id), 1, $method);
                    }
                    foreach ($redis->hgetall($session_id.':saved_designations') as $designation_id => $sp_amount) {
                        $frequency=$redis->hget($session_id.':saved_designation_frequency', $designation_id);

                        if ($frequency!=5) {
                            $temp_desig=Designation::find($designation_id)->pluck('name');

                            $redis->hset($session_id.':messages', 'd'.$designation_id, 'Sponsorship for <strong>'.$temp_desig.'</strong> successfully added!'.$this->getScrollMessage());
                            $redis->expire($session_id.':messages', 3600);

                            $this->postAddSponsorships($client_id, $program_id, $designation_id, $donor->id, $sp_amount, $frequency, 2, $method);
                        }
                    }
                }
                if ($result==false) {
                    return redirect('/frontend/order/'.$client_id.'/'.$program_id.'/'.$session_id)
                    ->withErrors($validator)
                    ->withInput();
                }

                //success! Go to the donor_view
                $this->deleteOrder($session_id, $redis);
                return redirect('frontend/donor_view/'.$client_id.'/'.$program_id.'/'.$session_id);
            } else {
                return redirect('/frontend/order/'.$client_id.'/'.$program_id.'/'.$session_id)
                    ->withErrors($validator)
                    ->withInput();
            }
        } else {
            $redis->hset($session_id.':messages', 'error', 'Error: Invalid Username or Password');
            $redis->expire($session_id.':messages', 3600);
            return redirect('/frontend/order/'.$client_id.'/'.$program_id.'/'.$session_id);
        }
    }

    public function postAddSponsorships($client_id, $program_ids, $entity_id, $donor_id, $sp_amount, $frequency, $type, $method, $session_id = null)
    {
            
        $donor = new Donor;
        $data = Input::all();
        $p = new Program;
            
        $entity= Entity::find($entity_id);

        $program_id=$program_ids[0];

        //Check to see if the entity's program id differs from the program id (ie. sub program)
        //If it's a designation, set the program id according to the first program listed.
        if ($type==1) {
            if (count($program_ids)>1) {
                $program_id = $entity->program_id;
            } else {
                $program_id = $program_ids[0];
            }
        } elseif ($type==2) {
            //Set the program_id to zero
        // if(isset($program_ids[0]))
            // 	$program_id=$program_ids[0];
            // else
                $program_id=0;
        }
        if ($method=='3') {
            $last=Carbon::now();
        } else {
            $last=null;
        }

        $program_type= $p->getProgramTypeFromEntity($entity_id);
            
        if ($type==1&&$frequency!=5) {
            $DonorEntity = new DonorEntity;
            $DonorEntity->donor_id = $donor_id;
            $DonorEntity->entity_id = $entity_id;
            $DonorEntity->client_id = $client_id;
            $DonorEntity->program_id= $program_id;

            //Set the program id if the admin chooses (for sub-programs)
            $DonorEntity->program_id=$entity->program_id;
            $temp_program=Program::find($program_id);
            if ($temp_program!=null) {
                if ($temp_program->client_id==$client_id) {
                    $DonorEntity->program_id=$program_id;
                }
            }
            $DonorEntity->save();
                
                
            $commitment = new Commitment;
            $commitment->client_id = $client_id;
            $commitment->donor_id = $donor_id;
            $commitment->donor_entity_id = $DonorEntity->id;
            $commitment->type = $type;
            $commitment->frequency = $frequency;
            if (isset($data['until'])) {
                $commitment->until = $data['until'];
            }
            $commitment->amount = $sp_amount;
            $commitment->designation = $entity_id;
            $commitment->method = $method;
            if ($last) {
                $commitment->last=$last;
            }

            if ($program_type=='funding') {
                $commitment->funding=1;
            }
                
            $commitment->save();
                
            if (isset($data['until'])) {
                $DonorEntity->until = $data['until'];
            }
            $DonorEntity->save();

            $e = new Entity;

            // $e->reloadSponsorshipsToCache($DonorEntity);
            $e->reloadEntitiesToCache($entity);
                
                
            $status = $donor->setStatus($DonorEntity->entity_id);
        }


        if ($type==2&&$frequency!=5) {
            $commitment = new Commitment;
            $commitment->client_id = $client_id;
            $commitment->donor_id = $donor_id;
                
            $commitment->type = $type;
            $commitment->frequency = $frequency;
                
            $commitment->amount = $sp_amount;
            $commitment->designation = $entity_id;
            $commitment->method = $method;
                
            if ($last) {
                $commitment->last=$last;
            }

            $commitment->save();
        }
    }

    private function getScrollMessage()
    {
            return "<script>alert('Your account was created. Click Ok and scroll to the top of the page.')</script>";
    }
}
