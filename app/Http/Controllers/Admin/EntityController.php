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
use Program;
use Field;
use Session;
use DB;
use Entity;
use Upload;
use DonorEntity;
use Donor;
use Donorfield;
use Setting;
use URL;
use Carbon;
use Hysform;
use Report;
use AWS;
use FormArchive;
use Commitment;
use Donoremail;
use Note;
use Cache;
use Queue;
    
use App\Http\Controllers\Controller;

class EntityController extends Controller
{
        
    public function addEntity($id)
    {
        $entity = new Entity;

        $program = Program::where('client_id', Session::get('client_id'))->find($id);

        if (count($program)==0) {
            return "Error: Program Not Found.";
        }

        $fields = Field::where('hysform_id', $program->hysform_id)->orderBy('field_order')->get();
        $counts = $entity->getProgramCounts($program->id);
            
        if ($program->setting_id != 0) {
            $programData = $entity->getProgramTypeData($program->setting_id);
        } else {
            $programData = ['error' => 'You must first add Settings to this program'];
        }
        return view('admin.views.addEntity', [
            'fields' => $fields,
            'program' => $program,
            'programData' => $programData,
            'counts'    => $counts
        ]);
    }

        

    public function postAddEntity($program_id)
    {
        $program = Program::find($program_id);
        $count = $program->counter + 1;
        $counter = $program->prefix.$count;
            
        $data = Input::all();

        $rules=[
            'sp_num' => ['integer']
            ];

         $validator = Validator::make($data, $rules);

        if ($validator->passes()) {
            $program->counter = $count;
            $program->save();
                
            $fields = Field::whereHysformId($program->hysform_id)->get();
            foreach ($fields as $field) {
                $field_types[$field->field_key] = $field->field_type;
            }
                
                
            unset($data['_token']);
            $sp_num = '';
            $sp_amount = '';
            if (isset($data['sp_num'])) {
                $sp_num = $data['sp_num'];
                unset($data['sp_num']);
            }
            if (isset($data['sp_amount'])) {
                $sp_amount = $data['sp_amount'];
                unset($data['sp_amount']);
            }
                
            $entity = new Entity;
            $entity->client_id = Session::get('client_id');
            $entity->program_id = $program_id;
            $entity->sp_amount = $sp_amount;
            $entity->sp_num = $sp_num;
            $entity->status = 0;
            $entity->wait_time = Carbon::now();

            $hash = "id:{$entity->id}";
            $profile = [];
            foreach ($data as $k => $v) {
                // handle various types of fields
                if ($field_types[$k] == 'hysLink') {
                    $link = '';
                    foreach ($v as $part) {
                        if (!empty($part)) {
                            $link .= ''.$part.'|';
                        }
                    }
                    $v = substr($link, 0, -1); // Removes the last pipe
                }
                    
                if ($field_types[$k] == 'hysCheckbox') {
                    $v = json_encode($v);
                }
                    
                if ($field_types[$k] == 'hysTable') {
                    $v = json_encode($v);
                }
                    
                if ($field_types[$k] == 'hysCustomid') {
                    $v = $counter;
                }
                    
                $profile[$k] = "$v";
            }
                    
            // need error handling
            
            $entity->json_fields=json_encode($profile);

            $entity->save();

            // Remove the entities_list cache, so this entity will show up next time a sponsorship is added.
            $entities = Cache::forget('entities_list-'.Session::get('client_id'));

            //Reloading the table for this user and all users
            $entity->reloadEntitiesToCache($entity);

            return redirect('admin/edit_entity/'.$entity->id.'')
                ->withInput()
                ->with('message', 'Profile saved')
                ->with('alert', 'success');
        }
        Session::flash('message', 'There was a problem with your submission. Please see below for details');
        Session::flash('alert', 'danger');
        return redirect('admin/add_entity/'.$program_id.'')
            ->withErrors($validator)
            ->withInput();
    }
        
    public function getFieldOptions($program_id)
    {

        if ($program_id!='all') {
            $program = Program::find($program_id);
            $entityFields = Field::whereHysformId($program->hysform_id)->orderBy('field_order')->get();
            $donorFields = Donorfield::whereHysformId($program->donor_hysform_id)->orderBy('field_order')->get();
            $efs = [];
            $dfs = [];
                
            // foreach ($entityFields as $ef) {
            // 	if ($ef->field_type != 'hysTable' OR $ef->field_type != 'hysCheckbox') {
            // 		$field_keys[]=$ef->field_key;
            // 	}
            // }

            foreach ($entityFields as $ef) {
                if ($ef->field_type != 'hysTable' or $ef->field_type != 'hysCheckbox') {
                    $efs[] = ['field_key' => $ef->field_key, 'field_label' => $ef->field_label];
                }
            }
                
            foreach ($donorFields as $df) {
                if ($df->field_type != 'hysTable' or $df->field_type != 'hysCheckbox') {
                    $dfs[] = ['field_key' => $df->field_key, 'field_label' => $df->field_label];
                }
            }
            // place fields in array with key matching permissions for easy exclusion in template
            $fields['program-'.$program->id.''] = $efs;
            $fields['donor-'.$program->donor_hysform_id.''] = $dfs;
            $fields['program'] = $program;
        } else {
            //$entityFields = Field::whereClientId(Session::get('client_id'))->orderBy('field_order')->groupBy('field_key')->get();
            $f = new Field;
            $entityFields= Field::allActiveEntityFields()->get();
            $donorFields = Donorfield::allActiveDonorFields()->get();
            $efs = [];
            $dfs = [];

                
            foreach ($entityFields as $ef) {
                if ($ef->field_type != 'hysTable' or $ef->field_type != 'hysCheckbox') {
                    $efs[] = ['field_key' => $ef->field_key, 'field_label' => $ef->field_label];
                }
            }
                
            foreach ($donorFields as $df) {
                if ($df->field_type != 'hysTable' or $df->field_type != 'hysCheckbox') {
                    $dfs[] = ['field_key' => $df->field_key, 'field_label' => $df->field_label];
                }
            }
            // place fields in array with key matching permissions for easy exclusion in template
            $fields['program-all'] = $efs;
            $fields['donor-all'] = $dfs;
            $fields['program'] = 'all';
        }


        return $fields;
    }
        
