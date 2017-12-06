<?php  namespace App\Controllers\Admin;
 
    use Auth;
use BaseController;
use Form;
use Input;
use Redirect;
use Sentry;
use View;
use MailchimpWrapper ;
use RedisL4;
use Validator;
use Session;
use DB;
use Hash;
use Response;
use Mail;
use Emailtemplate;
use Client;
use Gateway;
use Omnipay\Common\GatewayFactory;
use Config;
use Emailsetting;
use Donation;
use Group;
    
use App\Http\Controllers\Controller;

class ClientController extends Controller
{
    
    public function editClientAccount()
    {
        $client_id = Session::get('client_id');
        $client = Client::find($client_id);
        $stripe_gateway = Gateway::whereClientId($client_id)->where('gateway', 'Stripe')->get();
        $authorize_gateway = Gateway::whereClientId($client_id)->where('gateway', 'AuthorizeNet')->get();
        $emailsettings = Emailsetting::whereClientId($client_id)->first();
            
        $s_gs = [];
        $a_gs = [];
        foreach ($stripe_gateway as $g) {
            $s_gs = ['id' => $g->id, 'gateway' => $g->gateway, 'settings' => json_decode($g->settings, true)];
        }
        foreach ($authorize_gateway as $g) {
            $a_gs = ['id' => $g->id, 'gateway' => $g->gateway, 'settings' => json_decode($g->settings, true)];
        }

        $donation= new Donation;

        $mailgun='true';
        if (!empty($emailsettings)) {
            if (!empty($emailsettings->username)&&!empty($emailsettings->password)&&!empty($emailsettings->host)) {
                $mailgun='false';
            }
        }

        if (empty($emailsettings->mailchimp_api)) {
            $lists = [];
        } else {
            Config::set('mailchimp::apikey', $emailsettings->mailchimp_api);
            $lists = MailchimpWrapper::lists()->getList()['data'];
        }

        return view('admin.views.editClientAccount', [
            'client' => $client,
            'stripe_gateway' => $s_gs,
            'authorize_gateway' => $a_gs,
            'emailsettings' => $emailsettings,
            'mailgun'   => $mailgun,
            'donation' => $donation,
            'lists' => $lists
        ]);
    }
        
    public function postEditClientAccount()
    {
        $data = Input::all();
            
        $rules = [
            'organization' => 'required|min:3',
            'website' => 'required|url',
            'email' => 'required'
        ];
            
        $validator = Validator::make($data, $rules);
        
        if ($validator->passes()) {
            // Find the client
            $client = Client::find(Session::get('client_id'));
            $client->organization = $data['organization'];
            $client->email = $data['email'];
            $client->website = $data['website'];

            //When the Box Client id or secret is changed, we remove the access and refresh tokens.
            if (isset($data['box_client_id'])) {
                $client->box_access_token='';
                $client->box_refresh_token='';
                $client->box_client_id=$data['box_client_id'];
            }
            if (isset($data['box_client_secret'])) {
                $client->box_access_token='';
                $client->box_refresh_token='';
                $client->box_client_secret=$data['box_client_secret'];
            }

            if (isset($data['arb_enabled'])) {
                $client->arb_enabled='1';
            } else {
                $client->arb_enabled='';
            }

            $client->save();
                
            if (isset($data['stripe_gateway_id'])) {
                $s_gateway = Gateway::find($data['stripe_gateway_id']);
            } else {
                $s_gateway = new Gateway;
                $s_gateway->client_id = Session::get('client_id');
            }
            if (isset($data['authorize_gateway_id'])) {
                $a_gateway = Gateway::find($data['authorize_gateway_id']);
            } else {
                $a_gateway = new Gateway;
                $a_gateway->client_id = Session::get('client_id');
            }


            if (!empty($data['stripe'])) {
                $old_settings=json_decode($s_gateway->settings);

                if (isset($old_settings->StripeApiKey)&&$old_settings->StripeApiKey==$data['stripe']) {
                //no change to key
                } else {
                    $s_gateway->gateway = 'Stripe';
                    $s_gateway->settings = json_encode(['StripeApiKey' => $data['stripe']]);
                    $s_gateway->default=1;
                    $a_gateway->default=0;

                    $s_gateway->save();
                    $a_gateway->save();
                }
            }
            if ($s_gateway->gateway=='Stripe'&&empty($data['stripe'])) {
                $s_gateway->forceDelete();

                return redirect('admin/edit_client_account')
                ->with('message', 'Stripe account successfully removed')
                ->with('alert', 'success');
            }


            if (!empty($data['login_api_key'])) {
                //Authorize config info goes here!

                $old_settings=json_decode($a_gateway->settings);

                if (isset($old_settings->ApiLoginId)&&isset($old_settings->TransactionKey)&&$old_settings->ApiLoginId==$data['login_api_key']&&$old_settings->TransactionKey==$data['transaction_api_key']) {
                //no change to key
                } else {
                    $a_gateway->gateway = 'AuthorizeNet';
                    $a_gateway->settings = json_encode([
                        'ApiLoginId' => $data['login_api_key'],
                        'TransactionKey' => $data['transaction_api_key']]);
                    $s_gateway->default=0;
                    $a_gateway->default=1;

                    $s_gateway->save();
                    $a_gateway->save();
                }
            }

            if ($a_gateway->gateway=='AuthorizeNet'&&empty($data['login_api_key'])) {
                $a_gateway->forceDelete();
                return redirect('admin/edit_client_account')
                ->with('message', 'Authorize account successfully removed')
                ->with('alert', 'success');
            }
                
            return redirect('admin/edit_client_account')
                ->with('message', 'Account successfully updated')
                ->with('alert', 'success');
        }
            
        return redirect('admin/edit_client_account')
            ->withErrors($validator)
            ->withInput();
    }
        
