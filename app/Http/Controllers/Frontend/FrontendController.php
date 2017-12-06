<?php  namespace App\Controllers\Frontend;
 
    use Auth;
use BaseController;
use Form;
use Input;
use Redirect;
use Sentry;
use View;
use RedisL4;
use Validator;
use Program;
use Field;
use Session;
use DB;
use Entity;
use DataGrid;
use Environment;
use StdClass;
use User;
use Upload;
use Client;
use Setting;
use URL;
use Donor;
use Cache;

use App\Http\Controllers\Controller;

class FrontendController extends Controller
{


    function createFrontendSession()
    {
  
        $time = time(); // get current unix time
        $session_id = sha1($time . 'EVERSP'); // encrypt and salt it for fun
       
        $redis = RedisL4::connection();
            
        $redis->hset($session_id, 'logged_in', 'false');
        
        $redis->expire($session_id, 3600);
     
        return $session_id;
    }



    public function DisplayTitlesAndFiles($client_id, $program_id, $session_id = null)
    {

        $redis = RedisL4::connection();
    
        if ($session_id==null||$redis->exists($session_id)!=1) {
            $session_id=$this->createFrontendSession();
        }
        $redis->expire($session_id, 3600);

        $key = 'program_'.$program_id;

        // Cache::forget($key);

        //get the program id(s)
        $the_data = Cache::remember($key, 30, function () use ($client_id, $program_id) {
        
            $program= new Program;

            $setup=$program->isProgramSetup($program_id, $client_id);
            if ($setup!='true') {
                return ['error'=>$setup];
            }

            $program_ids=$program->getPrograms($client_id, $program_id);

            //get the entities that are unsponsored (status == 0)
            if (count($program_ids)==1) {
                $program = Program::find($program_id);
                $settings= $program->getSettings($program_id);

                if (empty($settings->display_all)) {
                    $entities = Entity::allEntitiesByProgram($program_id)->where('client_id', $client_id)->where('status', 0)->take(5000)->get();
                } else {
                    $entities = Entity::allEntitiesByProgram($program_id)->where('client_id', $client_id)->take(5000)->get();
                }
            } else {
                //This will spit out the error if it occured.
                if ($program_ids[0]==false) {
                    return $program_ids[1];
                }

                //This will use the settings of the first specified program in the list of programs
                //(ie. in program_id=3-4-5-7, program number 3's settings will be selected.

                $program = Program::find($program_ids[0]);

                $settings= $program->getSettings($program_ids[0]);
                if (empty($settings->display_all)) {
                    $entities = Entity::allEntitiesByPrograms($program_ids)->where('client_id', $client_id)->where('status', 0)->take(5000)->get();
                } else {
                    $entities = Entity::allEntitiesByPrograms($program_ids)->where('client_id', $client_id)->take(5000)->get();
                }
            }

            //Get the entity fields
            $fields = Field::where('client_id', $client_id)->where('hysform_id', $program->hysform_id)->where('permissions', '=', 'public')->orderBy('field_order')->get();
        

            //Gives the current Session another 60 minutes


            $setting=Setting::find($program->setting_id);
            $placeholder='';
            $program_settings= [];
            if (count($setting)) {
                $program_settings = (array) json_decode($setting->program_settings);

                if (isset($program_settings['placeholder'])&&$program_settings['placeholder']!='') {
                    $placeholder=$program_settings['placeholder'];
                } else {
                    $placeholder=URL::to('/images/placeholder.gif');
                }
            } else {
                return ['error'=>"Error: This program must have settings assigned to it by the Administrator."];
            }
        
            $donor = new Donor;
            $processed=[];

            foreach (Program::whereIn('id', $program_ids)->get() as $p) {
                $multi_programs[$p->id]=$p;
            }


        
            $e = new Entity;

            $first_thumb='';

            $details= [];

            // if(count($program_ids)==1)
            $details = $donor->getAmountDetailsForWholeTable($program_id, $entities);

            $entities_list= $entities->lists('id');
            $uploads = [];

            if (!empty($entities_list)) {
                $uploads= Upload::whereIn('entity_id', $entities_list)->where('profile', '1')->get()->lists('name', 'entity_id');
            }
            $upload = new Upload;


            //This allows us to display only the title fields
            $title_fields = Field::where('client_id', $client_id)->where('hysform_id', $program->hysform_id)->where('is_title', '=', '1')->where('permissions', '=', 'public')->orderBy('field_order')->get();

            //The frontend user may only filter by fields that are 'sortable'
            $sort_fields = Field::where('client_id', $client_id)->where('hysform_id', $program->hysform_id)->where('sortable', '=', '1')->orderBy('field_order')->get();
        
            $search_fields= Field::where('client_id', $client_id)->where('hysform_id', $program->hysform_id)->where('permissions', '=', 'public')->orderBy('field_order')->get();

            $filter_fields = Field::where('client_id', $client_id)->where('hysform_id', $program->hysform_id)->where('filter', '=', '1')->orderBy('field_order')->get();

            $display_info=false;
            if (isset($program_settings['display_info'])) {
                $display_info=$program_settings['display_info'];
            }
            $display_percent=false;
            if (isset($program_settings['display_percent'])) {
                $display_percent=$program_settings['display_percent'];
            }
            $currency_symbol='$';
            if (isset($program_settings['currency_symbol'])) {
                $currency_symbol=$program_settings['currency_symbol'];
            }

            foreach ($program_ids as $id) {
                $program_names[$id]=Program::where('client_id', $client_id)->where('id', $id)->pluck('name');
            }
            $height= 0;
            foreach ($entities as $entity) {
                $profile = $e->formatProfile(json_decode($entity->json_fields, true), $fields);
                $profile['hysmanage'] = $entity->id;
                $profile['status']=$entity->status;
            
                $profile['entity_info']='';
                $profile['entity_percent_display']='';
                $height= 20;

                if (!empty($details)) {
                    if ($details[$entity->id]['Percent Complete']>100) {
                        $details[$entity->id]['Percent Complete']= 100;
                    }

                    $profile['entity_percent']=$details[$entity->id]['Percent Complete'];

                    if ($display_percent=='1') {
                        $profile['entity_percent_display']='<div class="progress" style="max-width:90%;text-align:center;margin: 0 auto;">';
                        $profile['entity_percent_display'].= '<div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: '.$profile['entity_percent'].'%; min-width: 2em;">';
                        $profile['entity_percent_display'].= $profile['entity_percent'].'%';
                        $profile['entity_percent_display'].= '</div>';
                        $profile['entity_percent_display'].='</div>';
                        $height+=40;
                    }


                    if ($display_info=='1') {
                        if ($program_settings['program_type']=='number'||$profile['entity_percent']==100) {
                            $profile['entity_info']= '<p><span>'.$details[$entity->id]['info'].'</span></p>';
                        } else {
                            $profile['entity_info']= '<p><span>'.$program_settings['currency_symbol'].$details[$entity->id]['info'].'</span></p>';
                        }
                        $height+=40;
                    }
                }

                // STOP CALLING SQL inside loops!
                //If there are multiple programs, check this particular one for a sub-program link
                if (count($program_ids)>1) {
                    $the_program=$multi_programs[$entity->program_id];
                } else {
                    $the_program=$program;
                }

                //If the program is a link, use the new Program ID for searching by program name
                if ($the_program->link_id!=0) {
                    $profile['hysprogram_id']= $the_program->id;
                } else {
                    $profile['hysprogram_id']= $entity->program_id;
                }
            

                if (isset($uploads[$entity->id])) {
                    $profile['file_link']= $upload->makeAWSlinkThumb($uploads[$entity->id]);
                } else {
                    $profile['file_link']=$placeholder;
                }

                $temp_title=[];
                $profile['title_fields']= '';

                foreach ($title_fields as $field) {
                    if (isset($profile[$field->field_key])) {
                        $temp_title[]=$profile[$field->field_key];
                    }
                }
                $profile['title_fields']= "<span>".implode(' </span><span> ', $temp_title).'</span>';

                $profile['search_fields']= '';
                foreach ($search_fields as $field) {
                    if (isset($profile[$field->field_key])) {
                        $profile['search_fields'].= ' '.$profile[$field->field_key];
                    }
                }
            
                $profile['program_sort']='';
                if (count($program_names)>1) {
                    $profile['program_sort']='<span style="display:none;" class="hysprogram_id'.$profile['hysprogram_id'].' hidden">'.$program_names[$profile['hysprogram_id']].'</span>';
                }

                $profile['sort_fields']='';
                foreach ($sort_fields as $field) {
                    if (isset($profile[$field->field_key])) {
                        $profile['sort_fields'].='<span style="display:none" class="'.$field->field_key.' hidden">'.$profile[$field->field_key].'</span>';
                    } else {
                        $profile['sort_fields'].='<span style="display:none" class="'.$field->field_key.' hidden"></span>';
                    }
                }

                $profile['filter_fields']='';
                foreach ($filter_fields as $field) {
                    if ($field->filter=='1') {
                        if (isset($profile[$field->field_key])) {
                            $profile['filter_fields'].='<span style="display:none" class="hysfilter-'.$field->field_key.str_replace(' ', '_', $profile[$field->field_key]).' hidden">'.$profile[$field->field_key].'</span>';
                        }
                    }
                }


                $profile['url']=URL::to('frontend/view_entity', [ $client_id, $program_id, $profile['hysmanage']]);


                $processed[] = $profile;
            }

        
        
            $text_front = Setting::find($program->setting_id)->text_front;

            $sorting='';
            if (isset($program_settings['sorting'])) {
                $sorting=$program_settings['sorting'];
            }
        
            //Turn off sorting, if there are no sort fields
            if (count($sort_fields)==0) {
                $sorting='';
            }

            $disable_program_link='';
            if (isset($program_settings['disable_program_link'])) {
                $disable_program_link=$program_settings['disable_program_link'];
            }

        

        

            $filtering='';
            foreach ($filter_fields as $field) {
                if ($field->filter=='1') {
                    $filtering='1';
                }
            }
            $program_sort='';

        

            $client= Client::find($client_id);
        

            return [
                    'client_id'     =>  $client_id,
                    'program_id'    =>  $program_id,
                    'program_name'  =>  $program->name,
                    'processed'     =>  $processed,
                    'first_thumb'   =>  $first_thumb,
                    'client'        =>  $client,
                    'sort_fields'   =>  $sort_fields,
                    'filter_fields' =>  $filter_fields,
                    'text_front'    =>  $text_front,
                    'sorting'       =>  $sorting,
                    'filtering'     =>  $filtering,
                    'program_names' =>  $program_names,
                    'disable_program_link' => $disable_program_link,
                    'currency_symbol' => $currency_symbol,
                    'height'    => $height,
                    ];
        });

    

        $the_data['session_id']=$session_id;

        $e= new Entity;
        $total=$e->getTotal($session_id);
        $the_data['total']=$total;

        if (isset($the_data['error'])) {
            return $the_data['error'];
        }

        
        $stop_time= microtime(true);

        // return var_dump( $stop_time - $start_time);


        
        return view('frontend.views.displayPublicEntities', $the_data);
    }



