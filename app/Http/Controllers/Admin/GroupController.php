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
use Program;
use Hysform;
use Validator;
use Group;
use Cache;

use App\Http\Controllers\Controller;

class GroupController extends Controller
{

    public function viewGroups()
    {
        $groups = Group::where('client_id', Session::get('client_id'))->get();
        return view('admin.views.viewGroups')
            ->with('groups', $groups);
    }

    public function createGroup()
    {
        $programs = Program::where('client_id', Session::get('client_id'))->where('lft', '>', 1)->orderBy('lft')->get();
        $donors = Hysform::where('client_id', Session::get('client_id'))->where('type', 'donor')->get();
        $allGroups = Group::whereClientId(Session::get('client_id'))->get();
        
        return view('admin.views.createGroup', [
            'programs' => $programs,
            'donors' => $donors,
            'all_groups' => $allGroups
        ]);
    }

    public function postCreateGroup()
    {
        $data = Input::all();
        
        $rules = [
            'name' => 'required|min:3'
            ];
            
        $validator = Validator::make($data, $rules);
        
        if ($validator->passes()) {
            $name = $data['name'];
            $programs = Program::where('client_id', Session::get('client_id'))->where('lft', '>', 1)->orderBy('lft')->get();
            $donors = Hysform::where('client_id', Session::get('client_id'))->where('type', 'donor')->get();
            $program_all=1;
            $donor_all=1;
            foreach ($programs as $p) {
                if (!isset($data['program-'.$p->id])) {
                    $program_all=0;
                }
            }
            foreach ($donors as $d) {
                if (!isset($data['donor-'.$d->id])) {
                    $donor_all=0;
                }
            }

            if ($program_all==1) {
                $data['program-all']=1;
            }
            if ($donor_all==1) {
                $data['donor-all']=1;
            }



            unset($data['name']);
            unset($data['_token']);
            
                // Create the group
            $group = new Group;
            $group->client_id = Session::get('client_id');
            $group->name = $name;
            $group->permissions = json_encode($data);
            $group->save();

            Cache::tags('programs_menu-'.Session::get('client_id'))->flush();
            
            return redirect('admin/view_groups');
        }
        
        return redirect('admin/create_group')
            ->withErrors($validator)
            ->withInput();
    }
    
    public function editGroup($group_id)
    {
        $groupObject = Group::find($group_id);
        $permissions = json_decode($groupObject->permissions);
        $group = $groupObject->toArray();
        $allGroups = Group::whereClientId(Session::get('client_id'))->get();
        
        foreach ($permissions as $k => $v) {
            $group[$k] = $v;
        }
        unset($group['permissions']);

        $programs = Program::where('client_id', Session::get('client_id'))->where('lft', '>', 1)->orderBy('lft')->get();
        $donors = Hysform::where('client_id', Session::get('client_id'))->where('type', 'donor')->get();
        $user = Sentry::getUser();
        
        return view('admin.views.editGroup', [
            'programs' => $programs,
            'donors' => $donors,
            'group' => $group,
            'all_groups' => $allGroups,
            'user' => $user
        ]);
    }
    
    public function postEditGroup($group_id)
    {
        $data = Input::all();
        
        $rules = [
            'name' => 'required|min:3'
            ];
            
        $validator = Validator::make($data, $rules);
        
        if ($validator->passes()) {
            $name = $data['name'];

            $programs = Program::whereClientId(Session::get('client_id'))->where('lft', '>', 1)->orderBy('lft')->get();
            $donors = Hysform::whereClientId(Session::get('client_id'))->where('type', 'donor')->get();
            $program_all=1;
            $donor_all=1;
            foreach ($programs as $p) {
                if (!isset($data['program-'.$p->id])) {
                    $program_all=0;
                }
            }
            foreach ($donors as $d) {
                if (!isset($data['donor-'.$d->id])) {
                    $donor_all=0;
                }
            }

            
            if ($program_all==1) {
                $data['program-all']='1';
            }
            if ($donor_all==1) {
                $data['donor-all']='1';
            }

            unset($data['name']);
            unset($data['_token']);
            
                // Create the group
            $group = Group::find($group_id);
            $group->client_id = Session::get('client_id');
            $group->name = $name;
            $group->permissions = json_encode($data);
            $group->save();
            
            // update user's permission if they updated the group they belong to
            $user = Sentry::getUser();
            if ($user->group_id == $group_id) {
                Session::put('permissions', json_decode($group->permissions));
            }

            Cache::tags('programs_menu-'.Session::get('client_id'))->flush();
            
            return redirect('admin/view_groups');
        }
        
        return redirect('admin/edit_group/'.$group_id.'')
            ->withErrors($validator)
            ->withInput();
    }
    
    public function removeGroup($group_id)
    {
        $group = Group::find($group_id);
        $group->delete();
        
        return redirect('admin/view_groups');
    }
}
