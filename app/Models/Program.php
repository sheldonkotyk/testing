<?php

namespace App\Models;

use App\Http\Requests\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;

class Program extends Cartalyst\NestedSets\Nodes\EloquentNode
{
    protected $worker = 'Cartalyst\NestedSets\Workers\IlluminateWorker';
    
    protected $fillable = ['name', 'type', 'client_id', 'hysform_id', 'donor_hysform_id', 'setting_id', 'emailset_id'];

    public function setting()
    {
        return $this->belongsTo('App\Models\Setting');
    }

    public function entity()
    {
        return $this->hasMany('App\Models\Entity');
    }
    
    // create well formatted nested array from nested sets
    public function formatNested($nestedArray)
    {
        foreach ($nestedArray as $k => $v) {
            if (is_array($v)) {
                $vals = explode('{', $k);
                //$output[$vals[1]] = $vals[0];
                $output[] = ['id' => $vals[1], 'name' => $vals[0], 'children' => $this->formatNested($v)];
            } else {
                $vals = explode('{', $v);
                $output[] = ['id' => $vals[1], 'name' => $vals[0]];
            }
        }
        return $output;
    }

    private function nesty($nestedArray, $program_ids, $current_program = null, $sponsorships = false, $first = true)
    {
        $permissions = Session::get('permissions');
        $output='';
        if ($first) {
            $output = "<ul class=\"dropdown-menu\">\r\n";
        }
        
        $program_count=0;
        $visible_count=0;

        $url = '/admin/show_all_entities';

        if ($sponsorships) {
            $url = '/admin/show_all_sponsorships';
        }

        if ($first&&$sponsorships) {
            $c = new Client;
            $sponsorship_counts= $c->countCommitmentsByProgram(Session::get('client_id'));
            // var_dump($sponsorship_counts);
            $total =0;
            $count ='';
            foreach ($sponsorship_counts as $s) {
                $tmp= reset($s);
                $total+= $tmp['total'];
            }
            if ($total>0) {
                $count=$total;
            }

            $pre_active = '';
            $post_active = '';

            if ($current_program=='all') {
                $pre_active='<strong>';
                $post_active='</strong>';
            }

            $output .= "<li><a href=\"".URL::to('admin/show_all_sponsorships/all')."\"><span class='badge pull-right'>".$count."</span>".$pre_active." All Sponsorships ".$post_active." </a></li>\r\n";
        }
        $the_uri= Request::segments();
            $page ='';
        if (isset($the_uri[1])) {
            $page=$the_uri[1];
        }

        foreach ($nestedArray as $k => $v) {
            $program_count++;
            if (is_array($v)) {
                $vals = explode('{', $k); // keys: 0 = name, 1 = id

                $p = 'program-'.$vals[1].'';

                $pre_active='';
                $post_active='';

                if (($current_program==$vals[1]&&$page=='show_all_sponsorships')||($current_program==$vals[1]&&$sponsorships&&$page=='show_all_sponsorships')) {
                    $pre_active='<strong>';
                    $post_active='</strong>';
                }

                if ($sponsorships) {
                    if (isset($sponsorship_counts[$vals[1]])) {
                        $this_count= reset($sponsorship_counts[$vals[1]]);

                        $count= $this_count['total'];
                    } else {
                        $count= 0;
                    }
                } else {
                    $count= $this->countEntities($vals[1]);
                    // if($count<1)
                    // 	$count= '';
                }

                $vals[0]= strlen($vals[0]) > 30 ? substr($vals[0], 0, 30)."..." : $vals[0];

                    
                if (isset($permissions->$p) && $permissions->$p == 1) {
                    $visible_count++;
                    if (in_array($vals[1], $program_ids)) {
                        $output .= "<li ><a tabindex=\"-1\" href=\"$url/$vals[1]\"><span class='badge pull-right'>".$count."</span>".$pre_active." $vals[0] ".$post_active."</a></li>\r\n";
                    } elseif ($sponsorships==false) {
                        $output .= "<li ><a tabindex=\"-1\" href=\"#\">".$pre_active." $vals[0] ".$post_active." <em>Setup Incomplete</em> <span class=\"icon ion-wrench\"></span>  </a></li> \r\n";
                    }

                    $output .= $this->nesty($v, $program_ids, $current_program, $sponsorships, false);
                }
            } else {
                $vals = explode('{', $v); // keys: 0 = name, 1 = id

                $p = 'program-'.$vals[1].'';

                $pre_active='';
                $post_active='';

                if (($current_program==$vals[1]&&!$sponsorships&&$page!='show_all_sponsorships')||($current_program==$vals[1]&&$sponsorships&&$page=='show_all_sponsorships')) {
                    $pre_active='<strong>';
                    $post_active='</strong>';
                }

                if ($sponsorships) {
                    if (isset($sponsorship_counts[$vals[1]])) {
                        $this_count= reset($sponsorship_counts[$vals[1]]);

                        $count= $this_count['total'];
                    } else {
                        $count= 0;
                    }
                } else {
                    $count= $this->countEntities($vals[1]);
                    // if($count<1)
                    // 	$count= '';
                }

                $vals[0]= strlen($vals[0]) > 30 ? substr($vals[0], 0, 30)."..." : $vals[0];
                
                // var_dump($sponsorships);
                if (isset($permissions->$p) && $permissions->$p == 1) {
                    $visible_count++;
                    if (in_array($vals[1], $program_ids)) {
                        $output .= "<li class='ellipsis'><a href=\"$url/$vals[1]\"><span class='badge pull-right'>".$count."</span>".$pre_active." $vals[0] ".$post_active."</a></li>\r\n";
                    } elseif (isset($permissions->manage_programs)&&$sponsorships==false) {
                        $output .= '<li><a href="'.URL::to('admin/program_settings', [$vals[1]]).'">'.$pre_active.$vals[0].$post_active."  <em>Setup Incomplete</em> <span class=\"icon ion-wrench\"></span>  </a></li>\r\n";
                    } elseif ($sponsorships==false) {
                        $output .= '<li><a href="#">'.$pre_active.$vals[0].$post_active."  <em>Setup Incomplete</em> <span class=\"icon ion-wrench\"></span>  </a></li>\r\n";
                    }
                }
            }
        }

        if ($program_count==0&&isset($permissions->manage_programs)) {
            $output.='<li><a href="'.URL::to('admin/manage_program').'">No programs exist, create one.</a></li>';
        }
        if ($program_count==0&&!isset($permissions->manage_programs)) {
            $output.='<li><a href="'.URL::to('#').'">No programs exist.</a></li>';
        }
        if ($visible_count==0&&isset($permissions->manage_programs)) {
            $output.='<li><a href="'.URL::to('admin/view_groups').'">No programs accessible, change permissions.</a></li>';
        }
        if ($visible_count==0&&!isset($permissions->manage_programs)) {
            $output.='<li><a href="'.URL::to('#').'">No programs are accessible.</a></li>';
        }


        //if($visible_count==0)

        //$output=var_dump($permissions);
        if ($first) {
            $output .= "</ul>\r\n";
        }
        
        return $output;
    }