    public function fieldOptions($program_id, $type)
    {
            

            $data = Input::all();

            $fieldOptions = $this->getFieldOptions($program_id);

            $program = $fieldOptions['program'];
            unset($fieldOptions['program']);
                

        if ($program_id=='all') {
            $fields = Field::allActiveEntityFields()->get();
            $reports = Report::whereHysformId(0)->get();
        } else {
            $program=Program::find($program_id);
            $fields = Field::where('hysform_id', $program->hysform_id)->orderBy('field_order')->get();
            $reports = Report::whereHysformId($program->hysform_id)->get();
        }

            $d=new Donor;
            // $entity=Entity::where('program_id',$program_id)->get()->first();
            $details = [];
                
        if ($type=='sponsorship') {
            $details = [];
        } else {
            $details=$d->getAmountDetailsForTable('0', $program_id);
        }


            $vars = [
                'program' => $program,
                'fieldOptions' => $fieldOptions,
                'fields' => $fields,
                'type' => $type,
                'reports' => $reports,
                'details'=> $details];

            return view('admin.views.fieldOptions', $vars);
    }
        
    public function postFieldOptions($program_id, $type = null)
    {

        $data = Input::all();
        unset($data['_token']);


        $client_id=Session::get('client_id');

        $d= new Donor;
        // $entity= Entity::where('program_id',$program_id)->get()->first();

        $details = [];
            
        // if(count($entity))
            $details= $d->getAmountDetailsForTable('0', $program_id);

            
        // save to admin preferences in redis
        $redis = RedisL4::connection();
        $user = Sentry::getUser();
        $hash = "admin:{$user->id}:program-$program_id";
            
        // return var_dump($data);

        if ($program_id=='all') {
            $efields = [];
            if (!empty($data['program'])) {
                $redis->hdel($hash, 'program');
                $entityFields = Field::allActiveEntityFields()->get();
                    
                foreach ($data['program'] as $field) {
                    foreach ($entityFields as $ef) {
                        if ($field == $ef->field_key) {
                            $efields[] = ['field_key' => $field, 'field_label' => $ef->field_label, 'field_type' => $ef->field_type, 'field_data' => $ef->field_data];
                        }
                    }
                    if ($field == 'thumb') {
                        $efields['thumb'] = true;
                    }
                    if ($field=='profile_link') {
                        $efields['profile_link'] = true;
                    }

                    if ($field == 'created_at') {
                        $efields['created_at'] = true;
                    }

                    if ($field == 'updated_at') {
                        $efields['updated_at'] = true;
                    }
                        
                    if ($field == 'manage') {
                        $efields['manage'] = true;
                    }

                    foreach ($details as $name => $detail) {
                        //make names the same as field inputs
                        $n=strtolower(str_replace(' ', '_', $name));
                        //If any of these names already exist, put them in the efields!
                        if ($field==$n) {
                            $efields[$n]=true;
                        }
                    }
                }
                    
                $fields['program'] = json_encode($efields);
            }
                
            $dfields = [];
            if (!empty($data['donor'])) {
                $redis->hdel($hash, 'donor');
                $donorFields = Donorfield::allActiveDonorFields()->get();
                    
                foreach ($data['donor'] as $field) {
                    foreach ($donorFields as $df) {
                        if ($field == $df->field_key) {
                            $dfields[] = ['field_key' => $field, 'field_label' => $df->field_label, 'field_type' => $df->field_type, 'field_data' => $df->field_data];
                        }
                    }
                    if ($field == 'email') {
                        $dfields['email'] = true;
                    }
                    if ($field == 'username') {
                        $dfields['username'] = true;
                    }
                    if ($field == 'amount') {
                        $dfields['amount'] = true;
                    }
                    if ($field == 'frequency') {
                        $dfields['frequency'] = true;
                    }
                    if ($field == 'until') {
                        $dfields['until'] = true;
                    }
                    if ($field == 'last') {
                        $dfields['last'] = true;
                    }
                    if ($field == 'next') {
                        $dfields['next'] = true;
                    }
                    if ($field == 'method') {
                        $dfields['method'] = true;
                    }

                    if ($field == 'donor_updated_at') {
                        $dfields['donor_updated_at'] = true;
                    }

                    if ($field == 'donor_created_at') {
                        $dfields['donor_created_at'] = true;
                    }

                    if ($field == 'sponsorship_created_at') {
                        $dfields['sponsorship_created_at'] = true;
                    }
                }
                $fields['donor'] = json_encode($dfields);
            }

            $redis->hmset($hash, $fields);
                
            // // save report
            //  if (!empty($data['report_name'])) {
            // 	$report = new Report;
            // 	$report->client_id = Session::get('client_id');
            // 	$report->hysform_id = 0;
            // 	$report->name = $data['report_name'];
            // 	$report->fields = json_encode($fields);
            // 	$report->save();

            //  }

            // Remove the Caches of the program table because of changing the fields
            $entity= new Entity;
            // $entity->clearCache($program_id);

            return 'Preferences Saved';
        } else {
            $program = Program::find($program_id);
                
            $efields = [];
            if (!empty($data['program'])) {
                $redis->hdel($hash, 'program');
                $entityFields = Field::whereHysformId($program->hysform_id)->orderBy('field_order')->get();
                    
                foreach ($data['program'] as $field) {
                    foreach ($entityFields as $ef) {
                        if ($field == $ef->field_key) {
                            $efields[] = ['field_key' => $field, 'field_label' => $ef->field_label, 'field_type' => $ef->field_type, 'field_data' => $ef->field_data];
                        }
                    }
                    if ($field == 'thumb') {
                        $efields['thumb'] = true;
                    }
                    if ($field=='profile_link') {
                        $efields['profile_link'] = true;
                    }

                    if ($field == 'created_at') {
                        $efields['created_at'] = true;
                    }

                    if ($field == 'updated_at') {
                        $efields['updated_at'] = true;
                    }
                        
                    if ($field == 'manage') {
                        $efields['manage'] = true;
                    }

                    foreach ($details as $name => $detail) {
                        //make names the same as field inputs
                        $n=strtolower(str_replace(' ', '_', $name));

                        //If any of these names already exist, put them in the efields!
                        if ($field==$n) {
                            $efields[$n]=true;
                        }
                    }
                }
                    
                $fields['program'] = json_encode($efields);
            }
                
            $dfields = [];
            if (!empty($data['donor'])) {
                $redis->hdel($hash, 'donor');
                $donorFields = Donorfield::whereHysformId($program->donor_hysform_id)->orderBy('field_order')->get();
                    
                foreach ($data['donor'] as $field) {
                    foreach ($donorFields as $df) {
                        if ($field == $df->field_key) {
                            $dfields[] = ['field_key' => $field, 'field_label' => $df->field_label, 'field_type' => $df->field_type, 'field_data' => $df->field_data];
                        }
                    }

                    if ($field == 'email') {
                            $dfields['email'] = true;
                    }
                    if ($field == 'username') {
                        $dfields['username'] = true;
                    }
                    if ($field == 'amount') {
                        $dfields['amount'] = true;
                    }
                    if ($field == 'frequency') {
                        $dfields['frequency'] = true;
                    }
                    if ($field == 'until') {
                        $dfields['until'] = true;
                    }
                    if ($field == 'last') {
                        $dfields['last'] = true;
                    }
                    if ($field == 'next') {
                        $dfields['next'] = true;
                    }
                    if ($field == 'method') {
                        $dfields['method'] = true;
                    }
                    if ($field == 'donor_updated_at') {
                        $dfields['donor_updated_at'] = true;
                    }
                    if ($field == 'donor_created_at') {
                        $dfields['donor_created_at'] = true;
                    }
                    if ($field == 'sponsorship_created_at') {
                        $dfields['sponsorship_created_at'] = true;
                    }
                }
                $fields['donor'] = json_encode($dfields);
            }
                            
            $redis->hmset($hash, $fields);
                
            // save report
            if (!empty($data['report_name'])) {
                $report = new Report;
                $report->client_id = Session::get('client_id');
                $report->hysform_id = $program->hysform_id;
                $report->name = $data['report_name'];
                $report->fields = json_encode($fields);
                $report->save();

                $entity = new Entity;
                // Remove the Cache of the program table because of changing the report
                $entity->clearEntityCache($program_id, $reload = ['entities'], $trashed_options = null);
            }

            return 'Preferences Saved';
        }
    }
        
