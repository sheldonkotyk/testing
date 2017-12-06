<?php  namespace App\Controllers\Admin;
 
    use Auth;
use BaseController;
use Form;
use Input;
use Redirect;
use Sentry;
use View;
use Redis;
use Validator;
use Program;
use Field;
use Session;
use DB;
use Hysform;
use Setting;
use Emailset;
use Carbon;
use URL;
use Entity;
use Cache;
use Group;
    
use App\Http\Controllers\Controller;

class ProgramController extends Controller
{
        
    /**
         *  Loop through a nested array and prepare it for use with
         *  Nestable jquery plugin - https://github.com/dbushell/Nestable
         *
         *  @param array $nestedArray - prepared by Nested - Sets
         */
    public function makeNestable($nestedArray)
    {
        return $output = $this->nesty($nestedArray);
    }
        
    // child function of makeNestable, needed so that the whole tree is output
    // otherwise output is returned before loop has completed
    private function nesty($nestedArray)
    {
        $output = '<ol class="dd-list">';
        foreach ($nestedArray as $k => $v) {
            if (is_array($v)) {
                $vals = explode('{', $k);
                $p = Program::find($vals[1]);
                $setup = $p->isProgramSetup($vals[1]);
                $parent = '';
                    
                if (!empty($p->link_id) && count(Program::find($p->link_id))) {
                    $parent = ' ( subordinate to '.Program::find($p->link_id)->name.' ) ';
                }

                $output .= '<li class="dd-item" data-id="'.$vals[1].'">';
                $output .= '<div class="dd-handle dd3-handle">Drag</div><div class="dd3-content">';
                $output .= $vals[0].$parent . ' | <a href="/admin/program_settings/'.$vals[1].'"><small>Edit</small></a>';

                if (empty($p->link_id)) {
                    $output .= ' | <a href="/admin/csv_import/' . $vals[1] . '"><small>Import</small></a>';
                }
                    
                $output .= '<span class="pull-right">';
                $output .= ($setup == 'true' ? '<span class="glyphicon glyphicon-ok text-success"></span>':'<span class="text-danger opacity50">Errors </span><span class="glyphicon glyphicon-warning-sign text-danger opacity50"></span>');
                $output .= ' | <a data-toggle="modal" href="/admin/remove_program_warning/'.$vals[1].'" data-target="#delete-modal" title="remove"><small><span class="glyphicon glyphicon-remove"></span></small></a>';
                $output .= '</span></div>';

                $output .= $this->nesty($v);
            } else {
                $vals = explode('{', $v);
                $p = Program::find($vals[1]);
                $setup = $p->isProgramSetup($vals[1]);
                $parent = '';
                    
                if (!empty($p->link_id)) {
                    if (count(Program::find($p->link_id))) {
                        $parent=' ( subordinate to '.Program::find($p->link_id)->name.' ) ';
                    } else {
                        $parent=' ( Parent Program has been deleted. <a href="'.URL::to('admin/edit_program', [$p->id]).'">Reset Parent</a> ) ';
                    }
                }

                $output .= '<li class="dd-item" data-id="'.$vals[1].'">';
                $output .= '<div class="dd-handle dd3-handle">Drag</div><div class="dd3-content">';
                $output .= $vals[0].$parent.' | <a href="/admin/program_settings/'.$vals[1].'"><small>Edit</small></a>';
                    
                if (empty($p->link_id)) {
                    $output .= ' | <a href="/admin/csv_import/' . $vals[1] . '"><small>Import</small></a>';
                }
                    
                $output .= '<span class="pull-right">';
                $output .= ($setup=='true' ? '<span class="glyphicon glyphicon-ok text-success"></span>':'<span class="text-danger opacity50">Errors</span> <span class="glyphicon glyphicon-warning-sign text-danger opacity50"></span>');
                $output .= ' | <a data-toggle="modal" href="/admin/remove_program_warning/'.$vals[1].'" data-target="#delete-modal" title="remove"><small><span class="glyphicon glyphicon-remove"></span></small></a>';
                $output .= '</span></div></li>';
            }
        }
        $output .= '</li></li></ol>';
            
        return $output;
    }
            