    public function programsDropDown($current_program = false, $sponsorships = false)
    {
        $p_ids = [];

        $client= new Client;

        // array of programs with form attached
        $ps = Program::where('client_id', Session::get('client_id'))->get();
        foreach ($ps as $p) {
            if ($p->hysform_id > 0) {
                $p_ids[] = $p->id;
            }
        }
        $programs = Program::where('client_id', Session::get('client_id'))->first();
        
        $programs = $programs->presentChildrenAs('array', function ($item) {
            $output = ''.$item->name.'{'.$item->id.''; // separate with { for exploding in loop
            return $output;
        });

        $programs = $this->nesty($programs, $p_ids, $current_program, $sponsorships);
        
        return $programs;
    }

    public function getPrograms($client_id, $program_id)
    {

        $program_ids= explode('-', $program_id);
    
        if (count($program_ids)!=1) {
            $programs = [];
            
            foreach ($program_ids as $key => $id) {
                $programs[$key]= Program::where('client_id', $client_id)->find($id);

                if (!empty($programs[$key])) {
                    $settings[$key]=$programs[$key]->donor_hysform_id;
                } else {
                    return [false,"Error: A Program with an id of '".$id."' Doesn't exist."];
                }
            }

            if (count(array_unique($settings))<1) {
                return [false,"Error: No Settings found for the selected programs."];
            }
        } elseif (count($program_ids)==1) {
            $program=Program::where('client_id', $client_id)->find($program_ids[0]);

            if (count($program)>0||$program_ids[0]=='none') {
                return $program_ids;
            } else {
                return [false,"Error: Program with id ".$program_ids[0]. " doesn't exist."];
            }
        } else {
            return [false,'No Program id was found.'];
        }

        return $program_ids;
    }

