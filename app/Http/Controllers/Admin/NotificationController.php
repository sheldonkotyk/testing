<?php  namespace App\Controllers\Admin;
 
    use Auth;
use BaseController;
use Form;
use Input;
use Redirect;
use Sentry;
use View;
use RedisL4;
use Validator;
use Session;
use DB;
use Hysform;
use Field;
use Donorfield;
use Group;
use Notification;
    
    /***
	 * 
	 *  Use this controller for creating all types of notifications
	 *  item_id is flexible depending on the type of notification
	 *  A 'submit' form notification requires the item_id be the id of the form
	 *
	 */
     
use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
        
    public function submitFormNotification($program_id, $form_id)
    {
        // get groups
        $allGroups = Group::whereClientId(Session::get('client_id'))->get();
            
        // get current settings (if they exist)
        $notification = Notification::where('program_id', $program_id)->where('item_id', $form_id)->first();
            
        $current = [];
        if (!empty($notification->groups)) {
            $current = json_decode($notification->groups, true);
        }
            
        // get user permissions
        $user = Sentry::getUser();
        $userGroup = Group::find($user->group_id);
        $permissions = json_decode($userGroup->permissions);
            
        // check permissions
        $groups = [];

        foreach ($allGroups as $group) {
            $g = 'group-'.$group->id;
                
            if (isset($permissions->group_all) && $permissions->group_all == 1) {
                $groups[] = $group;
            } elseif (isset($permissions->$g) && $permissions->$g == 1) {
                $groups[] = $group;
            }
        }
            
        return view('admin.views.submitFormNotification', [
            'groups' => $groups,
            'current' => $current
        ]);
    }
        
    public function postSubmitFormNotification($program_id, $form_id)
    {
        $data = Input::all();
        if (empty($data['group'])) {
            $data['group']='';
        }
        // double check to prevent double submissions
        $notification = Notification::where('program_id', $program_id)->where('item_id', $form_id)->first();
            
        if (empty($notification->id)) {
            $notification = new Notification;
            $notification->client_id = Session::get('client_id');
            $notification->program_id = $program_id;
            $notification->item_id = $form_id;
            $notification->type = 'submit';
            $notification->groups = json_encode($data['group']);
            $notification->save();
        } elseif (!empty($data['group'])) {
            $notification->groups = json_encode($data['group']);
            $notification->save();
        } else {
            $notification->forceDelete();
        }

            
        return redirect('admin/program_settings/'.$program_id.'')
            ->with('alert', 'success')
            ->with('message', 'Notification settings successfully saved.');
    }
}
