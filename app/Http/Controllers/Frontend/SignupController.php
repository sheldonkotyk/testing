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
use Omnipay\Common\GatewayFactory;
use Config;
 
use App\Http\Controllers\Controller;

class SignupController extends Controller
{
    
    public function signUp()
    {
        return view('frontend.views.signup');
    }
        
    public function postSignUp()
    {
        $data = Input::all();
            
        $rules = [
            'organization' => 'required|min:3',
            'website' => 'required|url',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:7',
            'first_name' => 'required',
            'last_name' => 'required',
            'number' => 'required|creditcard',
            'cvc' => 'required',
            'expiryMonth' => 'required',
            'expiryYear' => 'required'
        ];
            
        $validator = Validator::make($data, $rules);
        
        if ($validator->passes()) {
            try {
                try {
                    $card = [
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'number' => $data['number'],
                        'cvv' => $data['cvc'],
                        'expiryMonth' => $data['expiryMonth'],
                        'expiryYear' => $data['expiryYear']
                    ];
                    
                    $params = ['description' => $data['organization']];
                    $gatewayFactory= new GatewayFactory;
                    $gateway = $gatewayFactory->create('Stripe');
                    $gateway->setApiKey(config('stripe_secret_key'));
                    $params['card'] = $card;
                    $params['description'] = $data['organization'];
                    $response = $gateway->createCard($params)->send();
                        
                    if ($response->isSuccessful()) {
                    // customer create was successful: update database
                        $stripe_cust_id = $response->getCardReference();
                    } else {
                    // payment failed: display message to customer
                        return redirect('signup')
                        ->withInput()
                        ->with('message', $response->getMessage())
                        ->with('alert', 'danger');
                    }
                } catch (\Exception $e) {
                    return redirect('signup')
                        ->withInput()
                        ->with('message', $e->getMessage())
                        ->with('alert', 'danger');
                }
                    
                // Create the client
                $client = new Client;
                $client->organization = $data['organization'];
                $client->website = $data['website'];
                $client->email = $data['email'];
                $client->stripe_cust_id = $stripe_cust_id;
                $client->save();
                    
                // Create the Program base
                $program = new Program(['name' => $data['organization'], 'client_id' => $client->id]);
                $program->makeRoot();
                    
                $permissions = ["account" => "1", "groups" => "1", "group_all" => "1", "admins" => "1","forms" => "1","new_form" => "1","manage_programs" => "1","manage_settings" => "1","manage_email" => "1"];
                    
                // Create the group
                $group = new Group;
                $group->name = $data['organization'];
                $group->client_id = $client->id;
                $group->permissions = json_encode($permissions);
                $group->save();
                    
                // Create the user
                $user = Sentry::register([
                    'email'    => $data['email'],
                    'password' => $data['password'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'client_id' => $client->id,
                    'group_id' => $group->id
                ]);
                    
                // create all the default data
                $client->programInABox($client->id);
                    
                $activationCode = $user->getActivationCode();
                    
                $data['email'] = $user->email;
                $data['id'] = $user->id;
                $data['activationCode'] = $activationCode;
                    
                Mail::queue('emails.activate', $data, function ($message) use ($user) {
                    $message->to($user->email, 'New User')->subject('Activation Code');
                });
                    
                //create default data set
                    
                return redirect('login')
                    ->with('message', 'Your account has been created. Please check your email for your account activation link. Once your account has been activated you will be able to log in. If you don\'t see your activation email in the next few minutes please check your spam folder. If you still can not find it please contact us at support@helpyousponsor.com and we will manually activate your account.')
                    ->with('alert', 'success');
            } catch (\Cartalyst\Sentry\Users\LoginRequiredException $e) {
                $message = 'Email field is required.';
            } catch (\Cartalyst\Sentry\Users\PasswordRequiredException $e) {
                $message = 'Password field is required.';
            } catch (\Cartalyst\Sentry\Users\UserExistsException $e) {
                $message = 'User with this email already exists.';
            } catch (\Cartalyst\Sentry\Groups\GroupNotFoundException $e) {
                $message = 'Group was not found.';
            }
        
            return redirect('signup')
                ->withInput()
                ->with('message', $message)
                ->with('alert', 'danger');
        }
            
        return redirect('signup')
            ->withErrors($validator)
            ->withInput()
            ->with('message', 'There was a problem with your submission. Please see below for details')
            ->with('alert', 'danger');
    }
}