    public function getFilledPrograms($client_id)
    {
        return Program::whereClientId($client_id)->get();
    }
    public function getFilledNonSubPrograms($client_id)
    {
        return Program::whereClientId($client_id)->where('link_id', '=', '0')->get();
    }

    public function getUnLinkedPrograms()
    {
        $programs = Program::whereClientId(Session::get('client_id'))->where('link_id', '=', '0')->where('lft', '>', '1')->get();

        $p_array= [];

        foreach ($programs as $program) {
            if (!empty($program->setting_id)&&!empty($program->hysform_id)) {
                $p_array[$program->id]=$program->name;
            }
        }

        return $p_array;
    }


    public function countEntities($program_id)
    {
        $client_id = Session::get('client_id');

        return count(Entity::whereClientId($client_id)->whereProgramId($program_id)->get());
    }

    public function isProgramSetup($program_id, $client_id = null)
    {

        $set = [];

        $program_ids= explode('-', $program_id);

        if (count($program_ids)>1) {
            foreach ($program_ids as $id) {
                $program = Program::where('client_id', $client_id)->find($id);
                if (empty($program)) {
                    $set[] = 'Error: Program #'.$id.' does not exist.';
                }
            }
        } elseif ($program_id != 'none') {
            $program = Program::find($program_id);
            
            if (is_object($program)) {
                if ($program->hysform_id==0) {
                    $set[]='Error: The sponsorship profile form has not been set for this program.';
                }
        
                if ($program->donor_hysform_id==0) {
                    $set[]='Error: The donor form has not been set for this program.';
                }
        
                if ($program->setting_id==0) {
                    $set[]='Error: The settings have not been set for this program.';
                }
        
                if ($program->link_id!=0&&!count(Program::find($program->link_id))) {
                    $set[]='Error: The Parent program for this program has been deleted.';
                }

                if ($client_id!=null&&$program->client_id!=$client_id) {
                    $set[]='Error: The Program does belong to this Client.';
                }
            } else {
                $set[] = 'Error: Program does not exist.';
            }
        }

        if (!empty($set)) {
            return(implode('<br>', $set));
        }
        
        return 'true';
    }

    public function getSettings($program_id)
    {
        $program = Program::find($program_id);
        
        if (count($program)) {
            $settings= Setting::find($program->setting_id);
            if (count($settings)) {
                $program_settings=json_decode($settings->program_settings);
                return $program_settings;
            }
        }
        return false;
    }

    public function getBaseSettings($program_id)
    {
        $program = Program::find($program_id);
        
        if (count($program)) {
            $settings= Setting::find($program->setting_id);
            if (count($settings)) {
                return $settings;
            }
        }
        return false;
    }

    public function getSettingsFromEntity($entity_id)
    {

        $entity= Entity::withTrashed()->find($entity_id);
        
        return $this->getSettings($entity->program_id);
    }

    public function getBaseSettingsFromEntity($entity_id)
    {

        $entity= Entity::withTrashed()->find($entity_id);
        
        return $this->getBaseSettings($entity->program_id);
    }

    public function getProgramType($program_id)
    {
        $settings= $this->getSettings($program_id);

        if ($settings) {
            return $settings->program_type;
        }
    }
    
    public function getProgramTypeFromEntity($entity_id)
    {
        $entity = Entity::withTrashed()->find($entity_id);

        if (count($entity)) {
            $program_id = $entity->program_id;
        } else {
            return false;
        }

        $settings= $this->getSettings($program_id);

        if ($settings) {
            return $settings->program_type;
        } else {
            return false;
        }
    }

    public function flushListFromSettings($setting_id)
    {
        foreach (Program::where('setting_id', $setting_id)->get() as $program) {
            Cache::forget('program_'.$program->id);
        }
    }
}