    public function selectSavedReport($report_id, $program_id, $trashed = '')
    {
        $report = Report::find($report_id);
        $fields = json_decode($report->fields, true);
            
        // save to admin preferences in redis
        $redis = RedisL4::connection();
        $user = Sentry::getUser();
        $hash = "admin:{$user->id}:program-$program_id";
        $redis->hdel($hash, 'program');
        if (!empty($fields['donor'])) {
            $redis->hdel($hash, 'donor');
        }

        $fields= $this->omitInvalidFields($program_id, $fields);
            
        $redis->hmset($hash, $fields);
            
            
        $user=Sentry::getUser();
        $entity=new Entity;

        // // Remove the Cache of the program table because of changing the report
        // $entity->clearEntityCache($program_id,$reload=array('entities'));

            
        return redirect('admin/show_all_entities/'.$program_id.'/'.$trashed);
    }

    private function omitInvalidFields($program_id, $fields)
    {

        $fields=  (array) json_decode($fields['program']);
        $new_fields= [];
        $field_options= $this->getFieldOptions($program_id, 'program');

        foreach ($field_options['program-'.$program_id] as $o) {
            $options_array[]= $o['field_key'];
        }

        foreach ($fields as $k => $f) {
            if (is_object($f)) {
                if (!in_array($f->field_key, $options_array)) {
                    $missing_fields[] = $f->field_label;
                } else {
                    $new_fields[$k]=$f;
                }
            } else {
                $new_fields[$k]=$f;
            }
        }
        if (isset($missing_fields)) {
            $program=Program::find($program_id);
            $hysform= Hysform::find($program->hysform_id);
            $s='';
            $thiss='this';
            $was = 'was';
            $has = 'has';
            if (count($missing_fields)>1) {
                $s='s';
                $thiss='these';
                $was= 'were';
            }
            Session::flash('message', 'Warning: The following field'.$s.' '.$has.' been omitted from this report: <br>'.implode('<br>', $missing_fields).'<br><br> This happened because '.$thiss.' field'.$s.' '.$was.' removed from the <a href="'.URL::to('admin/manage_form/'.$program->hysform_id).'">'.$hysform->name. ' form.</a>');
            Session::flash('alert', 'warning');
        }

        return ['program'=> json_encode($new_fields)];
    }
        