    public function postEmailSettings()
    {
        $data = Input::all();
            

        $test_mail=false;
        $rules = [
            'host' => 'required',
            'from_address' => 'required|email',
            'from_name' => 'required',
            'username' => 'required',
            'password' => 'required'
        ];
        $validator = Validator::make($data, $rules);

        $passed= $validator->passes();
        if (empty($data['host'])&&empty($data['username'])&&empty($data['password'])&&empty($data['api'])&&!empty($data['from_address'])&&!empty($data['from_name'])) {
            $passed= true;
        }

        if ($passed) {
            $emailsettings = Emailsetting::whereClientId(Session::get('client_id'))->first();

            if (!$emailsettings) {
                 $emailsettings = new Emailsetting;
            }
            $emailsettings->api= $data['api'];
            $emailsettings->domain= substr(strrchr($data['username'], "@"), 1);
            if ($emailsettings->domain==0) {
                $emailsettings->domain='';
            }

            $emailsettings->mailchimp_api=$data['mailchimp_api'];
            if (!empty($emailsettings->mailchimp_api)) {
                try {
                    Config::set('mailchimp::apikey', $emailsettings->mailchimp_api);
                    $lists = MailchimpWrapper::lists()->getList()['data'];
                } catch (\Exception $e) {
                    return redirect('admin/edit_client_account')
                    ->with('message', 'Your Mailchimp API key is invalid.')
                    ->with('alert', 'danger');
                    $emailsettings->mailchimp_api='';
                    $emailsettings->save();
                }
                // return $lists;
            }


                
            $emailsettings->client_id = Session::get('client_id');
            $emailsettings->host = rtrim($data['host'], "/");
            $emailsettings->from_address = $data['from_address'];
            $emailsettings->from_name = $data['from_name'];
            $emailsettings->username = trim($data['username']);
            $emailsettings->password = trim($data['password']);
            $emailsettings->save();

            try {
                Config::set('emailsetting', $emailsettings);

                $test_mail= Mail::send('emails.testMailGun', $data, function ($message) use ($emailsettings) {
                    $message->to($emailsettings->from_address, $emailsettings->from_name)
                        ->from($emailsettings->from_address, $emailsettings->from_name)
                        ->subject('Success! Your Email settings work.');
                });
            } catch (\Exception $e) {
                //If the test email sending fails.
                // $emailsettings->delete();
                return redirect('admin/edit_client_account')
                ->with('message', 'Your email settings were unable to send a test email. Error message:'.$e)
                ->with('alert', 'danger');
            }

            if ($test_mail==0) {
                //If the test email sending fails.
                $emailsettings->delete();
                // return var_dump($test_mail);

                return redirect('admin/edit_client_account')
                ->with('message', 'Error: Your email settings were unable to send a test email.')
                ->with('alert', 'danger');
            }

             // return var_dump( $test_mail);

            return redirect('admin/edit_client_account')
                ->with('message', 'Note:<br/>Email Settings Successfully Saved. <br/>You should receive an email at: '. $emailsettings->from_address .' confirming your email settings setup. <br/>If you don\'t recieve this email, your settings don\'t work')
                ->with('alert', 'info');
        }
            
        return redirect('admin/edit_client_account')
            ->withErrors($validator)
            ->with('message', 'There was a problem with your submission. See below for details.')
            ->with('alert', 'danger')
            ->withInput();
    }
                
    public function updateClientCC()
    {
            
        $client = Client::find(Session::get('client_id'));

        return view('admin.views.addCC', ['client'=>$client]);
    }

    public function emailSettings()
    {
        $client_id = Session::get('client_id');
        $client = Client::find($client_id);
        $emailsettings = Emailsetting::whereClientId($client_id)->first();
            
        $mailgun='true';
        if (!empty($emailsettings)) {
            if (!empty($emailsettings->username)&&!empty($emailsettings->password)&&!empty($emailsettings->host)) {
                $mailgun='false';
            }
        }

        if (empty($emailsettings->mailchimp_api)) {
            $lists = [];
        } else {
            Config::set('mailchimp::apikey', $emailsettings->mailchimp_api);
            $lists = MailchimpWrapper::lists()->getList()['data'];
        }

        return view('admin.views.emailSettings', [
            'client' => $client,
            'emailsettings' => $emailsettings,
            'mailgun'   => $mailgun,
            'lists' => $lists
        ]);
    }

        
        
