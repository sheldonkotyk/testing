<?php namespace App\Controllers\Donor;
 
    use Auth;
use BaseController;
use Form;
use Input;
use Redirect;
use Sentry;
use View;
use Session;
use Validator;
use Donor;
use Hash;
use RedisL4;
use DateTime;
use Entity;
use Emailtemplate;
use Program;
use URL;
use Mail;
use Client;
use Setting;
 
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
 
   //Makes a session for storing the current logged in Donor. Sends the session
    function createDonorSession($session_id, $donor_id)
    {
            
        $redis = RedisL4::connection();
        $redis->del($session_id);
        $time = time(); // get current unix time
        $session_id = sha1($time . 'EVERSP'); // encrypt and salt it for fun

        $donor= Donor::find($donor_id);

        $donor->last_login= new DateTime;
        $donor->save();
        //Send Session Info to Redis
        $redis->hset($session_id, 'logged_in', 'true');
        $redis->hset($session_id, 'donor_id', $donor_id);

        //Allows Donor to stay logged in for one hour
        $redis->expire($session_id, 3600);

        return $session_id;
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

   
 
    public function getLogin($client_id, $program_id, $session_id = null)
    {

        $redis = RedisL4::connection();

        if ($session_id== null) {
            $session_id=$this->createFrontendSession();
        }

        $t_program= new Program;

        $program_ids=$t_program->getPrograms($client_id, $program_id);

        $program= Program::where('client_id', $client_id)->find($program_ids[0]);

        $currency_symbol= '$';
           

        if ($program==null) {
            $disable_program_link='';
        } else {
            $program_settings = (array) json_decode(Setting::find($program->setting_id)->program_settings);

            $disable_program_link='';
            if (isset($program_settings['disable_program_link'])) {
                $disable_program_link=$program_settings['disable_program_link'];
            }
             $currency_symbol= $program_settings['currency_symbol'];
        }
        $e = new Entity;
        $total = $e->getTotal($session_id);

        return view('frontend.views.DonorLogin', [
            'session_id'    => $session_id,
            'client_id'     => $client_id,
            'program_id'    => $program_id,
            'disable_program_link' =>$disable_program_link,
            'login' => '',
            'currency_symbol'=>$currency_symbol,
            'total'     => $total,
            ]);
    }

    public function resetPassword($client_id, $program_id, $session_id = null)
    {

        $redis = RedisL4::connection();

        if ($session_id== null) {
            $session_id=$this->createFrontendSession();
        }


        return view('frontend.views.resetPassword', [
            'session_id'    => $session_id,
            'client_id'     => $client_id,
            'program_id'    => $program_id
            ]);
    }

    public function forgotUsername($client_id, $program_id, $session_id = null)
    {

        $redis = RedisL4::connection();

        if ($session_id== null) {
            $session_id=$this->createFrontendSession();
        }

        return view('frontend.views.forgotUsername', [
        'session_id'    => $session_id,
        'client_id'     => $client_id,
        'program_id'    => $program_id
        ]);
    }


    public function postForgotUsername($client_id, $program_id, $session_id = null)
    {

        $redis = RedisL4::connection();

        if ($session_id== null) {
            $session_id=$this->createFrontendSession();
        }

        $data= Input::all();
        unset($data['_token']);
        $rules=['email'=>'required|email|exists:donors'];

        $validator = Validator::make($data, $rules);

        if ($validator->passes()) {
            $donor = Donor::where('email', '=', $data['email'])->where('client_id', '=', $client_id)->get()->first();

            if (!isset($donor)) {
                $redis->hset($session_id.':messages', 'error', 'Error: Account Does Not Exist');
                $redis->expire($session_id.':messages', 3600);
                return redirect('frontend/forgot_username/'.$client_id.'/'.$program_id.'/'.$session_id)
                    ->withErrors($validator);
            } else {
                $data['email'] = $donor->email;
                $data['username'] = $donor->username;
                $data['login_link']=URL::to('frontend/login', [$client_id,$program_id]);
                $client= Client::find($client_id);
                if (count($client)) {
                    $data['organization']=$client->organization;
                    $data['website'] = $client->website;
                } else {
                    $data['organzation'] = 'HelpYouSponsor';
                    $data['website'] = 'http://helpyousponsor.com';
                }

                $program= new Program;
                $program_ids= $program->getPrograms($client_id, $program_id);

                $program= Program::find($program_ids[0]);

                if ($program_ids[0]=='none') {
                    $program=Program::whereClientId($client_id)->where('emailset_id', '!=', '0')->first();
                }

                if (!empty($program)) {
                    $e = new Entity;

                    $the_donor = $e->getDonorName($donor->id);
                    //Email the donor and give them their auto generated temporary password
                    $data['username']=$donor->username;

                    if (!empty($the_donor['name'])&&$the_donor['name']!='No Name Found') {
                        $data['name'] = $the_donor['name'];
                    }

                    // $sent = $emailtemplate->sendEmail($program->emailset_id, $details, 'notify_donor', $to);
                    $sent=false;
                    try {
                        Mail::send('emails.forgotUsername', $data, function ($message) use ($data) {
                            $message->to($data['email'], $data['name'])->subject('Forgot Username');
                        });
                        $sent=true;
                    } catch (Exception $e) {
                         echo 'Caught exception: ',  $e->getMessage(), "\n";

                         $redis->hset($session_id.':messages', 'error', 'Error: Email sending failure. '. $e->getMessage());
                         $redis->expire($session_id.':messages', 3600);
                         $sent=false;
                    }

                    if ($sent==false) {
                        $redis->hset($session_id.':messages', 'error', 'Error: Email sending failure.');
                        $redis->expire($session_id.':messages', 3600);
                        return redirect('frontend/forgot_username/'.$client_id.'/'.$program_id.'/'.$session_id)
                            ->withErrors($validator);
                    }
                        

                    $redis=RedisL4::connection();
                    $redis->hset($session_id.':messages', 'success', 'Your temporary password has been emailed to you at '.$donor->email.'. Check your email.');
                    $redis->expire($session_id.':messages', 3600);
                    return redirect('frontend/login/'.$client_id.'/'.$program_id.'/'.$session_id)
                        ->withErrors($validator)
                        ->withInput();
                } else {
                    return "Error: No program is connected to this donor form, therefore no email can be sent.";
                }
            }
        } else {
            return redirect('frontend/forgot_username/'.$client_id.'/'.$program_id.'/'.$session_id)
                    ->withErrors($validator);
        }
    }
       

    public function rand_string($length)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        return substr(str_shuffle($chars), 0, $length);
    }

    public function postResetPassword($client_id, $program_id, $session_id = null)
    {

        $redis = RedisL4::connection();

        if ($session_id== null) {
            $session_id=$this->createFrontendSession();
        }
        $method='';
        $data = Input::all();
        unset($data['_token']);

        if (isset($data['username'])&&!isset($data['email'])) {
            $method='username';
            $rules = [
                'username' => 'exists:donors,username',
            ];
        }
        if (!isset($data['username'])&&isset($data['email'])) {
            $method='email';
            $rules = [
                'email' => 'email|exists:donors'
            ];
        }

        if (!isset($data['username'])&&!isset($data['email'])) {
            $redis=RedisL4::connection();
            $redis->hset($session_id.':messages', 'error', 'Error: You must type in either your username or your email address');
            $redis->expire($session_id.':messages', 3600);
            return redirect('frontend/reset_password/'.$client_id.'/'.$program_id.'/'.$session_id)
                ->withInput();
        }

        if (isset($data['username'])&&isset($data['email'])) {
            $method='username';
            $rules = [
                'username' => 'exists:donors,username',
            ];

            $prevalidator= Validator::make($data, $rules);
            if ($prevalidator->passes()) {
            } else {
                $method='email';
                $rules = [
                'email' => 'email|exists:donors'
                ];
            }
        }


           
        $validator = Validator::make($data, $rules);

        if ($validator->passes()) {
            if ($method=='username') {
                $donor = Donor::where('username', '=', $data['username'])->where('client_id', '=', $client_id)->get()->first();
            }

            if ($method=='email') {
                $donor = Donor::where('email', '=', $data['email'])->where('client_id', '=', $client_id)->get()->first();
            }

            if (!isset($donor)) {
                $redis->hset($session_id.':messages', 'error', 'Error: Account Does Not Exist');
                $redis->expire($session_id.':messages', 3600);
                return redirect('frontend/reset_password/'.$client_id.'/'.$program_id.'/'.$session_id)
                    ->withErrors($validator);
            } else {
                $data['password']=$this->rand_string(8);
                $data['email'] = $donor->email;
                $data['username'] = $donor->username;
                $data['login_link']=URL::to('frontend/login', [$client_id,$program_id]);
                $data['forgot_username_link'] =URL::to('frontend/forgot_username', [$client_id,$program_id]);
                $client= Client::find($client_id);
                if (count($client)) {
                    $data['organization']=$client->organization;
                    $data['website'] = $client->website;
                } else {
                    $data['organzation'] = 'HelpYouSponsor';
                    $data['website'] = 'http://helpyousponsor.com';
                }

                $donor->password= Hash::make($data['password']);
                $donor->save();

                $program= new Program;
                $program_ids= $program->getPrograms($client_id, $program_id);

                $program= Program::find($program_ids[0]);

                if ($program_ids[0]=='none') {
                    $program=Program::whereClientId($client_id)->where('emailset_id', '!=', '0')->first();
                }

                if (!empty($program)) {
                    $e = new Entity;
                        

                    $the_donor = $e->getDonorName($donor->id);
                    //Email the donor and give them their auto generated temporary password
                    $data['username']=$donor->username;

                    if (!empty($the_donor['name'])&&$the_donor['name']!='No Name Found') {
                        $data['name'] = $the_donor['name'];
                    }

                    // $sent = $emailtemplate->sendEmail($program->emailset_id, $details, 'notify_donor', $to);
                    $sent=false;
                    try {
                        Mail::send('emails.passwordReset', $data, function ($message) use ($data) {
                            $message->to($data['email'], $data['name'])->subject('Password Reset');
                        });
                        $sent=true;
                    } catch (Exception $e) {
                         echo 'Caught exception: ',  $e->getMessage(), "\n";

                         $redis->hset($session_id.':messages', 'error', 'Error: Email sending failure. '. $e->getMessage());
                         $redis->expire($session_id.':messages', 3600);
                         $sent=false;
                    }

                    if ($sent==true) {
                         $donor->password= HASH::make($data['password']);
                         $donor->save();
                         unset($data['password']);
                    } else {
                        $redis->hset($session_id.':messages', 'error', 'Error: Email sending failure.');
                        $redis->expire($session_id.':messages', 3600);
                        return redirect('frontend/reset_password/'.$client_id.'/'.$program_id.'/'.$session_id)
                            ->withErrors($validator);
                    }
                        

                    $redis=RedisL4::connection();
                    $redis->hset($session_id.':messages', 'success', 'Your temporary password has been emailed to you at '.$donor->email.'. Check your email.');
                    $redis->expire($session_id.':messages', 3600);
                    return redirect('frontend/login/'.$client_id.'/'.$program_id.'/'.$session_id)
                        ->withErrors($validator)
                        ->withInput();
                } else {
                    return "No program, therefore no email.";
                }
            }
        } else {
            return redirect('frontend/reset_password/'.$client_id.'/'.$program_id.'/'.$session_id)
                ->withErrors($validator);
        }
    }
 
    public function postLogin($client_id, $program_id, $session_id = null)
    {
            
        $data = Input::all();
        unset($data['_token']);
            

        $rules = [
            'username' => 'required|exists:donors,username,client_id,'.$client_id,
            'password' => 'required'
        ];
        $password=trim($data['password']);
        $username=trim($data['username']);
           
        $validator = Validator::make($data, $rules);
            
        if ($validator->passes()) {
            unset($data['password']);
                

            $donor = Donor::where('username', '=', $username)->get()->first();

                

            if (Hash::check($password, $donor->password)) {
                $session_id=$this->createDonorSession($session_id, $donor->id);
                $redis=RedisL4::connection();
                $redis->hset($session_id.':messages', '1', "You have successfully logged in!");
                $redis->expire($session_id.':messages', 3600);

                return redirect('frontend/donor_view/'.$client_id.'/'.$program_id.'/'.$session_id);
            } else {
                $redis= RedisL4::connection();
                $redis->hset($session_id.':messages', 'error', 'Error: Invalid Username or Password');
                $redis->expire($session_id.':messages', 3600);
                return redirect('frontend/login/'.$client_id.'/'.$program_id.'/'.$session_id)
                    ->withErrors($validator)
                    ->withInput();
            }
        }

         $donor = Donor::where('username', '=', $username)->get()->first();

        if ($donor===null) {
            $redis=RedisL4::connection();
            $redis->hset($session_id.':messages', 'error', 'Error: Invalid Username or Password');
            $redis->expire($session_id.':messages', 3600);
            return redirect('frontend/login/'.$client_id.'/'.$program_id.'/'.$session_id)
            ->withErrors($validator)
            ->withInput();
        }

        return redirect('frontend/login/'.$client_id.'/'.$program_id)
            ->withErrors($validator)
            ->withInput();
    }
 
    public function postSignup($client_id, $program_id)
    {
        $program = Program::find($program_id);

        $hysform_id = $program->donor_hysform_id;
            
        $data = Input::all();
        unset($data['_token']);
            
        $rules = [
            'username' => 'required|unique:donors|min:5',
            'email' => 'required|email|required|unique:donors,email,NULL,id,client_id,'.$client_id,
            'password' => 'required|min:5'
        ];
           
        $validator = Validator::make($data, $rules);
            
        if ($validator->passes()) {
            $password = Hash::make($data['password']);
            unset($data['password']);
                
            $donor = new Donor;
            $donor->client_id = $client_id;
            $donor->hysform_id = $hysform_id;
            $donor->username = trim($data['username']);
            $donor->email = trim($data['email']);
            $donor->password = $password;
            $donor->who_added= json_encode(['type'=>'donor','method'=>'individual','id'=>'']);
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
            

            $to=['type' => 'admin','email' => '' ,'name' => 'admin'];
            
            $details['donor'] = $email->getDonor($donor->id);
            $details['other'] = ['donor_email' => $donor->email];
            
            $adminEmailSent = $email->sendEmail($program->emailset_id, $details, 'new_donor_admin', $to);

            if ($adminEmailSent) {
                $redis = RedisL4::connection();
                $redis->hset($session_id.':messages', 'Admin Email Sent!');
                $redis->expire($session_id.':messages', 3600);
            }

            // need error handling
            
            $redis = RedisL4::connection();
            $redis->hmset($hash, $profile);

            return redirect('frontend/donor_view/'.$client_id.'/'.$program_id.'/'.$session_id)
                ->withInput();
        }
        return redirect('frontend/signup_donor/'.$client_id.'/'.$program_id)
            ->withErrors($validator)
            ->withInput();
    }

    public function getLogout($client_id, $program_id, $session_id)
    {
        $redis= RedisL4::connection();

        if ($redis->exists($session_id)!=1) {
            return redirect('/frontend/login/'.$client_id.'/'.$program_id);
        }


        $redis->del($session_id.':donor_id');
        $redis->del($session_id.':saved_entity_id');
        $redis->del($session_id.':saved_entity_frequency');
        $redis->del($session_id.':saved_designations');
        $redis->del($session_id.':saved_designation_frequency');
        $redis->del($session_id.':logged_in');
        $redis->del($session_id.':messages');
        $redis->del($session_id.':view_all');
        $redis->del($session_id);


           
        $new_session=$this->createFrontendSession();
        $redis->hset($new_session.':messages', '1', "You have successfully logged out.");
        $redis->expire($session_id.':messages', 3600);



        return Redirect::route('donor.login', ([$client_id,$program_id,$new_session]));
    }
}