    public function removeSavedReport($report_id, $program_id)
    {
        $report = Report::find($report_id);
        $report->delete();
        $entity = new Entity;
        // Remove the Cache of the program table because of changing the report
        $entity->clearEntityCache($program_id, $reload = ['entities'], $trashed_options = null);

        return redirect('admin/show_all_entities/'.$program_id);
    }
        
    public function showAllEntities($program_id, $trashed = false)
    {


        $e = new Entity;
        $vars = $e->getEntities($program_id, Session::get('client_id'), $trashed);

        if (isset($vars['error'])) {
            return $vars['error'];
        }

        return view('admin.views.showAllEntities')->with($vars);
    }
    
    public function showAllEntitiesTable($program_id, $trashed = false)
    {

        $e = new Entity;
        $user= Sentry::getUser();
        $vars = $e->getEntitiesTable($program_id, $user->id, $trashed);

        return view('admin.views.showAllEntitiesTable')->with($vars);
    }

    public function showAllEntitiesAjax($program_id, $trashed = false)
    {
        // check for saved preferences
        // if none load first 6 fields

        $program = Program::find($program_id);

        $d = new Donor;
        $details=$d->getAmountDetailsForWholeTable($program_id);
        // return var_dump($details);
        $redis = RedisL4::connection();
        $user = Sentry::getUser();
        $hash = "admin:{$user->id}:program-$program_id";
        $admin = $redis->hgetall($hash);
        $manage = false;
        $thumbnail = false;
        $profile_link = false;
        $created_at = false;
        $updated_at = false;
        $details_display = [];
            
        if (!empty($admin['program'])) {
            $fields = json_decode($admin['program']);
            if (isset($fields->manage)) {
                $manage = $fields->manage;
                unset($fields->manage);
            }
            if (isset($fields->thumb)) {
                $thumbnail = $fields->thumb;
                unset($fields->thumb);
            }
            if (isset($fields->profile_link)) {
                $profile_link= $fields->profile_link;
                unset($fields->profile_link);
            }

            if (isset($fields->created_at)) {
                $created_at = $fields->created_at;
                unset($fields->created_at);
            }
            if (isset($fields->updated_at)) {
                $updated_at = $fields->updated_at;
                unset($fields->updated_at);
            }

            foreach ($details as $name => $detail) {
                $n = strtolower(str_replace(' ', '_', $name));
                if (isset($fields->{$n})) {
                    $details_display[$n]=$name;
                    unset($fields->{$n});
                }
            }
        }

        if (!isset($fields)) {
            $fields = Field::where('hysform_id', $program->hysform_id)->orderBy('field_order')->take(6)->get();
            $manage = true;
            $thumbnail = true;
        }
            
        $e = new Entity;
        $vars = $e->getEntitiesAjax($program_id, URL::to(''), $trashed);

        $hashes= $vars['hashes'];
        $pipeline= $vars['pipeline'];

        $processed = [];

        foreach ($hashes as $k => $hash) {
            //Set entity Id as first column in table
            $profiles[$k][]= $k;

            //Set Manage buttons
            if ($manage && empty($program->link_id)) {
                $profiles[$k][] =$hash ['manage'];
            }

            //Add Thumbnail image
            if ($thumbnail) {
                $profiles[$k][]=$hash['thumbnail'];
            }

            if ($profile_link) {
                $profiles[$k][]=$hash['profile_link'];
            }

            //Set fields that the user has selected to display
            foreach ($fields as $f) {
                if (isset($f->field_key)) {
                    if (isset($pipeline[$k]['hys_profile'][$f->field_key])) {
                        $profiles[$k][]=$pipeline[$k]['hys_profile'][$f->field_key];
                    }
                }
            }
                
            if ($created_at) {
                $profiles[$k][] = $hash['created_at'];
            }
            if ($updated_at) {
                $profiles[$k][] = $hash['updated_at'];
            }

            foreach ($details_display as $name => $detail) {
                    $profiles[$k][]= $hash[$detail];
            }

            $processed[] = $profiles[$k];
        }

        return json_encode(['data'=>$processed]);
    }

    public function showAllSponsorships($program_id, $trashed = false)
    {

        $client_id =Session::get('client_id');
        $program = Program::where('client_id', $client_id)->find($program_id);

        if (count($program)==0&&$program_id!='all') {
            return "Error: Program Not Found.";
        }
            
        $e = new Entity;

        $vars = $e->getSponsorships($client_id, $program_id, $trashed);

        return view('admin.views.showAllSponsorships')
        ->with($vars);
    }
        
    public function sponsoredTable($program_id, $trashed = false)
    {

        $e = new Entity;

        $vars = $e->getSponsorshipsTable($program_id, $trashed);

        return view('admin.views.showAllSponsorshipsTable')
        ->with($vars);
    }

