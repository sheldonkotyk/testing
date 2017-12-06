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

class DesignationCheckoutController extends Controller
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
            
            
        if (!$redis->exists($session_id)) {
            $time = time(); // get current unix time
            $session_id = sha1($time . 'EVERSP'); // encrypt and salt it for fun
        }
            
        //Send Session Info to Redis //Allows Donor to stay logged in for one hour

        $redis->hset($session_id, "logged_in", "true");
        $redis->hset($session_id, "donor_id", $donor_id);
        
        $redis->expire($session_id, 3600);
            
            
            
        return $session_id;
    }

    public function checkoutUpdateAmount($client_id, $program_id, $entity_id, $amount, $currency, $session_id)
    {
        $redis = RedisL4::connection();

        if ($session_id==null||$redis->exists($session_id)!=1) {
            $session_id=$this->createFrontendSession();
        }

        $redis->hset($session_id.':saved_entity_id', $entity_id, $amount);
        $redis->expire($session_id.':saved_entity_id', 3600);

        $d= new Donor;

        $name= $d->getEntityName($entity_id);

        $redis->hset($session_id.':messages', '1', 'Sponsorship amount for <strong>'.$name['name'].'</strong> was changed to '.$currency.$amount);
        $redis->expire($session_id.':messages', 3600);

        return redirect('/frontend/orderD/'.$client_id.'/'.$program_id.'/'.$session_id);
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

        $name= $d->getEntityName($entity_id);

        $redis->hset($session_id.':messages', '1', 'Sponsorship schedule for <strong>'.$name['name'].'</strong> was changed to '.$d->getFrequency($frequency));
        $redis->expire($session_id.':messages', 3600);
        return redirect('/frontend/orderD/'.$client_id.'/'.$program_id.'/'.$session_id);
    }

    public function checkoutUpdateDesignationFrequency($client_id, $hysform_id, $designation_id, $id, $frequency, $session_id)
    {
        $redis = RedisL4::connection();

        if ($session_id==null||$redis->exists($session_id)!=1) {
            $session_id=$this->createFrontendSession();
        }

        $redis->hset($session_id.':saved_designation_frequency', $id, $frequency);
        $redis->expire($session_id.':saved_designation_frequency', 3600);
        $d= Designation::where('client_id', $client_id)->find($id)->get()->first();

        $donor =new Donor;

        $redis->hset($session_id.':messages', '1', 'Schedule for <strong>'.$d->name.'</strong> was changed to '.$donor->getFrequency($frequency));
        $redis->expire($session_id.':messages', 3600);

          return redirect('/frontend/orderD/'.$client_id.'/'.$hysform_id.'/'.$designation_id.'/'.$session_id);
    }

    public function checkoutAddDesignation($client_id, $hysform_id, $designation_id, $currency, $session_id = null)
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
        return redirect('/frontend/orderD/'.$client_id.'/'.$hysform_id.'/'.$designation_id.'/'.$session_id)
            ->withErrors($validator);
    }

    public function checkoutRemoveDesignation($client_id, $hysform_id, $designation_id, $id, $currency, $session_id = null)
    {

        $redis = RedisL4::connection();

        if ($session_id==null||$redis->exists($session_id)!=1) {
            $session_id=$this->createFrontendSession();
        }

        $amount=$redis->hget($session_id.':saved_designations', $id);
        $redis->hdel($session_id.':saved_designations', $id);
        $redis->hdel($session_id.':saved_designation_frequency', $id);

        $redis->hset($session_id.':messages', '1', 'Gift of '.$currency.$amount.' was removed from your order.');
        $redis->expire($session_id.':messages', 3600);
        return redirect('/frontend/orderD/'.$client_id.'/'.$hysform_id.'/'.$designation_id.'/'.$session_id);
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
            $donor->save();
                

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
                $profile[$k] = "$v";
            }
                    
            // need error handling
            
            $redis = RedisL4::connection();
            $redis->hmset($hash, $profile);

            $session_id=$this->createDonorSession($session_id, $donor->id);

            $redis->hset($session_id.':messages', '1', "Welcome, your donor account was successfully added!");
            $redis->expire($session_id.':messages', 3600);

            return redirect('frontend/donor_view/'.$client_id.'/'.$program_id.'/'.$session_id)
                ->withInput();
        }
        return redirect('frontend/signup_donor/'.$client_id.'/'.$program_id)
            ->withErrors($validator)
            ->withInput();
    }


    public function postCheckoutSignup($client_id, $hysform_id, $designation_id, $session_id = null)
    {
                    

            
        $data = Input::all();
        unset($data['_token']);
            
        $rules = [
            'signup_username' => 'unique:donors,username|min:5|required',
            'email' => 'email|required',
            'signup_password' => 'min:5|required'
        ];
           
        $validator = Validator::make($data, $rules);
            
        if ($validator->passes()) {
            $password = Hash::make($data['signup_password']);
            unset($data['signup_password']);
                
            $donor = new Donor;
            $donor->client_id = $client_id;
            $donor->hysform_id = $hysform_id;
            $donor->username = $data['signup_username'];
            $donor->email = $data['email'];
            $donor->password = $password;
            $donor->save();
                

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
                $profile[$k] = "$v";
            }
                    
            // need error handling
            
            $redis = RedisL4::connection();
            // $redis->hmset($hash, $profile);
            $donor->json_fields=json_encode($profile);
            $donor->save();

            $d= new Donor;

            $result=$d->addDonationOrder($session_id, 'none', $client_id, $donor->id, 'method_signup');

            $redis->hset($session_id.':messages', '1', "Welcome, you successfully logged in!");
            $redis->expire($session_id.':messages', 3600);

            if ($result==true) {
                foreach ($redis->hgetall($session_id.':saved_designations') as $designation_id => $sp_amount) {
                    $frequency=$redis->hget($session_id.':saved_designation_frequency', $designation_id);

                    if ($frequency!=5) {
                        $temp_desig=Designation::find($designation_id)->pluck('name');
                        $redis->hset($session_id.':messages', 'd'.$designation_id, 'Commitment for <strong>'.$temp_desig.'</strong> successfully added!');
                        $redis->expire($session_id.':messages', 3600);
                        $this->postAddSponsorships($client_id, $designation_id, $donor->id, $sp_amount, $frequency, 2, $data['method_signup']);
                    } else {
                        $temp_desig=Designation::find($designation_id)->pluck('name');
                        $redis->hset($session_id.':messages', 'd'.$designation_id, 'Your Donation for '. $temp_desig.' was accepted. Thank you!');
                        $redis->expire($session_id.':messages', 3600);
                    }
                }
            }
                
            $session_id=$this->createDonorSession($session_id, $donor->id);
            if ($result==false) {
                    return redirect('frontend/orderD/'.$client_id.'/'.$hysform_id.'/'.$designation_id.'/'.$session_id)
                    ->withErrors($validator)
                    ->withInput();
            }

            $this->deleteOrder($session_id, $redis);


            return redirect('frontend/donor_view/'.$client_id.'/none/'.$session_id);
        }
        return redirect('frontend/orderD/'.$client_id.'/'.$hysform_id.'/'.$designation_id.'/'.$session_id)
            ->withErrors($validator)
            ->withInput();
    }


    public function postCheckoutLogin($client_id, $hysform_id, $designation_id, $session_id = null)
    {
                    
        $data = Input::all();
        $redis= RedisL4::connection();
        $donor_id= $redis->hget($session_id, 'donor_id');
        if (isset($donor_id)) {
            $donor=Donor::find($donor_id);
        }

        unset($data['_token']);
        
        $rules = [
            'login_username' => 'exists:donors,username',
            'login_password' => 'required'
        ];
           
        $validator = Validator::make($data, $rules);
            
        if ($validator->passes()||isset($donor_id)) {
            $method=Input::get('method_login');
            if (!isset($donor_id)) {
                $password=$data['login_password'];
                $username=$data['login_username'];
                unset($data['login_password']);
                
                $donor = Donor::where('username', '=', $username)->get()->first();
                if (Hash::check($password, $donor->password)) {
                    $donor_id=$donor->id;
                }
            }
                
            if (isset($donor_id)) {
                $donation = new Donation;

                $stripe_id= $donation->getDonorStripeId($donor->id, $client_id);

                if ($stripe_id==false&&$method=='3'&&Input::get('page')!='cc') {
                    $session_id=$this->createDonorSession($session_id, $donor->id);
                    return redirect('frontend/orderD/'.$client_id.'/'.$hysform_id.'/'.$designation_id.'/'.$session_id);
                }

                $redis = RedisL4::connection();
                        
                $session_id=$this->createDonorSession($session_id, $donor->id);
                $result=$donor->addDonationOrder($session_id, 'none', $client_id, $donor->id, 'method_login');
                if ($result==true) {
                    foreach ($redis->hgetall($session_id.':saved_designations') as $designation_id => $sp_amount) {
                        $frequency=$redis->hget($session_id.':saved_designation_frequency', $designation_id);

                        if ($frequency!=5) {
                            $temp_desig=Designation::find($designation_id)->pluck('name');

                            $redis->hset($session_id.':messages', 'd'.$designation_id, 'Sponsorship for <strong>'.$temp_desig.'</strong> successfully added!');
                            //return array($frequency=>$sp_amount);
                            $redis->expire($session_id.':messages', 3600);

                            $this->postAddSponsorships($client_id, $designation_id, $donor->id, $sp_amount, $frequency, 2, $method);
                        }
                    }
                }
                if ($result==false) {
                    return redirect('frontend/orderD/'.$client_id.'/'.$hysform_id.'/'.$designation_id.'/'.$session_id)
                    ->withErrors($validator)
                    ->withInput();
                }
                        

                $this->deleteOrder($session_id, $redis);
                return redirect('frontend/donor_view/'.$client_id.'/none/'.$session_id);
            } else {
                    return redirect('frontend/orderD/'.$client_id.'/'.$hysform_id.'/'.$designation_id.'/'.$session_id)
                        ->withErrors($validator)
                        ->withInput();
            }
        }
        return redirect('frontend/orderD/'.$client_id.'/'.$hysform_id.'/'.$designation_id.'/'.$session_id)
            ->withErrors($validator)
            ->withInput();
    }

    

    public function postAddSponsorships($client_id, $entity_id, $donor_id, $sp_amount, $frequency, $type, $method, $session_id = null)
    {
            
        $donor = new Donor;
        $data = Input::all();
            
            
        if ($type==1) {
            $DonorEntity = new DonorEntity;
            $DonorEntity->donor_id = $donor_id;
            $DonorEntity->entity_id = $entity_id;
            $DonorEntity->client_id = $client_id;
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
            $commitment->last=Carbon::now();
            $commitment->save();
                
            if (isset($data['until'])) {
                $DonorEntity->until = $data['until'];
            }
            $DonorEntity->save();
                
            $donor->setStatus($entity_id);
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
            $commitment->last=Carbon::now();
            $commitment->save();
        }
    }


    /**
         * returns the designation whether it is an entity or an actual designation.
         *
         * @access public
         * @param mixed $type
         * @param mixed $designation_id
         * @param mixed $redis
         * @return void
         */
    public function getDesignation($type, $designation_id)
    {
        if ($type == 1) {
            $donor = new Donor;
            $entity = $donor->getEntityName($designation_id);
            $program = Program::find($entity['program_id']);
            $designation = ['name' => $entity['name'], 'emailset_id' => $program->emailset_id];
        } elseif ($type == 2) {
            $d = Designation::find($designation_id);
            $designation = ['code' => $d->code, 'name' => $d->name, 'emailset_id' => $d->emailset_id];
        }
        return $designation;
    }


    public function checkoutDesignationsOnly($client_id, $hysform_id, $designation_id, $session_id = null)
    {

        $redis = RedisL4::connection();

        //$program = Program::find($program_id);

        //$donor_id =$redis->hget($session_id,'donor_id');

        $d = new Donor;
            
        $donation= new Donation;

        //$stripe_id= $donation->getDonorStripeId($donor_id,$client_id);

        $hasStripe=false;
        if (isset($stripe_id)) {
            $hasStripe=true;
        }

        if ($session_id==null||$redis->exists($session_id)!=1) {
            $session_id=$this->createFrontendSession();
        }

        $redis->expire($session_id, 3600);
        $redis->expire($session_id.':saved_designations', 3600);
        $redis->expire($session_id.':saved_designation_frequency', 3600);

        
        $d_frequencies= $redis->hgetall($session_id.':saved_designation_frequency');

        $saved_designations=$redis->hgetall($session_id.':saved_designations');

        // return var_dump(Designation::find(key($saved_designations))->pluck('emailset_id'));
        // return var_dump($saved_designations);
            
        $signup_fields = Donorfield::where('client_id', $client_id)->where('hysform_id', $hysform_id)->where('permissions', '=', 'public')->orderBy('field_order')->get();


        //$frequency_options=$d->getFrequencies();
        $d_frequency_options=$d->getDesignationFrequencies();

        if ($designation_id=='all') {
            $desigs = Designation::whereClientId($client_id)->get();
        } else {
            $desigs= Designation::whereClientId($client_id)->where('id', $designation_id)->get();
        }

            
        //get designations, whether hysform is stored as an array or string
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
        //Checks for the client enabling Stripe in the program Settings
        $useStripe = $dntns->checkUseStripe($client_id);


        $total=0;
        $num_of_sponsorships=0;
        foreach ($saved_designations as $designation) {
            $num_of_sponsorships++;
            $total +=$designation;
        }

        $frequency_array = [];
        $frequency_text = [];

        foreach ($d_frequencies as $k => $f) {
            if (isset($frequency_array[$d_frequency_options[$f]])) {
                $frequency_array[$d_frequency_options[$f]]++;
            } else {
                $frequency_array[$d_frequency_options[$f]]=1;
            }
        }

        foreach ($frequency_array as $k => $f) {
            if (count($saved_designations)==1) {
                $frequency_text[]= 'paid '.$k;
            } else {
                $frequency_text[]= $f.' paid '.$k;
            }
        }

        if (count($frequency_text)==0) {
            $frequency_text= '';
        } elseif (count($frequency_text)==1) {
            $frequency_text=array_pop($frequency_text);
        } else {
            $last_element=array_pop($frequency_text);
            $second_to_last=array_pop($frequency_text);
            array_push($frequency_text, $second_to_last .' and '.$last_element);
            $frequency_text= implode(', ', $frequency_text);
        }

        $months = ['01'=>'01 - January','02'=>'02 - February','03'=>'03 - March','04'=>'04 - April','05'=>'05 - May','06'=>'06 - June','07'=>'07 - July','08'=>'08 - August','09'=>'09 - September','10'=>'10 - October','11'=>'11 - November','12'=>'12 - December'];

        $today=Carbon::now();
        $i = 0;
        while ($i<10) {
            $years[$today->year+$i] = $today->year+$i;
            $i++;
        }


        $program_id=null;
        $entities=[];
        $amount_permissions=[];
        $text_checkout= '';
        $profilePics=[];
        $titles=[];
        $frequencies=[];
        $frequency_options= [];
        $vars=['symbol' => '$'];
        $login_box='1';
        $checks='';
        $designations_allowed='1';
        $stripe_id=null;


        $designation= Designation::find($designation_id);

        $donation_amounts_array=[];

        if (!empty($designation->donation_amounts)) {
            foreach (explode(',', $designation->donation_amounts) as $amount) {
                $donation_amounts_array[$amount]= '$' .$amount;
            }
        }


        return view('frontend.views.checkOutDesignationOnly', [
            'session_id'        => $session_id,
            'client_id'         => $client_id,
            'program_id'        => $program_id,
            'hysform_id'        => $hysform_id,
            'designation_id'    => $designation_id,
            'entities'          => $entities,
            'designations'      => $designations,
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
            'useStripe'         => $useStripe,
            'hasStripe'         => $hasStripe,
            'designation'       => $designation,
            'saved_designations'=> $saved_designations,
            'total'             => $total,
            'login_box'         => $login_box,
            'checks'            => $checks,
            'num_of_sponsorships'   => $num_of_sponsorships,
            'designations_allowed'  => $designations_allowed,
            'frequency_text'    => $frequency_text,
            'useCC' =>          $useCC = true,
            'months'    =>$months,
            'years' =>$years,
            'donation_amounts_array'=>$donation_amounts_array,

            ]);
    }
}
