<?php namespace App\Controllers\Admin;

use Auth;
use BaseController;
use Form;
use Input;
use Redirect;
use Sentry;
use View;
use Client;
use Mail;
use Session;
use Group;
use User;
use Validator;

use App\Http\Controllers\Controller;

class UserController extends Controller
{
    
    public function activateUser($id, $activationCode)
    {
        try {
            // Find the user using the user id
            $user = Sentry::getUserProvider()->findById($id);
        
            // Attempt to activate the user
            if ($user->attemptActivation($activationCode)) {
                // User activation passed
                $result = ['message' => 'Account activation successful', 'alert' => 'success'];
            } else {
                // User activation failed
                $result = ['message' => 'Account activation failed', 'alert' => 'danger'];
            }
        } catch (\Cartalyst\Sentry\Users\UserNotFoundException $e) {
            $result = ['message' => 'Account was not found.', 'alert' => 'danger'];
        } catch (\Cartalyst\Sentry\Users\UserAlreadyActivatedException $e) {
            $result = ['message' => 'Account is already activated.', 'alert' => 'warning'];
        }
        
        Session::flash('message', $result['message']);
        Session::flash('alert', $result['alert']);
        return redirect('login');
    }
    
    public function manuallyActivateUser($id)
    {
        try {
            $user = Sentry::findUserById($id);

            if ($user->attemptActivation($user->activation_code)) {
                // User activation passed
                return redirect('admin/view_admins')
                    ->with('message', 'User activated')
                    ->with('alert', 'success');
            } else {
                return redirect('admin/view_admins')
                    ->with('message', 'Activation failed')
                    ->with('alert', 'danger');
            }
        } catch (Cartalyst\Sentry\Users\UserNotFoundException $e) {
            return redirect('admin/view_admins')
                ->with('message', 'User was not found.')
                ->with('alert', 'danger');
        } catch (Cartalyst\Sentry\Users\UserAlreadyActivatedException $e) {
            return redirect('admin/view_admins')
                ->with('message', 'User is already activated.')
                ->with('alert', 'danger');
        }
    }
    
    public function addAdmin()
    {
        $groupsObject = Group::where('client_id', Session::get('client_id'))->get();
        foreach ($groupsObject as $group) {
            $groups[$group->id] = $group->name;
        }
        
        return view('admin.views.addAdmin')
            ->with('groups', $groups);
    }
    
    public function postAddAdmin()
    {
        $data = Input::all();
        
        $rules = [
            'email' => 'required|email|unique:users',
            'password' => 'required|min:7',
            'first_name' => 'required'
        ];
        
        $validator = Validator::make($data, $rules);
    
        if ($validator->passes()) {
            try {
                // Create the user
                $user = Sentry::register([
                    'email'    => $data['email'],
                    'password' => $data['password'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'client_id' => Session::get('client_id'),
                    'group_id' => $data['group']
                ]);
            
                $activationCode = $user->getActivationCode();
                
                $data['email'] = $user->email;
                $data['id'] = $user->id;
                $data['activationCode'] = $activationCode;
                
                Mail::queue('emails.newAdmin', $data, function ($message) use ($user) {
                    $message->to($user->email, 'New User')->subject('Activation Code');
                });
                
                Session::flash('message', 'The admin account has been created and an activation code along with the password has been emailed. Once the admin has activated the account they will be able to login.');
                Session::flash('alert', 'success');
                return redirect('admin/view_admins');
            } catch (\Cartalyst\Sentry\Users\LoginRequiredException $e) {
                $message = 'Email field is required.';
            } catch (\Cartalyst\Sentry\Users\PasswordRequiredException $e) {
                $message = 'Password field is required.';
            } catch (\Cartalyst\Sentry\Users\UserExistsException $e) {
                $message = 'User with this email already exists.';
            } catch (\Cartalyst\Sentry\Groups\GroupNotFoundException $e) {
                $message = 'Group was not found.';
            }
    
            Session::flash('message', $message);
            Session::flash('alert', 'danger');
            return redirect('admin/add_admin')
                ->withInput();
        }
        Session::flash('message', 'There was a problem with your submission. Please see below for details');
        Session::flash('alert', 'danger');
        return redirect('admin/add_admin')
            ->withErrors($validator)
            ->withInput();
    }
    
    public function editAdmin($user_id)
    {
        $admin = User::whereId($user_id)->with('group')->first();
        $groupsObject = Group::whereClientId(Session::get('client_id'))->get();
        foreach ($groupsObject as $group) {
            $groups[$group->id] = $group->name;
        }
        $user= Sentry::getUser();
        
        return view('admin.views.editAdmin')
            ->with('admin', $admin)
            ->with('user', $user)
            ->with('groups', $groups);
    }
    
    public function postEditAdmin($user_id)
    {
        $data = Input::all();
        
        $rules = [
            'email' => 'required|email',
            'first_name' => 'required'
        ];
        
        $validator = Validator::make($data, $rules);
    
        if ($validator->passes()) {
            try {
                // Find the user using the user id
                $user = Sentry::findUserById($user_id);
            
                // Update the user details
                $user->email = $data['email'];
                $user->first_name = $data['first_name'];
                $user->last_name = $data['last_name'];
                $user->group_id = $data['group_id'];
                if (!empty($data['password'])) {
                    $user->password = $data['password'];
                }
            
                // Update the user
                if ($user->save()) {
                    Session::flash('message', 'Admin details updated successfully');
                    Session::flash('alert', 'success');
                    return redirect('admin/view_admins');
                } else {
                    Session::flash('message', 'There was a problem updating this admin');
                    Session::flash('alert', 'danger');
                    return redirect('admin/edit_admin/'.$user_id.'')
                        ->withErrors($validator)
                        ->withInput();
                }
            } catch (Cartalyst\Sentry\Users\UserExistsException $e) {
                Session::flash('message', 'User with this login already exists.');
                Session::flash('alert', 'warning');
                return redirect('admin/edit_admin/'.$user_id.'')
                    ->withErrors($validator)
                    ->withInput();
            } catch (Cartalyst\Sentry\Users\UserNotFoundException $e) {
                Session::flash('message', 'User was not found.');
                Session::flash('alert', 'warning');
                return redirect('admin/view_admins');
            }
        }
        Session::flash('message', 'There was a problem updating this admin');
        Session::flash('alert', 'danger');
        return redirect('admin/edit_admin/'.$user_id.'')
            ->withErrors($validator)
            ->withInput();
    }
    
    public function removeAdmin($user_id)
    {
        $user = User::find($user_id);
        $user->delete();
        
        return redirect('admin/view_admins');
    }
    
    public function viewAdmins()
    {
        $admins = User::where('client_id', Session::get('client_id'))->with('group')->get();
        
        return view('admin.views.viewAdmins')
            ->with('admins', $admins);
    }
}