    public function showAllSponsorshipsAjax($program_id, $trashed = false)
    {

        $processed = [];
            
        $d = new Donor;
        $entity=Entity::where('program_id', $program_id)->first();
        $client_id=Session::get('client_id');

        $nameFields= [];
        $donorNameFields= [];
        if ($program_id=='all') {
            $d_fields=Donorfield::where('client_id', $client_id)->orderBy('field_order')->take(3)->get();
            $p_fields=Field::where('client_id', $client_id)->orderBy('field_order')->take(3)->get();
            $details = [];
        } else {
            $program= Program::find($program_id);
            $details= $d->getAmountDetailsForTable($entity);
            $d_fields=$donorFields = Donorfield::where('hysform_id', $program->donor_hysform_id)->orderBy('field_order')->take(3)->get();
            $p_fields=Field::where('hysform_id', $program->hysform_id)->orderBy('field_order')->take(3)->get();
        }

        $hashes=[];
            
        $nameFields=[];

            
        $link_id='';

        $user = Sentry::getUser();
        $hash = "admin:{$user->id}:program-".$program_id;
        $redis = RedisL4::connection();
        $admin = $redis->hgetall($hash);
        $manage = false;
        $thumb = false;
        $created_at = false;
        $updated_at = false;
        $donor_created_at = false;
        $donor_updated_at = false;
        $sponsorship_created_at = false;
        $email = false;
        $username = false;
        $amount = false;
        $frequency = false;
        $until = false;
        $last = false;
        $next = false;
        $method = false;

        $details_display=[];
            
        if (!empty($admin)) {
            $programFields = json_decode($admin['program']);
                
            if (!empty($admin['donor'])) {
                $donorFields = json_decode($admin['donor']);
            } else {
                //return "no Donorfields in Redis!";
                $donorFields = $d_fields;
            }
                
            if (isset($programFields->manage)) {
                $manage = $programFields->manage;
                unset($programFields->manage);
            }
            foreach ($details as $name => $detail) {
                $n=strtolower(str_replace(' ', '_', $name));
                if (isset($programFields->{$n})) {
                    $details_display[$n]=$name;
                    unset($programFields->{$n});
                }
            }
        }

        if (isset($programFields->thumb)) {
            $thumb = $programFields->thumb;
            unset($programFields->thumb);
        }
        if (isset($programFields->created_at)) {
            $created_at = $programFields->created_at;
            unset($programFields->created_at);
        }
        if (isset($programFields->updated_at)) {
            $updated_at = $programFields->updated_at;
            unset($programFields->updated_at);
        }
        if (isset($donorFields->email)) {
            $email = $donorFields->email;
            unset($donorFields->email);
        }
        if (isset($donorFields->username)) {
            $username = $donorFields->username;
            unset($donorFields->username);
        }
        if (isset($donorFields->amount)) {
            $amount = $donorFields->amount;
            unset($donorFields->amount);
        }
        if (isset($donorFields->frequency)) {
            $frequency = $donorFields->frequency;
            unset($donorFields->frequency);
        }
        if (isset($donorFields->until)) {
            $until = $donorFields->until;
            unset($donorFields->until);
        }
        if (isset($donorFields->last)) {
            $last = $donorFields->last;
            unset($donorFields->last);
        }
        if (isset($donorFields->next)) {
            $next = $donorFields->next;
            unset($donorFields->next);
        }
        if (isset($donorFields->method)) {
            $method = $donorFields->method;
            unset($donorFields->method);
        }
        if (isset($donorFields->donor_created_at)) {
            $donor_created_at = $donorFields->donor_created_at;
            unset($donorFields->donor_created_at);
        }
        if (isset($donorFields->donor_updated_at)) {
            $donor_updated_at = $donorFields->donor_updated_at;
            unset($donorFields->donor_updated_at);
        }
        if (isset($donorFields->sponsorship_created_at)) {
            $sponsorship_created_at = $donorFields->sponsorship_created_at;
            unset($donorFields->sponsorship_created_at);
        }
            
        if (!isset($programFields)) {
            $programFields = $p_fields;
            $donorFields = $d_fields;
            $manage = true;
            $thumb = true;
        }

        $e = new Entity;

        $vars = $e->getSponsorshipsAjax($client_id, $trashed, URL::to(''));

        $hashes = $vars['hashes'];
        $pipeline = $vars ['pipeline'];

        if (isset($hashes)) {
            foreach ($hashes as $k => $hash) {
                if ($program_id=='all'||$hash['hys_program']==$program_id||$hash['hys_entity_program']==$program_id) {
                    //Set the Donor id as the first field.
                    $entityProfile = [$k];
                    $donorProfile=[];

                    if ($manage && empty($program->link_id)) {
                        $entityProfile[] = $hash['manage'];
                    }

                    if ($program_id=='all') {
                        //Display to which program this sponsorship belongs.
                        if ($hash['hys_program']=='0') {
                            $entityProfile[] = $hash['hys_entity_program_name'];
                        } else {
                            $entityProfile[] = $hash['hys_program_name'];
                        }
                    }

                    //Set Thumnail image
                    if ($thumb) {
                        $entityProfile[]=$pipeline[$k]['thumb'];
                    }

                    //Set the fields that the particular user has selected to view
                    foreach ($programFields as $f) {
                        if (isset($f->field_key)) {
                            if (isset($pipeline[$k]['hys_profile'][$f->field_key])) {
                                $entityProfile[]=$pipeline[$k]['hys_profile'][$f->field_key];
                            } else {
                                $entityProfile[]='';
                            }
                        }
                    }

                    //Set the fields that come from the optional details display
                    foreach ($details_display as $name => $detail) {
                        if (isset($hash[$detail])) {
                            $entityProfile[]= $hash[$detail];
                        }
                    }

                    //Set the fields that the particular user has selected to view
                    foreach ($donorFields as $f) {
                        if (isset($f->field_key)) {
                            if (isset($pipeline[$k]['hys_donor_profile'][$f->field_key])) {
                                $donorProfile[]=$pipeline[$k]['hys_donor_profile'][$f->field_key];
                            } else {
                                $donorProfile[]='';
                            }
                        }
                    }

                    if ($created_at) {
                        $entityProfile[] = $hash['created_at'];
                    }
                    if ($updated_at) {
                        $entityProfile[] = $hash['updated_at'];
                    }
                    if ($email) {
                        $donorProfile[] = $hash['email'];
                    }
                    if ($username) {
                        $donorProfile[] = $hash['username'];
                    }
                        
                    if ($amount) {
                        $donorProfile[] = $hash['amount'];
                    }
                    if ($frequency) {
                        $donorProfile[] = $hash['frequency'];
                    }
                    if ($until) {
                        $donorProfile[] = $hash['until'];
                    }
                    if ($last) {
                        $donorProfile[] = $hash['last'];
                    }
                    if ($next) {
                        $donorProfile[] = $hash['next'];
                    }
                    if ($method) {
                        $donorProfile[] = $hash['method'];
                    }

                    if ($donor_created_at) {
                        $donorProfile[] = $hash['donor_created_at'];
                    }
                    if ($donor_updated_at) {
                        $donorProfile[] = $hash['donor_updated_at'];
                    }
                    if ($sponsorship_created_at) {
                        $donorProfile[] = $hash['sponsorship_created_at'];
                    }

                    $processed[]=array_merge($entityProfile, $donorProfile);
                }
            }
        }

        return json_encode(['data'=>$processed]);
    }

        
    public function editEntity($id)
    {
            
        $entity = Entity::where('client_id', Session::get('client_id'))->withTrashed()->find($id);

        if (count($entity)==0) {
            return "Error: Entity Not Found.";
        }
        $entity= $entity->toArray();

        $profile = json_decode($entity['json_fields'], true);
        $program = Program::find($entity['program_id']);
        $fields = Field::where('hysform_id', $program->hysform_id)->orderBy('field_order')->get();
            
        $donor = new Donor;
        $entity_name = $donor->getEntityName($entity['id']);
        $name = $entity_name['name'];
            
        // submit only forms
        $submit_ids = explode(',', $program->entity_submit);
        $submit = Hysform::whereClientId(Session::get('client_id'))->whereIn('id', $submit_ids)->get();
            
        // get sponsors
        $e = new Entity;
        $sponsors = $e->getSponsors($id);
            
        $upload = new Upload;
        $uploads = Entity::withTrashed()->find($id)->uploads()->where('profile', 1)->first();
        $profilePic = '';
        $profileThumb = '';
        if (!empty($uploads)) {
            $profilePic = $uploads->makeAWSlink($uploads);
            $profileThumb = $uploads->makeAWSlinkThumb($uploads);
        }
            
        $programData = $e->getProgramTypeData($program->setting_id, $id);

        foreach ($entity as $k => $v) {
            $profile[$k] = $v;
        }

        $settings= $program->getSettings($program->id);

        $donations=[];
        if ($settings->program_type=='funding') {
            $donations= $e->getDonations($id);
        }

        $d = new Donor;
        $details = $d->getAmountDetails($id);

        Session::put('redirect', URL::full());

        // return var_dump($details);
            
        return view('admin.views.editEntity', [
            'profile' => $profile,
            'fields' => $fields,
            'profilePic' => $profilePic,
            'donations' => $donations,
            'entity'    => $entity,
            'profilePic' => $profilePic,
            'profileThumb' => $profileThumb,
            'sponsors' => $sponsors,
            'programData' => $programData,
            'submit' => $submit,
            'program' => $program,
            'name' => strip_tags($name),
            'settings'=>$settings,
            'details' => $details,
        ]);
    }
        
