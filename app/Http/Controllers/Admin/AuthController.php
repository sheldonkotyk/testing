<?php namespace App\Controllers\Admin;
 
    use Auth;
use BaseController;
use Form;
use Input;
use Redirect;
use Sentry;
use View;
use Session;
use Group;
use Emailsetting;
use Config;
use Donor;
use URL;
 
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
 
    public function getLogin()
    {
        return view('frontend.views.login');
    }
 
    public function postLogin()
    {
        $credentials = [
            'email'    => trim(Input::get('email')),
            'password' => trim(Input::get('password'))
        ];
             
        try {
            $user = Sentry::authenticate($credentials, false);
 
            if ($user) {
                // add client_id to session
                Session::put('client_id', $user->client_id);
                    
                // get group permissions and add to session
                $group = Group::find($user->group_id);
                if (empty($group)) {
                    Sentry::logout();
                    return Redirect::route('admin.login')
                        ->with('message', 'This Administrator needs to be placed in a group before they can log in!')
                        ->with('alert', 'danger');
                }
                Session::put('permissions', json_decode($group->permissions));
                    

                $emailsetting = Emailsetting::where('client_id', $user->client_id)->first();
                Config::set('emailsetting', $emailsetting);
                    
                return Redirect::intended('admin');
            }
        } catch (\Cartalyst\Sentry\Users\LoginRequiredException $e) {
            $message = 'Login field is required.';
            $alert = 'danger';
        } catch (\Cartalyst\Sentry\Users\PasswordRequiredException $e) {
            $message = 'Password field is required.';
            $alert = 'danger';
        } catch (\Cartalyst\Sentry\Users\WrongPasswordException $e) {
            $message = 'Wrong password, try again.';
            $alert = 'danger';
        } catch (\Cartalyst\Sentry\Users\UserNotFoundException $e) {
            $add= '';
            $donor = Donor::where('username', Input::get('email'))->first();
            if (count($donor)) {
                $add= '<br>This is the administator login page.<br> <a href="'.URL::to('frontend/login', [$donor->client_id,'none']).'">Click here for the donor login page.</a>';
            }
            $message = 'User was not found.'.$add;
            $alert = 'danger';
        } catch (\Cartalyst\Sentry\Users\UserNotActivatedException $e) {
            $message = 'User is not activated.';
            $alert = 'danger';
        } catch (\Cartalyst\Sentry\Throttling\UserSuspendedException $e) {
            $message = 'User is suspended. After 5 failed login attempts the user is suspended for 15 minutes.';
            $alert = 'danger';
        } catch (\Cartalyst\Sentry\Throttling\UserBannedException $e) {
            $message = 'User is banned. You must contact support@helpyousponsor.com to reactivate.';
            $alert = 'danger';
        }

        return Redirect::route('admin.login')
            ->with('message', $message)
            ->with('alert', $alert)
            ->withInput();
    }
 
    public function getLogout()
    {
        Sentry::logout();
        Session::flush();
 
        return Redirect::route('admin.login');
    }

    public function resetPassword()
    {
        return view('admin.views.resetPassword');
    }
}