    public function manageProgram()
    {
        $programs = Program::where('client_id', Session::get('client_id'))->first();

        $number_of_programs= Program::where('client_id', Session::get('client_id'))->where('lft', '>', '1')->get()->count();

        $client = ['id' => $programs->id, 'name' => $programs->name];

        // return nested array with name{id as key except on leaf id is value
        $programs = $programs->presentChildrenAs('array', function ($item) {
            $output = ''.$item->name.'{'.$item->id.''; // separate with { for exploding in loop
            return $output;
        });
        $programs = $this->makeNestable($programs);

        $permissions=Session::get('permissions');
    
        if (!isset($permissions->{'program-all'})||!isset($permissions->{'donor-all'})) {
            Session::flash('message', 'You have programs and/or donor forms that have been created, and you don\'t have your permissions set use them.<br/>
					<a href="'.URL::to('admin/view_groups').'">Set the permissions here.</a>');
            Session::flash('alert', 'warning');
        }

        $archived = Program::onlyTrashed()->where('client_id', Session::get('client_id'))->get();
            
        return view('admin.views.manageProgram', [
            'programs' => $programs,
            'client' => $client,
            'archived' => $archived,
            'number_of_programs'=>$number_of_programs
            ]);
    }

    public function multiProgramSelect()
    {
        $client_id = Session::get('client_id');
        $hysforms = Hysform::where('client_id', $client_id);

        $p= new Program;

        $programs= $p->getFilledNonSubPrograms($client_id);

        $exists=false;
        foreach ($programs as $program) {
            $programs_by_hysform_id[$program->hysform_id][]=$program;
            if (count($programs_by_hysform_id[$program->hysform_id])>1) {
                $exists=true;
            }
        }

        return view('admin.views.multiProgramSelect', [
            'programs_by_hysform_id'    => $programs_by_hysform_id,
            'exists' => $exists]);
    }

    public function postMultiProgramSelect()
    {

        $client_id = Session::get('client_id');
        $hysforms = Hysform::where('client_id', $client_id);

        $p= new Program;

        $program_list= Input::get('program_list');
        $program_list_save= Input::get('program_list');
        $program_settings= Input::get('program_settings');
        if (!empty($program_settings)&&!empty($program_list)) {
            foreach ($program_list as $k => $v) {
                if ($program_settings==$v) {
                    unset($program_list[$k]);
                }
            }
        }

        if (count($program_list)) {
            if (!empty($program_settings)) {
                $hyphen_list = $program_settings.'-'.implode('-', $program_list);
            } else {
                $hyphen_list = implode('-', $program_list);
            }
        } else {
            return redirect('admin/multi_program_select')
                ->with('message', 'Error: No Programs Selected')
                ->with('alert', 'danger');
        }
            //$hyphen_list = implode('-',$program_list_save);

            return redirect('admin/multi_program_select')
                ->with('message', 'The URL is: <h3><a href="'.URL::to('frontend/view_all/'.$client_id.'/'.$hyphen_list).'">'.URL::to('frontend/view_all/'.$client_id.'/'.$hyphen_list.'/</a></h3>'))
                ->with('alert', 'info');
    }

    public function postChildProgram()
    {
        $data = Input::all();
            
        $rules = [
            'name' => 'required|min:3'
        ];
           
        $validator = Validator::make($data, $rules);

        // return var_dump($data);
            
        if ($validator->passes()) {
            $parent = Program::where('client_id', $data['parent_id'])->get()->first();
            // $parent2 = Program::find($data['parent_id']);

            // return var_dump(array($parent1,$parent2));

            $child = new Program(['name' => $data['name'], 'client_id' => Session::get('client_id'), 'prefix' => $data['prefix']]);
            $child->makeFirstChildOf($parent);

            $user= Sentry::getUser();

            $group= Group::find($user->group_id);

            if ($group!=null) {
                $permissions=json_decode($group->permissions, true);

                $permissions['program-'.$child->id]='1';

                if ($group->areAllProgramsSet(Session::get('client_id'), $group->id)) {
                    $permissions['program-all']=1;
                }

                $group->permissions=json_encode($permissions);
                    
                $group->save();
                // Update permissions in Session, so user doesn't have to re-login.
                Session::put('permissions', json_decode($group->permissions));
                //Flush the menus.
                Cache::tags('programs_menu-'.Session::get('client_id'))->flush();
            }
            
            return redirect('admin/manage_program')
                ->with('message', 'You have created an new program called, <em>'.$child->name.'</em>.<br> Click on <strong><a href="'.URL::to("admin/program_settings/".$child->id).'">config</a></strong> to setup this program.<br> This program has been enabled for the group <em>'.$group->name.'</em>.<br> If you want other groups to use this program, you must give them permissions in <strong><a href="'.URL::to('admin/view_groups').'">Groups.</a></strong>')
                ->with('alert', 'info');
        }
        return redirect('admin/add_child_program/'.$data['parent_id'].'')
            ->withErrors($validator)
            ->withInput();
    }

    public function addSubProgram()
    {
        

        $p= new Program;
        $programs= $p->getUnLinkedPrograms();

        return view('admin/views/addSubProgram')->with(['parent_id' => Session::get('client_id'),'programs' => $programs]);
    }

    public function postSubProgram()
    {
        $data = Input::all();
            
        $rules = [
            'name' => 'required|min:3',
            'link_id' => 'required'
        ];

            
           
        $validator = Validator::make($data, $rules);
            
        if ($validator->passes()) {
            $parent = Program::find($data['link_id']);

            $child = new Program;
            $child->makeFirstChildOf($parent);
                
            $child->name =          $data['name'];
            $child->client_id =     Session::get('client_id');
            $child->link_id =       $data['link_id'];
            $child->hysform_id=     $parent->hysform_id;
            $child->save();


            $user= Sentry::getUser();
            $group= Group::find($user->group_id);

            if ($group!=null) {
                $permissions=  json_decode($group->permissions, true);

                $permissions['program-'.$child->id]='1';

                if ($group->areAllProgramsSet(Session::get('client_id'), $group->id)) {
                    $permissions['program-all']=1;
                }

                $group->permissions=json_encode($permissions);

                $group->save();
                //Update permissions in Session, so user doesn't have to re-login.
                Session::put('permissions', json_decode($group->permissions));
                //Flush the menus.
                Cache::tags('programs_menu-'.Session::get('client_id'))->flush();
            }

            Cache::tags('programs_menu-'.Session::get('client_id'))->flush();
            return redirect('admin/manage_program');
        }
        return redirect('admin/add_sub_program/')
            ->withErrors($validator)
            ->withInput();
    }
        
    public function programSettings($id)
    {

        $program = Program::where('client_id', Session::get('client_id'))->find($id);

        if (count($program)==0) {
            return "Error: Program Not Found.";
        }

        $hysforms = Hysform::whereClientId(Session::get('client_id'))->get();
        $settings = Setting::whereClientId(Session::get('client_id'))->get();
        $emailsets = Emailset::whereClientId(Session::get('client_id'))->get();
        $entity_submit = explode(',', $program->entity_submit);
        $donor_submit = explode(',', $program->donor_submit);

        $parent_program=null;
        $parent_settings=null;
        $parent= null;
        $parent_settings=null;
        if ($program->link_id!=0) {
            $parent=Program::find($program->link_id);
            if (count($parent)&&$parent->setting_id!=0) {
                $parent_settings=json_decode(Setting::find($parent->setting_id)->program_settings);
            } else {
                Session::flash('message', 'Error: Parent Program has been deleted. <a href="'.URL::to('admin/edit_program', [$id]).'">Reset Parent</a>  ');
                Session::flash('alert', 'danger');
            }
        }

        return view('admin.views.programSettings', [
            'program' => $program,
            'parent' =>$parent,
            'parent_settings'   => $parent_settings,
            'hysforms' => $hysforms,
            'entity_submit' => $entity_submit,
            'donor_submit' => $donor_submit,
            'settings' => $settings,
            'emailsets' => $emailsets
        ]);
    }
        
    public function postSponsorshipToProgram($id)
    {
        $data = Input::all();
        $program = Program::find($id);
        $old= $program->hysfrom_id;
        $program->hysform_id = $data['sponsorship_form'];
        $program->save();

        if ($this->validateEmailSet($id)==false) {
            //$program->hysform_id=$old;
            $program->emailset_id=0;
            $program->save();
        } else {
            //Change child hysform_id to match parent and notify Client
            $this->setChildHysformId($id);
        }

        Cache::tags('programs_menu-'.$program->client_id)->flush();
        $entity = new Entity;
        $entity->clearEntityCache($program->id);

        return redirect('admin/program_settings/'.$id.'');
    }
        
    public function postDonorToProgram($id)
    {
        $data = Input::all();
        $program = Program::find($id);
        $old=$program->donor_hysform_id;
        $program->donor_hysform_id = $data['donor_form'];

        if ($this->validateDonorForm($id, $data['donor_form'])) {
            $program->save();
        }

            
            
        //Remove the Email Set if it fails
        if ($this->validateEmailSet($id)==false) {
            $program->emailset_id='';
            $program->save();
        }
            
        Cache::tags('programs_menu-'.$program->client_id)->flush();
        $entity = new Entity;
        $entity->clearEntityCache($program->id);
            
        return redirect('admin/program_settings/'.$id.'');
    }

    public function postSettingsToProgram($id)
    {
        $data = Input::all();
        $program = Program::find($id);
        $program->setting_id = $data['settings_id'];
        $program->save();

        Cache::tags('programs_menu-'.$program->client_id)->flush();
        $entity = new Entity;
        $entity->clearEntityCache($program->id);
            
        return redirect('admin/program_settings/'.$id.'');
    }

    public function postEmailsetsToProgram($id)
    {
        $data = Input::all();
        $program = Program::find($id);
            
        // check to see if the email set is used elsewhere,
        //if so make sure the hysform_id's match so there aren't short code problems
        $check = false;
        $allprograms = Program::whereEmailsetId($data['emailset_id'])->where('emailset_id', '!=', '0')->get();
        foreach ($allprograms as $k => $p) {
            if ($p->hysform_id != $program->hysform_id && $p->hysform_id!=0) {
                $check = true;
                $already_used[]='Sponsor form differs from: '.'<a href="'.URL::to('admin/program_settings/'.$p->id).'">'.$p->name.'</a>';
                $reset[$k]='<a href="'.URL::to('admin/program_settings/'.$p->id).'">'.$p->name.'</a>';
            }
            if ($p->donor_hysform_id != $program->donor_hysform_id &&$p->hysform_id!=0) {
                $check = true;
                $already_used[]='Donor form differs from: '.'<a href="'.URL::to('admin/program_settings/'.$p->id).'">'.$p->name.'</a>';
            }
        }
            
        if ($check == true) {
            return redirect('admin/program_settings/'.$id)
                ->with('message', 'You cannot use the selected Email Template Set. It is currently being used by other program(s) <br/>'. implode('<br/> ', $already_used) .'<br/> These must match or it would cause problems with using short codes. Please change the forms or create a new Email Template Set to use with this program.')
                ->with('alert', 'danger');
        }
            
        $program = Program::find($id);
        $program->emailset_id = $data['emailset_id'];
        $program->save();

        Cache::tags('programs_menu-'.$program->client_id)->flush();
        $entity = new Entity;
        $entity->clearEntityCache($program->id);
            
        return redirect('admin/program_settings/'.$id);
    }
        
    public function postSubmitFormToProgram($id)
    {
        $data = Input::all();
        $program = Program::find($id);
        if ($data['submit_form_type'] == 'entity') {
            if (!empty($program->entity_submit)) {
                $entity_submit = $program->entity_submit;
                $entity_submit .= ','.$data['submit_form'].'';
                $program->entity_submit = $entity_submit;
            } else {
                $program->entity_submit = $data['submit_form'];
            }
        } elseif ($data['submit_form_type'] == 'donor') {
            if (!empty($program->donor_submit)) {
                $donor_submit = $program->donor_submit;
                $donor_submit .= ','.$data['submit_form'].'';
                $program->donor_submit = $donor_submit;
            } else {
                $program->donor_submit = $data['submit_form'];
            }
        }
        $program->save();

        Cache::tags('programs_menu-'.$program->client_id)->flush();
            
        return redirect('admin/program_settings/'.$id.'');
    }
        
    public function removeSubmitForm($type, $program_id, $form_id)
    {
        $program = Program::find($program_id);
        $submit = '';
        if ($type == 'entity') {
            $entity_submit = explode(',', $program->entity_submit);
            foreach ($entity_submit as $es) {
                if ($es != $form_id) {
                    $submit .= "$es,";
                }
            }
            $submit = substr($submit, 0, -1); // Removes the last comma
            $program->entity_submit = $submit;
        }
        if ($type == 'donor') {
            $donor_submit = explode(',', $program->donor_submit);
            foreach ($donor_submit as $ds) {
                if ($ds != $form_id) {
                    $submit .= "$ds,";
                }
            }
            $submit = substr($submit, 0, -1); // Removes the last comma
            $program->donor_submit = $submit;
        }
        $program->save();
            
        return redirect('admin/program_settings/'.$program_id.'');
    }
        
    public function editProgram($program_id)
    {
        $program = Program::find($program_id);
        $p= new Program;
        $programs= $p->getUnLinkedPrograms(Session::get('client_id'));
            
        return view('admin.views.editProgram')->with(['program'=> $program,'programs' => $programs]);
    }
        
    public function postEditProgram($program_id)
    {
        $data = Input::all();
            
        $rules = [
            'name' => 'required|min:3',
            'counter' => 'numeric'
        ];
        if (empty($data['prefix'])) {
            $data['prefix']='';
        }
        if (empty($data['counter'])) {
            $data['counter']=0;
        }
           
        $validator = Validator::make($data, $rules);
            
        if ($validator->passes()) {
            $program = Program::find($program_id);
            $program->name = $data['name'];
            $program->prefix = $data['prefix'];
            $program->counter = $data['counter'];
            if (isset($data['link_id'])) { //If this is a subordinate program
                $parent=Program::find($data['link_id']);
                $program->link_id = $data['link_id']; //Set the link_id to the parent program
                $program->hysform_id = $parent->hysform_id; //Set the hsyform_id to the same as the parent
                    
                //If the donor form is the same, set the program to blank
                if ($program->donor_hysform_id==$parent->donor_hysform_id) {
                    $program->donor_hysform_id=0;
                }
                    
                if ($program->setting_id!=0) {//Check to see if the new settings are compatible (via program_type) with the parent. If not, remove the setting id.
                    $program_setting= json_decode(Setting::find($program->setting_id)->program_settings);
                    $parent_setting= json_decode(Setting::find($parent->setting_id)->program_settings);

                    if ($parent_setting->program_type!=$program_setting->program_type) {
                        $program->setting_id=0;
                    }
                }
            }

            $program->save();

            Cache::tags('programs_menu-'.Session::get('client_id'))->flush();
                
            return redirect('admin/manage_program/')
                ->with('alert', 'success')
                ->with('message', 'Program info successfully updated.');
        }
        return redirect('admin/edit_program/'.$program_id.'')
            ->with('alert', 'danger')
            ->with('message', 'There was a problem with your submission.')
            ->withErrors($validator)
            ->withInput();
    }
        
    public function removeProgramWarning($program_id)
    {
        return view('admin.views.deleteProgramModal')->with('program_id', $program_id);
    }
        
    public function removeProgram($program_id)
    {
        $program = Program::find($program_id);
        $program->delete();

        $entities= Entity::withTrashed()->where('program_id', $program_id)->get();
        foreach ($entities as $entity) {
            $entity->forceDelete();
        }


        Cache::tags('programs_menu-'.Session::get('client_id'))->flush();
            
        return redirect('admin/manage_program');
    }
        
    // AJAX function for updating the programs tree
    public function updateTree()
    {
        $data = Input::all();
        $programs = Program::whereClientId(Session::get('client_id'))->first();
        $tree = $data['data'];
        $programs->mapTree($tree);

        Cache::tags('programs_menu-'.Session::get('client_id'))->flush();
    }

    private function validateEmailSet($program_id)
    {
        $program = Program::find($program_id);

        // Check to see if the email set is invalid.
        // If it is, reset the child program templates to 0 and notify client.
        $check = false;
        $programs = Program::whereEmailsetId($program->emailset_id)->where('emailset_id', '!=', '0')->get();

        foreach ($programs as $k => $p) {
            if ($p->hysform_id != $program->hysform_id) {
                $check = true;
                $reset[$k]='<a href="'.URL::to('admin/program_settings/'.$p->id).'">'.$p->name.'</a>';
            }
            if ($p->donor_hysform_id != $program->donor_hysform_id) {
                $check = true;
                $reset[$k]='<a href="'.URL::to('admin/program_settings/'.$p->id).'">'.$p->name.'</a>';
            }
        }

        if ($check) {
            $previous_flash= Session::get('message');
            Session::flash('message', $previous_flash .
                '<br/>Warning: This change causes <a href="' . URL::to('admin/error') . '">conflicts with your email set templates</a>.<br/> The change was made, but the Email Set Template had to be reset.<br/> You may need to make a new Email Set Template.');
            Session::set('error', $program->name.'" Details: The Email Set Templates conflicted with these programs: <br/>'.implode('<br/>', $reset));
            Session::set('error-alert', 'warning');
            Session::flash('alert', 'warning');
        }
        if (empty($reset)) {
            return true;
        } else {
            return false;
        }
    }

    //Checks to see if the donor form is shared with the parent program
    public function validateDonorForm($program_id, $donor_form_id)
    {
        $program=Program::find($program_id);

        $hysform=Hysform::find($donor_form_id);

        if (!count($program)) {
            $previous_flash= Session::get('message');
            Session::flash('message', $previous_flash .'The program does not exist.');
            Session::flash('alert', 'warning');
            return false;
        }
        if (!count($program)) {
            $previous_flash= Session::get('message');
            Session::flash('message', $previous_flash .'The donor form does not exist.');
            Session::flash('alert', 'warning');
            return false;
        }

        //If the program is not a sub-program, any donor form may be used.
        if (empty($program->link_id)) {
            return true;
        }

        // Now Any donor form can be used by a sub-program
        // if(!empty($program->link_id))
        // {
        // 	$parent_program=Program::find($program->link_id);
        // 	if(count($parent_program))
        // 	{
        // 		if($parent_program->donor_hysform_id==$donor_form_id)
        // 		{
        // 			$previous_flash= Session::get('message');
           //  		Session::flash('message', $previous_flash .'Error: Parent and Sub programs must use distinct donor forms<br>
           //  			Therefore, "'.$hysform->name.'" may not be used by: "'.$program->name.'"<br> because it\'s already being used by the parent program: "'.$parent_program->name.'."
           //  			<br>');
           //  		Session::flash('alert','danger');
        // 			return false;
        // 		}
        // 	}
        // }

        return true;
    }
        

    private function setChildHysformId($program_id)
    {
        if ($program_id==0) {
            return false;
        }
        $program_names= [];

        $parent_program=Program::find($program_id);
            
        $child_programs=Program::where('link_id', '=', $program_id)->get();

        foreach ($child_programs as $child) {
            if ($child->hysform_id!=$parent_program->hysform_id) {
                $child->hysform_id=$parent_program->hysform_id;
                $child->save();
                $program_names[]='<a href="'.URL::to('admin/program_settings/'.$child->id).'">'.$child->name.'</a>';
            }
        }
        if (!empty($program_names)) {
            Session::flash('message', 'Note: Changing the Sponsorship Profile Form for "'.$parent_program->name.'" has altered the sponsor forms for these programs:<br/>'.
                implode('<br/>', $program_names));
            Session::flash('alert', 'info');
        } else {
            return false;
        }
    }
}