    public function postEditEntity($entity_id)
    {
        $data = Input::all();
        unset($data['_token']);

        $rules=[
            'sp_num' => ['integer']
            ];

         $validator = Validator::make($data, $rules);

        if ($validator->passes()) {
            $entity = Entity::withTrashed()->find($entity_id);
            $archived = false;
            if (!empty($entity->deleted_at)) { // if archived make sure it stays archived
                $archived = true;
            }
                
            $program = Program::find($entity->program_id);
            $fields = Field::whereHysformId($program->hysform_id)->get();
            foreach ($fields as $field) {
                $field_types[$field->field_key] = $field->field_type;
            }

            $sp_num = '';
            $sp_amount = '';
            $updateStatus = false;
            if (isset($data['sp_num'])) {
                $sp_num = $data['sp_num'];
                        
                //If the sp_num has been changed, reset the status!
                $updateStatus = true;
                unset($data['sp_num']);
            }

            if (isset($data['sp_amount']) && is_numeric($data['sp_amount'])) {
                $sp_amount = $data['sp_amount'];

                //If the sp_amount has been changed, reset the status!
                $updateStatus = true;
                unset($data['sp_amount']);
            }

            $entity->sp_amount = $sp_amount;
            $entity->sp_num = $sp_num;

                
                
            $profile = [];
            foreach ($data as $k => $v) {
                // handle various types of fields
                if (isset($field_types[$k]) && $field_types[$k] == 'hysLink') {
                    $link = '';
                    foreach ($v as $part) {
                        if (!empty($part)) {
                            $link .= ''.$part.'|';
                        }
                    }
                    $v = substr($link, 0, -1); // Removes the last pipe
                }
                    
                if (isset($field_types[$k]) && $field_types[$k] == 'hysCheckbox') {
                    $v = json_encode($v);
                }
                    
                if (isset($field_types[$k]) && $field_types[$k] == 'hysTable') {
                    $v = json_encode($v);
                }
                    
                $profile[$k] = "$v";
            }

            $entity->json_fields = json_encode($profile);
            $entity->save();

            if ($updateStatus) {
                $d = new Donor;
                $d->setStatus($entity_id);
            }
                
            // in case the mysql db was not updated
            $entity->touch();
            if ($archived == true) {
                $entity->delete(); // make sure entity remains archived.
            }
            
            return redirect('admin/edit_entity/'.$entity->id.'')
                ->withInput()
                ->with('message', Session::get('message').'Profile saved')
                ->with('alert', 'success');
        }
        return redirect('admin/edit_entity/'.$entity_id.'')
                ->witherrors($validator)
                ->with('message', Session::get('message').'Error')
                ->with('alert', 'danger');
    }