    public function DisplayTitlesAndFilesPagination($client_id, $program_id, $session_id = null)
    {

        $redis = RedisL4::connection();
        
        if ($session_id==null||$redis->exists($session_id)!=1) {
            $session_id=$this->createFrontendSession();
        }
        $redis->expire($session_id, 3600);

        //Set the current session to pagination. After the session dies, it will revert back to infinite
        $redis->hset($session_id, 'pagination', 'true');

        $key = 'program_'.$program_id;

        // Cache::forget($key);

        //get the program id(s)
        $the_data = Cache::remember($key, 30, function () use ($client_id, $program_id) {
            
            $program= new Program;

            $setup=$program->isProgramSetup($program_id, $client_id);
            if ($setup!='true') {
                return ['error'=>$setup];
            }

            $program_ids=$program->getPrograms($client_id, $program_id);

            //get the entities that are unsponsored (status == 0)
            if (count($program_ids)==1) {
                $program = Program::find($program_id);
                $settings= $program->getSettings($program_id);

                if (empty($settings->display_all)) {
                    $entities = Entity::allEntitiesByProgram($program_id)->where('client_id', $client_id)->where('status', 0)->take(1000)->get();
                } else {
                    $entities = Entity::allEntitiesByProgram($program_id)->where('client_id', $client_id)->take(1000)->get();
                }
            } else {
                //This will spit out the error if it occured.
                if ($program_ids[0]==false) {
                    return $program_ids[1];
                }

                //This will use the settings of the first specified program in the list of programs
                //(ie. in program_id=3-4-5-7, program number 3's settings will be selected.

                $program = Program::find($program_ids[0]);

                $settings= $program->getSettings($program_ids[0]);
                if (empty($settings->display_all)) {
                    $entities = Entity::allEntitiesByPrograms($program_ids)->where('client_id', $client_id)->where('status', 0)->take(1000)->get();
                } else {
                    $entities = Entity::allEntitiesByPrograms($program_ids)->where('client_id', $client_id)->take(1000)->get();
                }
            }

            //Get the entity fields
            $fields = Field::where('client_id', $client_id)->where('hysform_id', $program->hysform_id)->where('permissions', '=', 'public')->orderBy('field_order')->get();
            

            //Gives the current Session another 60 minutes


            $setting=Setting::find($program->setting_id);
            $placeholder='';
            $program_settings= [];
            if (count($setting)) {
                $program_settings = (array) json_decode($setting->program_settings);

                if (isset($program_settings['placeholder'])&&$program_settings['placeholder']!='') {
                    $placeholder=$program_settings['placeholder'];
                } else {
                    $placeholder=URL::to('/images/placeholder.gif');
                }
            } else {
                return ['error'=>"Error: This program must have settings assigned to it by the Administrator."];
            }
            
            $donor = new Donor;
            $processed=[];

            foreach (Program::whereIn('id', $program_ids)->get() as $p) {
                $multi_programs[$p->id]=$p;
            }


            
            $e = new Entity;

            $first_thumb='';

            $details= [];

            // if(count($program_ids)==1)
            $details = $donor->getAmountDetailsForWholeTable($program_id, $entities);

            $entities_list= $entities->lists('id');
            $uploads = [];

            if (!empty($entities_list)) {
                $uploads= Upload::whereIn('entity_id', $entities_list)->where('profile', '1')->get()->lists('name', 'entity_id');
            }
                $upload = new Upload;


            //This allows us to display only the title fields
            $title_fields = Field::where('client_id', $client_id)->where('hysform_id', $program->hysform_id)->where('is_title', '=', '1')->where('permissions', '=', 'public')->orderBy('field_order')->get();

            //The frontend user may only filter by fields that are 'sortable'
            $sort_fields = Field::where('client_id', $client_id)->where('hysform_id', $program->hysform_id)->where('sortable', '=', '1')->orderBy('field_order')->get();
            
            $search_fields= Field::where('client_id', $client_id)->where('hysform_id', $program->hysform_id)->where('permissions', '=', 'public')->orderBy('field_order')->get();

            $filter_fields = Field::where('client_id', $client_id)->where('hysform_id', $program->hysform_id)->where('filter', '=', '1')->orderBy('field_order')->get();

            $display_info=false;
            if (isset($program_settings['display_info'])) {
                $display_info=$program_settings['display_info'];
            }
            $display_percent=false;
            if (isset($program_settings['display_percent'])) {
                $display_percent=$program_settings['display_percent'];
            }
            $currency_symbol='$';
            if (isset($program_settings['currency_symbol'])) {
                $currency_symbol=$program_settings['currency_symbol'];
            }

            foreach ($program_ids as $id) {
                $program_names[$id]=Program::where('client_id', $client_id)->where('id', $id)->pluck('name');
            }
            $height= 0;
            foreach ($entities as $entity) {
                $profile = $e->formatProfile(json_decode($entity->json_fields, true), $fields);
                $profile['hysmanage'] = $entity->id;
                $profile['status']=$entity->status;
                
                $profile['entity_info']='';
                $profile['entity_percent_display']='';
                $height= 20;

                if (!empty($details)) {
                    if ($details[$entity->id]['Percent Complete']>100) {
                        $details[$entity->id]['Percent Complete']= 100;
                    }

                    $profile['entity_percent']=$details[$entity->id]['Percent Complete'];

                    if ($display_percent=='1') {
                        $profile['entity_percent_display']='<div class="progress" style="max-width:90%;text-align:center;margin: 0 auto;">';
                        $profile['entity_percent_display'].= '<div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: '.$profile['entity_percent'].'%; min-width: 2em;">';
                        $profile['entity_percent_display'].= $profile['entity_percent'].'%';
                        $profile['entity_percent_display'].= '</div>';
                        $profile['entity_percent_display'].='</div>';
                        $height+=40;
                    }


                    if ($display_info=='1') {
                        if ($program_settings['program_type']=='number'||$profile['entity_percent']==100) {
                            $profile['entity_info']= '<p><span>'.$details[$entity->id]['info'].'</span></p>';
                        } else {
                            $profile['entity_info']= '<p><span>'.$program_settings['currency_symbol'].$details[$entity->id]['info'].'</span></p>';
                        }
                        $height+=40;
                    }
                }

                // STOP CALLING SQL inside loops!
                //If there are multiple programs, check this particular one for a sub-program link
                if (count($program_ids)>1) {
                    $the_program=$multi_programs[$entity->program_id];
                } else {
                    $the_program=$program;
                }

                //If the program is a link, use the new Program ID for searching by program name
                if ($the_program->link_id!=0) {
                    $profile['hysprogram_id']= $the_program->id;
                } else {
                    $profile['hysprogram_id']= $entity->program_id;
                }
                

                if (isset($uploads[$entity->id])) {
                    $profile['file_link']= $upload->makeAWSlinkThumb($uploads[$entity->id]);
                } else {
                    $profile['file_link']=$placeholder;
                }

                $temp_title=[];
                $profile['title_fields']= '';

                foreach ($title_fields as $field) {
                    if (isset($profile[$field->field_key])) {
                        $temp_title[]=$profile[$field->field_key];
                    }
                }
                $profile['title_fields']= "<span>".implode(' </span><span> ', $temp_title).'</span>';

                $profile['search_fields']= '';
                foreach ($search_fields as $field) {
                    if (isset($profile[$field->field_key])) {
                        $profile['search_fields'].= ' '.$profile[$field->field_key];
                    }
                }
                
                $profile['program_sort']='';
                if (count($program_names)>1) {
                    $profile['program_sort']='<span style="display:none;" class="hysprogram_id'.$profile['hysprogram_id'].' hidden">'.$program_names[$profile['hysprogram_id']].'</span>';
                }

                $profile['sort_fields']='';
                foreach ($sort_fields as $field) {
                    if (isset($profile[$field->field_key])) {
                        $profile['sort_fields'].='<span style="display:none" class="'.$field->field_key.' hidden">'.$profile[$field->field_key].'</span>';
                    } else {
                        $profile['sort_fields'].='<span style="display:none" class="'.$field->field_key.' hidden"></span>';
                    }
                }

                $profile['filter_fields']='';
                foreach ($filter_fields as $field) {
                    if ($field->filter=='1') {
                        if (isset($profile[$field->field_key])) {
                            $profile['filter_fields'].='<span style="display:none" class="hysfilter-'.$field->field_key.str_replace(' ', '_', $profile[$field->field_key]).' hidden">'.$profile[$field->field_key].'</span>';
                        }
                    }
                }


                $profile['url']=URL::to('frontend/view_entity', [ $client_id, $program_id, $profile['hysmanage']]);


                $processed[] = $profile;
            }

            
            
            $text_front = Setting::find($program->setting_id)->text_front;

            $sorting='';
            if (isset($program_settings['sorting'])) {
                $sorting=$program_settings['sorting'];
            }
            
            //Turn off sorting, if there are no sort fields
            if (count($sort_fields)==0) {
                $sorting='';
            }

            $disable_program_link='';
            if (isset($program_settings['disable_program_link'])) {
                $disable_program_link=$program_settings['disable_program_link'];
            }

            

            

            $filtering='';
            foreach ($filter_fields as $field) {
                if ($field->filter=='1') {
                    $filtering='1';
                }
            }
            $program_sort='';

            

            $client= Client::find($client_id);
            

            return [
                        'client_id'     =>  $client_id,
                        'program_id'    =>  $program_id,
                        'program_name'  =>  $program->name,
                        'processed'     =>  $processed,
                        'first_thumb'   =>  $first_thumb,
                        'client'        =>  $client,
                        'sort_fields'   =>  $sort_fields,
                        'filter_fields' =>  $filter_fields,
                        'text_front'    =>  $text_front,
                        'sorting'       =>  $sorting,
                        'filtering'     =>  $filtering,
                        'program_names' =>  $program_names,
                        'disable_program_link' => $disable_program_link,
                        'currency_symbol' => $currency_symbol,
                        'height'    => $height,
                        ];
        });

        

            $the_data['session_id']=$session_id;

            $e= new Entity;
            $total=$e->getTotal($session_id);
            $the_data['total']=$total;

        if (isset($the_data['error'])) {
            return $the_data['error'];
        }

            
            $stop_time= microtime(true);

            // return var_dump( $stop_time - $start_time);

            return view('frontend.views.displayPublicEntitiesPagination', $the_data);
    }
}
