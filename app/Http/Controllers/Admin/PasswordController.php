<?php  namespace App\Controllers\Admin;

use BaseController;
use Auth;
use Input;
use Password;
use Redirect;
use Hash;

use App\Http\Controllers\Controller;

class PasswordController extends Controller
{
 
    public function remind()
    {
        return \view('password.remind');
    }

    public function request()
    {
        $credentials = ['email'    => Input::get('email')];

        return Password::remind($credentials);
    }

    public function reset($token)
    {
        return \view('password.reset')->with('token', $token);
    }

    public function update()
    {
        $credentials = [
        'email'     => Input::get('email'),
        'password'  => Input::get('password'),
        'password_confirmation' => Input::get('password_confirmation'),
        'token'     => Input::get('token')
        ];
     
        return Password::reset($credentials, function ($user, $password) {
            $user->password = Hash::make($password);
     
            $user->save();
     
            return redirect('login')->with('flash', 'Your password has been reset');
        });
    }
}