    public function moveEntity($entity_id)
    {
        $redis = RedisL4::connection();
        $profile = $redis->hgetall("id:$entity_id");

        $entity = Entity::where('client_id', Session::get('client_id'))->withTrashed()->find($entity_id);
        if (count($entity)==0) {
            return "Error: Entity Not Found.";
        }

        $entity = Entity::withTrashed()->find($entity_id)->toArray();
        $program = Program::find($entity['program_id']);
        foreach ($entity as $k => $v) {
            $profile[$k] = $v;
        }

        $donor = new Donor;
        $entity_name = $donor->getEntityName($entity_id);
        $name = $entity_name['name'];

        // submit only forms
        $submit_ids = explode(',', $program->entity_submit);
        $submit = Hysform::whereClientId(Session::get('client_id'))->whereIn('id', $submit_ids)->get();
            
        $compatible_programs= $this->getCompatiblePrograms($entity_id);

        //get The thumbnail for the entity
        $upload = new Upload;
        $uploads = Entity::withTrashed()->find($entity_id)->uploads()->where('profile', 1)->first();
        $profileThumb = '';
        if (!empty($uploads)) {
            $profileThumb = $uploads->makeAWSlinkThumb($uploads);
        }

        return view('admin.views.moveEntity', [
            'compatible_programs'   => $compatible_programs['result'],
            'count'                 => $compatible_programs['count'],
            'name'                  => $name,
            'profile'               => $profile,
            'entity'                => $entity,
            'submit'                => $submit,
            'program'               => $program,
            'profileThumb'          => $profileThumb]);
    }



    public function postMoveEntity($entity_id)
    {
        $data= Input::all();

        $entity = Entity::where('client_id', Session::get('client_id'))->withTrashed()->find($entity_id);
        if (count($entity)==0) {
            return "Error: Entity Not Found.";
        }

        $compatible_programs= $this->getCompatiblePrograms($entity_id);

        if (isset($data['new_program'])) {
            $new_program_id= $data['new_program'];

            if (array_key_exists($new_program_id, $compatible_programs['result'])) {
                $entity=Entity::find($entity_id);

                $old_program_id=$entity->program_id;

                $entity->program_id=$new_program_id;

                $entity->save();

                //Clear cache for both tables
                // $entity->clearCache($old_program_id);
                // $entity->clearCache($new_program_id);
                    

                Session::flash('message', 'Entity successfully moved.');
                Session::flash('alert', 'success');

                return redirect('admin/edit_entity/'.$entity_id);
            }
        }
            
            Session::flash('message', 'Failed to move Entity.');
            Session::flash('alert', 'danger');
            return redirect('admin/edit_entity/'.$entity_id);
    }

    private function getCompatiblePrograms($entity_id)
    {
        $client_id = Session::get('client_id');

        $entity = Entity::withTrashed()->find($entity_id);

        $program = Program::find($entity->program_id);

        $compatible_programs = Program::where('client_id', $client_id) //Must be the correct client
                                        ->where('hysform_id', $program->hysform_id) //Sponsorship forms must match
                                        // ->where('donor_hysform_id',$program->donor_hysform_id) //Donor Forms must match
                                        ->where('link_id', '0') //Must not be Sub-Program
                                        ->where('id', '!=', $entity->program_id)
                                        ->get();

        $permissions = Session::get('permissions');

        $count= count($compatible_programs);
        $result_array=[];
        foreach ($compatible_programs as $p) {
            $result_array[$p->id]=$p->name;

            if (!isset($permissions->{'program-'.$p->id})) {
                unset($result_array[$p->id]); //Remove program if the user doesn't have permission to it.
            }
        }

        return(['result'=>$result_array,'count'=>$count]);
    }
        
    public function removeEntity($entity_id)
    {

        $entity = Entity::where('client_id', Session::get('client_id'))->withTrashed()->find($entity_id);
        if (count($entity)==0) {
            return "Error: Entity Not Found.";
        }

        $this->removeEntities($entity->program_id, $entity_id);
            
        return Redirect::back()
            ->with('message', 'Successfully Archived')
            ->with('alert', 'success');
    }

    public function removeEntities($program_id, $entity_id = null)
    {
        // archive any sponsorships and remove commitments
        $an_entity = new Entity;
        $d = new Donor;

        if ($entity_id==null) {
            $entity_ids= Input::get('entity_ids');
        } else {
            $entity_ids= [$entity_id];
        }

        $DonorEntity = DonorEntity::where('client_id', Session::get('client_id'))->whereIn('entity_id', $entity_ids)->get();
            
        if (count($DonorEntity)) {
            foreach ($DonorEntity as $de) {
                $de->delete();
                $d->setStatus($de->entity_id);
            }
        }
            
        $commitment = Commitment::where('type', '1')->where('client_id', Session::get('client_id'))->whereIn('designation', $entity_ids)->get();
            
        if (count($commitment)) {
            foreach ($commitment as $c) {
                $c->delete();
            }
        }

        // then remove the entity
        $entities = Entity::where('client_id', Session::get('client_id'))->where('program_id', $program_id)->whereIn('id', $entity_ids)->get();
            
        foreach ($entities as $entity) {
            $entity->delete();
        }

        // refresh the cache
        $an_entity->reloadEntitiesToCache($entities);

        Cache::tags('programs_menu-'.Session::get('client_id'))->flush();
    }
        
