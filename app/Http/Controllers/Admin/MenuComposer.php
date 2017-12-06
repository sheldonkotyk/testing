<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Request;
use App\Models\Client;
use App\Models\Donor;
use App\Models\Entity;
use App\Models\Group;
use App\Models\Hysform;
use App\Models\Program;
use App\Models\User;
use Cartalyst\Sentry\Facades\Laravel\Sentry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class MenuComposer
{

    public function compose($view)
    {

        $client_id = Session::get('client_id');
        
        $donorsbyform = Hysform::where('type', 'donor')->where('client_id', $client_id)->get();
        
        $p = new Program;
        $the_uri= Request::segments();
            $page ='';
        if (isset($the_uri[1])) {
            $page=$the_uri[1];
        }


        //This portion of code finds the program id (if it exists)
        //for the purpose of displaying the current program in the topnav menu.
        $programs_active='';
        $current_program='';
        $sponsorships_active = '';

        if ($page=='show_all_entities') {
            $current_program=$the_uri[2];
        }

        if ($page=='edit_entity') {
            $current_program= Entity::withTrashed()->find($the_uri[2])->program_id;
        }
        
        if ($page=='add_entity') {
            $current_program=$the_uri[2];
        }

        if ($page=='upload_file'&&$the_uri[2]=='entity') {
            $current_program= Entity::withTrashed()->find($the_uri[3])->program_id;
        }
        
        if ($page=='notes'&&$the_uri[3]=='entity') {
            $current_program=$the_uri[4];
        }

        if ($page=='move_entity') {
            $current_program= Entity::withTrashed()->find($the_uri[2])->program_id;
        }
        
        if ($page=='show_all_sponsorships'&&$the_uri[2]!='all') {
             $current_program=$the_uri[2];
        }

        if ($page=='show_all_sponsorships'&&$the_uri[2]=='all') {
            $current_program='all';
        }

            
        if (!empty($current_program)) {
            if ($page=='show_all_sponsorships') {
                $sponsorships_active='active';
            } else {
                $programs_active= 'active';
            }
        }



        $donors_active='';
        $current_hysform='';
        $add_donor='';


        if ($page=='show_all_donors') {
            $current_hysform=$the_uri[2];
        }

        if ($page=='edit_donor') {
            $current_hysform=Donor::withTrashed()->find($the_uri[2])->hysform_id;
        }
        
        if ($page=='add_donor') {
            $current_hysform=$the_uri[2];
            $add_donor='true';
        }
        
        if ($page=='upload_file'&&$the_uri[2]=='donor') {
            $current_hysform=Donor::withTrashed()->find($the_uri[3])->hysform_id;
        }
        
        if ($page=='notes'&&$the_uri[3]=='donor') {
            $current_hysform=Donor::withTrashed()->find($the_uri[2])->hysform_id;
        }
        
        if ($page=='sponsorships') {
            $current_hysform=Donor::withTrashed()->find($the_uri[2])->hysform_id;
        }
        
        if ($page=='donations_by_donor') {
            $current_hysform=Donor::withTrashed()->find($the_uri[2])->hysform_id;
        }

        if (!empty($current_hysform)) {
            $donors_active  = 'active';
        }

        $home_active= '';

        if ($page='admin'&&!isset($the_uri[1])) {
            $home_active='active';
        }

         $the_user = Sentry::getUser();


         //TAKE THIS OUT BEFORE PUSHING TO MASTER!!!
         Cache::tags('programs_menu-'.$client_id)->flush();

        $programs = Cache::tags('programs_menu-'.$client_id)->remember('programs_menu-'.$client_id.'-'.$the_user->id, 10080, function () use ($current_program, $p) {
            return  $p->programsDropDown($current_program);
        });

        $sponsorships = Cache::tags('programs_menu-'.$client_id)->remember('sponsorships_menu-'.$client_id.'-'.$the_user->id, 10080, function () use ($current_program, $p) {
            return  $p->programsDropDown($current_program, 'true');
        });

        if (!empty($current_program)&&$current_program!='all') {
            $program = Program::find($current_program);
            $programs = str_replace('href="/admin/show_all_entities/'.$current_program.'">'.$program->name, 'href="/admin/show_all_entities/'.$current_program.'"> '.'<strong> '.$program->name.' </strong>', $programs);
        }

        $org = Client::find($client_id);

        $number_of_admin_groups= Group::where('client_id', $client_id)->get()->count();

        $number_of_admins= User::where('client_id', $client_id)->get()->count();

        $number_of_programs= Program::where('client_id', $client_id)->where('lft', '>', '1')->get()->count();

        $vars=[
        'programs'=> $programs,
        'sponsorships' => $sponsorships,
        'sponsorships_active'=> $sponsorships_active,
        'number_of_admins'=>$number_of_admins,
        'number_of_admin_groups'=>$number_of_admin_groups,
        'number_of_programs'=>$number_of_programs,
        'donorforms'=> $donorsbyform,
        'org'=> $org,
        'new_emails'=>$org->countNewEmails(),
        'programs_active'=>$programs_active,
        'donors_active'=>$donors_active,
        'current_hysform'=>$current_hysform,
        'home_active'=>$home_active,
        'add_donor'=>$add_donor];

       
        
        //Users 1 and 2 can switch their client id and group on the fly
        if ($the_user->id=='1') {
            $users=User::all();
            $clients=Client::all();
            $users_list=[];
            foreach ($users as $user) {
                $user->client_name=$clients->find($user->client_id)->organization;
            }
            $user_list=$users;

            //var_dump($users);
            $vars['user_list']=$user_list;
            if (count(Session::get('emulating'))) {
                $vars['emulating'] = Session::get('emulating');
            }
        }
        
        

        $view->with($vars);
    }
}