    public function postUpdateClientCC()
    {
        $data = Input::all();
        $client = Client::find(Session::get('client_id'));
        $params = [];
            
        $rules = [
            'firstName' => 'required',
            'lastName' => 'required',
            'number' => 'required|creditcard',
            'cvv' => 'required',
            'expiryMonth' => 'required',
            'expiryYear' => 'required'
        ];
            
        $validator = Validator::make($data, $rules);
        
        if ($validator->passes()) {
            try {
                $card = [
                    'firstName' => $data['firstName'],
                    'lastName' => $data['lastName'],
                    'number' => $data['number'],
                    'cvv' => $data['cvv'],
                    'expiryMonth' => $data['expiryMonth'],
                    'expiryYear' => $data['expiryYear']
                ];
            
                $params['description'] = $client->organization;
        
                $gateway_factory = new GatewayFactory;
                $gateway = $gateway_factory->create('Stripe');
                $gateway->setApiKey(config('stripe_secret_key'));
                    
                $params['card'] = $card;
                    
                if (!empty($client->stripe_cust_id)) {
                    $params['cardReference'] = $client->stripe_cust_id;
                    $response = $gateway->updateCard($params)->send();
                } else {
                    $response = $gateway->createCard($params)->send();
                }
                
                if ($response->isSuccessful()) {
                // customer create was successful: update database
                    $client->stripe_cust_id = $response->getCardReference();
                    $client->save();
                    $message = "Card saved successfully.";
                    $alert = "success";
                } else {
                // payment failed: display message to customer
                    $message = $response->getMessage();
                    $alert = 'danger';
                }
            } catch (\Exception $e) {
                $message = $e->getMessage();
                $alert = 'danger';
            }
                
            if ($alert == 'success') {
                return redirect('admin/edit_client_account')
                    ->with('message', $message)
                    ->with('alert', $alert);
            } else {
                return redirect('admin/update_client_cc')
                ->with('message', $message)
                ->with('alert', $alert);
            }
        }
        return redirect('admin/update_client_cc')
            ->withErrors($validator)
            ->withInput()
            ->with('message', 'There was a problem with your submission. Please see below for details.')
            ->with('alert', 'danger');
    }
        
    public function switchClient()
    {
        $data=Input::all();



        // return var_dump($data);
        $vars=explode(',', $data['users']);

        $user=Sentry::getUser();

        if ($user->id!='1') {
            return Redirect::back();
        }
        // return var_dump(Session::all());

        if (isset($vars[0])&&isset($vars[1])) {
            $client_id=$vars[0];
            $group_id=$vars[1];

            // add client_id to session
                Session::put('client_id', $client_id);
                    
                // get group permissions and add to session
                $group = Group::find($group_id);
            if (empty($group)) {
                Sentry::logout();
                return Redirect::route('admin.login')
                ->with('message', 'This Administrator needs to be placed in a group before they can log in!')
                ->with('alert', 'danger');
            }
                Session::put('permissions', json_decode($group->permissions));

                $emailsetting = Emailsetting::where('client_id', $client_id)->first();

                Config::set('emailsetting', $emailsetting);

                Session::put('emulating', "Emulating Client: ".Client::find($client_id)->organization ." with Group: ".$group->name);

            return Redirect::back()->with('message', 'Client and Group id successfully switched.')->with('alert', 'success');
        }
    }


    public function mailgunLogsData()
    {

        $client_id = Session::get('client_id');
        $client= Client::find($client_id);
        $emailsetting = Emailsetting::where('client_id', $client_id)->first();

        if (empty($emailsetting->domain)) {
            $emailsetting->domain= substr(strrchr($emailsetting->username, "@"), 1);
            $emailsetting->save();
        }

        $ch= curl_init();
        $url = "https://api.mailgun.net/v3/".rtrim($emailsetting->domain, "/")."/log";
        

        curl_setopt($ch, CURLOPT_URL, $url);
 
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
         
        curl_setopt($ch, CURLOPT_USERPWD, 'api:'.$emailsetting->api);
         
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
         
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
         
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
         
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
             

        $result = curl_exec($ch);

        if (empty($result)) {
            return json_encode(['data'=> []]);
        }

        curl_close($ch);

        $d_result= json_decode($result);

        $processed = [];

        foreach ($d_result->items as $k => $r) {
            foreach ($r as $each) {
                $processed[$k][]=$each;
            }
        }

        return json_encode(['data'=>$processed]);
    }

    public function mailgunLogs()
    {

        $client_id = Session::get('client_id');
        $client= Client::find($client_id);
        $emailsetting = Emailsetting::where('client_id', $client_id)->first();

        if (empty($emailsetting)) {
            return redirect('admin/edit_client_account')->with('message', 'Error: Email Settings must be entered on this page to view logs.')->with('alert', 'warning');
        }

        if (empty($emailsetting->api)) {
            return redirect('admin/edit_client_account')->with('message', 'Error: Mailgun Api must be entered on this page to view logs.')->with('alert', 'warning');
        }

        return view('admin.views.mailgunLogs', [
            'client' => $client,
            'emailsetting' => $emailsetting,
        ]);
    }
}