    public function activateEntity($entity_id)
    {
        
        $entity = Entity::onlyTrashed()->where('client_id', Session::get('client_id'))->withTrashed()->find($entity_id);
        if (count($entity)==0) {
            return "Error: Entity Not Found.";
        }

        $entity->restore();
            
        $d = new Donor;
        $d->setStatus($entity_id);

        $entity->reloadEntitiesToCache($entity);

        Cache::tags('programs_menu-'.Session::get('client_id'))->flush();


        return Redirect::back()
            ->with('message', 'Successfully Restored')
            ->with('alert', 'success');
    }

    public function activateEntities($program_id)
    {

        $a_donor = new Donor;
        $an_entity = new Entity;

        $entity_ids =Input::get('entity_ids');

        $entities = Entity::onlyTrashed()->where('client_id', Session::get('client_id'))->where('program_id', $program_id)->whereIn('id', $entity_ids)->get();
            
        foreach ($entities as $entity) {
            $entity->restore();
            $a_donor->setStatus($entity->id);
        }

        $an_entity->reloadEntitiesToCache($entities);
        Cache::tags('programs_menu-'.Session::get('client_id'))->flush();
    }
        
    public function permanentlyDeleteEntity($entity_id)
    {
        // permanently delete the entity and associated records
        $entity=Entity::onlyTrashed()->find($entity_id);

        if (empty($entity)) {
            return redirect('admin')
            ->with('alert', 'warning')
            ->with('message', 'The entity could not be found.');
        }

        $program_id= $entity->program_id;

        Input::replace(['entity_ids'=>[$entity_id]]);

        $this->deleteEntities($entity->program_id);
        
        return redirect('admin/show_all_entities/'.$program_id.'/1')
            ->with('alert', 'success')
            ->with('message', 'Permanently Deleted.');
    }

    public function deleteEntities($program_id)
    {
        // permanently delete the entity and associated records

        $entity_ids = Input::get('entity_ids');

        $entities = Entity::onlyTrashed()->where('client_id', Session::get('client_id'))->where('program_id', $program_id)->whereIn('id', $entity_ids)->forceDelete();

        Commitment::withTrashed()->where('client_id', Session::get('client_id'))->where('type', 1)->whereIn('designation', $entity_ids)->forceDelete();
            
        $donoremails = Donoremail::withTrashed()->whereIn('entity_id', $entity_ids)->get();
        foreach ($donoremails as $de) {
            $subemails = Donoremail::withTrashed()->where('parent_id', $de->id)->forceDelete();
            $de->forceDelete();
        }
            
        DonorEntity::withTrashed()->where('client_id', Session::get('client_id'))->whereIn('entity_id', $entity_ids)->forceDelete();
            
        FormArchive::withTrashed()->where('client_id', Session::get('client_id'))->whereIn('entity_id', $entity_ids)->forceDelete();
            
        $notes = Note::withTrashed()->whereIn('entity_id', $entity_ids)->forceDelete();
            
        $uploads = Upload::withTrashed()->where('client_id', Session::get('client_id'))->whereIn('entity_id', $entity_ids)->get();

        $e = new Entity;

        $e->removeEntitiesFromTrashedCache($program_id, $entity_ids);

        foreach ($uploads as $upload) {
            $s3 = AWS::get('s3');
            $result = $s3->deleteObjects([
                'Bucket' => 'hys',
                'Objects' => [
                    [
                        'Key' => $upload->name,
                    ],
                ],
            ]);
                
            $upload->forceDelete();
        }
    }

    public function moveEntitiesToSQL()
    {
            $entities = Entity::onlyTrashed()->get();
            
            // $entities = Entity::all();

            //This gets all the entities at once with the redis pipeline
            $pipeline = RedisL4::pipeline(function ($pipe) use ($entities) {
                foreach ($entities as $entity) {
                    $pipe->hgetall("id:{$entity->id}");
                }
            });

            $i=0;
            $changed=0;
        foreach ($entities as $entity) {
            $programs[$entity->program_id]='1';
            if (isset($pipeline[$i])) {
                $entity->json_fields=json_encode($pipeline[$i]);
                $entity->save();
                $changed++;
            }
            $i++;
        }

            $e = new Entity;

        foreach ($programs as $k => $v) {
            $e->clearEntityCache($k, null, null);
        }

            return 'total Entities Copied to SQL = '. $changed;
    }

    public function updateAllStatuses($client_id, $key)
    {
        if ($key == 'lksdnljfs54nefonsdlkfnsle46fjlskd34jnfklsdnflksenflksenf') {
            $donor = new Donor;
            $i=0;
            $entities =  Entity::where('client_id', $client_id)->get();
            foreach ($entities as $entity) {
                $i++;
                $donor->setStatus($entity->id, true);
            }

            Cache::forget('entities_list-'.$client_id);
            $programs = Program::where('client_id', $client_id)->get();
            foreach ($programs as $program) {
                    $data = [
                        'client_id'=>$client_id,
                        'url'=>URL::to(''),
                        'program_id'=>$program->id];

                    Cache::forget('program_'.$program->id);
                    Queue::push('reloadEntityCache', $data);
            }


            return('Updated the Status of '.$i.' Entities for client_id #'.$client_id);
        }
            return;
    }
}
