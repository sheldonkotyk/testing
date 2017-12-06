<?php

namespace App\Models;

use Carbon\Carbon;
use Cartalyst\Sentry\Facades\Laravel\Sentry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;

class Entity extends Model
{

    use SoftDeletes;

    protected $dates = ['deleted_at'];


    //This function is for the 'sub-program' Feature
    public function scopeAllEntitiesByProgram($query, $id)
    {
        $program= Program::find($id);
        if ($program->link_id!=0) {
            $id=$program->link_id;
        }

        return $query->whereProgramId($id);
    }
    
    public function scopeAllEntitiesByPrograms($query, $ids)
    {
        $i=0;
        foreach ($ids as $id) {
            $program= Program::find($id);
            if ($program->link_id!=0) {
                $id=$program->link_id;
            }

            $i++;
            if ($i==1) {
                $raw='(program_id = '.$id;
            } else {
                $raw.=' or program_id = '.$id;
            }
        }
            $raw.=')';

        return $query->whereRaw($raw);
    }
    
    /**
    *  Returns an array of all entities in a program. Includes all profile information and the array keys are the database ID.
    **/
    public function allEntities($program_id)
    {
        $program = Program::find($program_id);
        $entities = Entity::where('program_id', $program_id)->get();
        $fields = Field::where('hysform_id', $program->hysform_id)->orderBy('field_order')->get();
        
        $profiles = [];

        foreach ($entities as $entity) {
            $id = $entity->id;
            $profiles[$id] = json_decode($entity->json_fields, true);
            foreach ($entity->attributes as $k => $v) {
                $profiles[$id][$k] = $v;
            }
            
            $id = '';
        }
        
        return $profiles;
    }
    
    public function uploads()
    {
        return $this->hasMany('App\Models\Upload');
    }
    
    public function program()
    {
        return $this->belongsTo('App\Models\Program');
    }
    
    public function donors()
    {
        return $this->belongsToMany('App\Models\Donor', 'donor_entity')->whereNull('donor_entity.deleted_at');
    }

    public function clearEntityCache($program_id, $reload = null, $trashed_options = null)
    {

        if ($trashed_options==null) {
            $trashed_options =['','1','available','sponsored','unsponsored'];
        }

        if ($reload==null) {
            $reload= ['entities','data','frontend'];
        }

        if (in_array('frontend', $reload)) {
            Cache::forget('program_'.$program_id);
        }

        if (in_array('entities', $reload)) {
            $program = Program::find($program_id);
            Cache::tags('programs_menu-'.$program->client_id)->flush();
            Cache::tags('showallentities-'.$program_id)->flush();
            Cache::forget('entities_list-'.$program->client_id);
            // foreach($trashed_options as $trashed)
            // 	Cache::forget('showallentities-'.$program_id.'-'.$trashed);
        }

        if (in_array('data', $reload)) {
            foreach ($trashed_options as $trashed) {
                Cache::forget('showallentitiesajax-'.$program_id.'-'.$trashed);
            }
        }
    }

    //Make this function do what it otta!

    public function removeEntitiesFromTrashedCache($program_id, $entity_ids)
    {
            
            $cache= Cache::get('showallentitiesajax-'.$program_id.'-1');

            //crawl through the cached tables and remove this entity from them all (if it exists, that is.)
            $mod = false;
        if ($cache!=null) {
            foreach ($entity_ids as $k => $entity_id) {
                if (isset($cache['hashes'][$entity_id])) {
                    $mod = true;
                    //Empty out the current contents of the cache for this entity.
                    unset($cache['pipeline'][$entity_id]);
                    unset($cache['hashes'][$entity_id]);
                }
            }
        }

        if ($mod) {
            Cache::put('showallentitiesajax-'.$program_id.'-1', $cache, 1440);
        }
    }

    public function reloadEntityCaches($program_id, $reload, $trashed_options)
    {

        //queue reload of Donors table
        $program=Program::find($program_id);
        $cache_data['program_id']=$program_id;
        $cache_data['client_id'] = $program->client_id;
        $cache_data['url']= URL::to('');

        Cache::tags('programs_menu-'.$program->client_id)->flush();
        
        if ($reload==null) {
            $cache_data['reload']= ['entities','data','frontend'];
        }

        if ($trashed_options==null) {
            $cache_data['trashed_options'] =['','1','available','sponsored','unsponsored'];
        }

        Queue::push('reloadEntityCache', $cache_data);
    }


    public function reloadEntitiesToCache($entities, $url = false)
    {
        if ($url===false) {
            $url = URL::to('');
        }
        if (!count($entities)) {
            return false;
        }

        //change to multiple entities if it's just one.
        if (isset($entities->id)) {
            $entities= Entity::withTrashed()->where('id', $entities->id)->get();
        }

        //Clear cache for entity counts
        $e = new Entity;
        $first= $entities->first();
        $program = Program::find($first->program_id);
        $e->clearEntityCache($first->program_id, ['entities','frontend'], null);



        //Reload Sponsorships cache here!
        $entity_id_array = [];
        foreach ($entities as $entity) {
            $entity_id_array[]=$entity->id; //There is an error here for some reason!
        }

        $des=DonorEntity::whereIn('entity_id', $entity_id_array)->get();
        $ens=Entity::has('donors', '=', '0');

        $this->reloadSponsorshipsToCache($des);

        //first figure out where this entity belongs in the cache tables
        foreach ($entities as $entity) {
            if ($entity->deleted_at==null) {
                $tables[$entity->id]['1']=false;
                $tables[$entity->id]['']=true;

                if ($entity->status=='0') {
                    $tables[$entity->id]['available'] = true;
                } else {
                    $tables[$entity->id]['available'] = false;
                }

                if ($entity->status=='1') {
                    $tables[$entity->id]['sponsored'] = true;
                } else {
                    $tables[$entity->id]['sponsored']= false;
                }

                if ($ens->find($entity->id)!=null) {
                    $tables[$entity->id]['unsponsored'] = true;
                } else {
                    $tables[$entity->id]['unsponsored'] = false;
                }
            } else {
                $tables[$entity->id]['1']=true;
                $tables[$entity->id]['']= false;
                $tables[$entity->id]['available'] = false;
                $tables[$entity->id]['sponsored'] = false;
                $tables[$entity->id]['unsponsored'] = false;
            }
        }

        $caches=[];

        foreach ($tables[$entities->first()->id] as $k => $t) {
            $caches[$k]= Cache::get('showallentitiesajax-'.$entities->first()->program_id.'-'.$k);
        }

        $d = new Donor;

        $details_display= $d->getAmountDetailsForWholeTable($entity->program_id);
        
        $details= $d->getAmountDetailsForWholeTable($entity->program_id, $entities);

        //crawl through the cached tables and remove this entity from them all (if it exists, that is.)
        foreach ($caches as $k => $cache) {
            if ($cache!=null) {
                foreach ($entities as $entity) {
                    if (isset($cache['hashes'][$entity->id])) {
                        //Empty out the current contents of the cache for this entity.
                        unset($caches[$k]['pipeline'][$entity->id]);
                        unset($caches[$k]['hashes'][$entity->id]);
                    }
                }
            }
        }

        $fields = Field::where('hysform_id', $program->hysform_id)->orderBy('field_order')->get();
            
        // get name fields for linking in view
        $nameFields = [];
        
        //Exclude date and name fields from receiving links in table due because they are title fields.
        $nf = Field::where('hysform_id', $program->hysform_id)->where('is_title', 1)->where('field_type', '!=', 'hysDate')->where('field_type', '!=', 'hysAge')->orderBy('field_order')->get();
        foreach ($nf as $fk) {
            $nameFields[] = $fk->field_key;
        }


        $entities_list = $entities->lists('id');
        $uploads= [];

        if (!empty($entities_list)) {
            $uploads= Upload::whereIn('entity_id', $entities_list)->where('profile', '1')->get()->lists('name', 'entity_id');
        }

        $upload = new Upload;
        

        foreach ($entities as $entity) {
            //Get the updated Hashed fields from mysql
            $new_hash[$entity->id] = [
                'id' => $entity->id,
                'manage' => '<small class="manage">
										<span class="label label-info"><a href="'. $url.'/admin/edit_entity/'.$entity->id.'"><span class="glyphicon glyphicon-pencil"></span> Edit</a></span>
										<span class="label label-info"><a href="'. $url.'/admin/upload_file/entity/'.$entity->id.'"><span class="glyphicon glyphicon-file"></span> Files</a></span> '
                                        .'</small>',
                'created_at' =>  '<span class="hidden">'.strtotime($entity->created_at).'</span>'.Carbon::createFromTimeStamp(strtotime($entity->created_at))->toFormattedDateString(),
                'updated_at' =>  '<span class="hidden">'.strtotime($entity->updated_at).'</span>'.Carbon::createFromTimeStamp(strtotime($entity->updated_at))->toFormattedDateString()];

            if (isset($uploads[$entity->id])) {
                $picarray = explode('.', $uploads[$entity->id]);
                $thumb = $picarray[0].'_t_.'.$picarray[1];
                $new_hash[$entity->id]['thumbnail'] = '<img src="https://s3-us-west-1.amazonaws.com/hys/' . $thumb . '" width="30px" />';
                $new_hash[$entity->id]['profile_link']= 'https://s3-us-west-1.amazonaws.com/hys/'.$uploads[$entity->id];
            } else {
                $new_hash[$entity->id]['thumbnail']='';
                $new_hash[$entity->id]['profile_link']='';
            }

            $new_pipeline[$entity->id]=json_decode($entity->json_fields, true);

            if (!empty($details_display)) {
                foreach ($details_display as $name => $detail) {
                    $new_hash[$entity->id][$name]= $details[$entity->id][$name];
                }
            }

            $profile = [];

            foreach ($fields as $f) {
                if (isset($new_pipeline[$entity->id][$f->field_key]) && in_array($f->field_key, $nameFields) && empty($program->link_id)) {
                    $profile[$f->field_key] = '<a href="'. $url.'/admin/edit_entity/'.$entity->id.'">'.$new_pipeline[$entity->id][$f->field_key].'</a>';
                } else {
                    if (isset($new_pipeline[$entity->id][$f->field_key])) {
                        $profile[$f->field_key] = $new_pipeline[$entity->id][$f->field_key];
                    } else {
                        $profile[$f->field_key]= '';
                    }
                }
            }
                $new_pipeline[$entity->id]['hys_profile']=$e->formatProfile($profile, $fields);
        }

        //run through the caches and put them back into redis
        foreach ($caches as $k => $cache) {
            if ($cache!=null) {
                $mod = false;
                foreach ($entities as $entity) {
                    if ($tables[$entity->id][$k]===true) {
                            $mod = true;
                            //Set the new hash and pipeline
                            $cache['hashes'][$entity->id]=$new_hash[$entity->id];
                            $cache['pipeline'][$entity->id]=$new_pipeline[$entity->id];
                    }
                }

                if (isset($mod)) { //Slap the new data back on the cache!
                    Cache::put('showallentitiesajax-'.$entities->first()->program_id.'-'.$k, $cache, 1440);
                }
            }
        }
        return true;
    }

    public function getEntities($program_id, $client_id, $trashed)
    {

        $key = 'showallentities-'.$program_id.'-'.$trashed;

        $vars = Cache::tags('showallentities-'.$program_id)->remember($key, 10080, function () use ($program_id, $client_id, $trashed) {

            $program = Program::where('client_id', $client_id)->find($program_id);

            if (count($program)==0) {
                return ['error'=>"Error: Program Not Found."];
            }

            $reports = Report::whereHysformId($program->hysform_id)->get();

            $entity = new Entity;
            $counts = $entity->getProgramCounts($program_id);

            return $vars = [
                'program'   => $program,
                'reports'   => $reports,
                'trashed'   => $trashed,
                'counts'    => $counts,
                'recipients'=> '1'
                ];
        });

        return $vars;
    }

    public function getEntitiesTable($program_id, $user_id, $trashed)
    {

        $program = Program::find($program_id);

        $trashed_names= [
        '1'=>"Archived",
        'unsponsored' => "Un-Sponsored",
        '0' => '',
        'sponsored' => "Fully Sponsored",
        'available' => "Available"];

        $trashed_name = $trashed_names[$trashed];
        
        $d = new Donor;
        $entity = Entity::where('program_id', $program_id)->first();
        $details = $d->getAmountDetailsForTable('0', $program_id);
        $redis = RedisL4::connection();
        $user = Sentry::getUser();
        $hash = "admin:{$user->id}:program-$program_id";
        $admin = $redis->hgetall($hash);
        
        // $redis->delete($hash);

        $manage = false;
        $thumbnail = false;
        $profile_link= false;
        $created_at = false;
        $updated_at = false;
        $details_display = [];
        $offset =0;
        $nowrap=['0'=>'',
                        '1'=>'',
                        '2'=>'',
                        '3'=>'',
                        '4'=>'',
                        '5'=>'',
                        '6'=>''];
                        
        if (!empty($admin['program'])) {
            $fields = json_decode($admin['program']);
            if (isset($fields->manage)) {
                $nowrap['0'] = '1';
                $offset = 1;
                $manage = $fields->manage;
                unset($fields->manage);
            }
            if (isset($fields->thumb)) {
                $nowrap['1'] = '1';
                $thumbnail = $fields->thumb;
                unset($fields->thumb);
            }
            if (isset($fields->profile_link)) {
                $nowrap['2']='1';
                $profile_link= $fields->profile_link;
                unset($fields->profile_link);
            }
            if (isset($fields->created_at)) {
                $nowrap['3'] = '1';
                $created_at = $fields->created_at;
                unset($fields->created_at);
            }
            if (isset($fields->updated_at)) {
                $nowrap['4'] = '1';
                $updated_at = $fields->updated_at;
                unset($fields->updated_at);
            }
            $i= 5;
            foreach ($details as $name => $detail) {
                $n = strtolower(str_replace(' ', '_', $name));
                if (isset($fields->{$n})) {
                    $nowrap[$i] = '1';
                    $i++;
                    $details_display[$n] = $name;
                    unset($fields->{$n});
                }
            }
            //Set nowrap settings for datatables.
            $client_fields_count=   count((array)$fields);
            $to_splice=[];

            foreach ((array)$fields as $f) {
                $to_splice[]='';
            }
            
            if (!empty($to_splice)) {
                array_splice($nowrap, $offset, 0, $to_splice);
            }
        }
        


        if (!isset($fields)) {
            $fields = Field::where('hysform_id', $program->hysform_id)->orderBy('field_order')->take(6)->get();
            $manage = true;
            $thumbnail = true;
        }
        
        // get name fields for linking in view
        $nameFields = [];
        
        $nf = Field::where('hysform_id', $program->hysform_id)->where('is_title', 1)->orderBy('field_order')->get();
        foreach ($nf as $fk) {
            $nameFields[] = $fk->field_key;
        }
        
        $vars =  [
            'fields' => $fields,
            'nameFields' => $nameFields,
            'program' => $program,
            'manage' => $manage,
            'thumbnail' => $thumbnail,
            'profile_link' => $profile_link,
            'created_at' => $created_at,
            'updated_at' => $updated_at,
            'trashed' => $trashed,
            'trashed_name' => $trashed_name,
            'details_display' => $details_display,
            'nowrap'    => json_encode($nowrap)];

        return $vars;
    }
    

    public function getEntitiesAjax($program_id, $url, $trashed)
    {

        $key= 'showallentitiesajax-'.$program_id.'-'.$trashed;

        // Cache::forget($key);
        
        $cached_vars = Cache::remember($key, 10080, function () use ($program_id, $url, $trashed, $key) {
            $loading = Cache::get($key.'loading');
            if (empty($loading)) {
                Cache::put($key.'loading', '1', 5);
                $program = Program::find($program_id);
                $d = new Donor;
                $e = new Entity;

                $details= $d->getAmountDetailsForWholeTable($program_id);

                if ($trashed == '1') {
                    $entities = Entity::allEntitiesByProgram($program_id)->onlyTrashed()->get();
                } elseif ($trashed=='available') {
                    $entities = Entity::allEntitiesByProgram($program_id)->where('status', '0')->get();
                } elseif ($trashed=='sponsored') {
                    $entities = Entity::allEntitiesByProgram($program_id)->where('status', '1')->get();
                } elseif ($trashed=='unsponsored') {
                    $entities = Entity::allEntitiesByProgram($program_id)->has('donors', '=', '0')->get();
                } else {
                    $entities = Entity::allEntitiesByProgram($program_id)->get();
                }

                $e_details=$d->getAmountDetailsForWholeTable($program_id, $entities);

                $indexed_pipeline = [];

                $fields = Field::where('hysform_id', $program->hysform_id)->orderBy('field_order')->get();
                
                // get name fields for linking in view
                $nameFields = [];
                
                //get all title fields except for date and age fields (we will add links to these fields later)
                $nf = Field::where('hysform_id', $program->hysform_id)->where('is_title', 1)->where('field_type', '!=', 'hysDate')->where('field_type', '!=', 'hysAge')->orderBy('field_order')->get();
                foreach ($nf as $fk) {
                    $nameFields[] = $fk->field_key;
                }

                $entities_list = $entities->lists('id');
                $uploads= [];

                if (!empty($entities_list)) {
                    $uploads= Upload::whereIn('entity_id', $entities_list)->where('profile', '1')->get()->lists('name', 'entity_id');
                }

                $upload = new Upload;
                
                foreach ($entities as $entity) {
                    $hashes[$entity->id] = [
                        'id'    => $entity->id,
                        'manage' => '<small class="manage">
											<span class="label label-info"><a href="'. $url.'/admin/edit_entity/'.$entity->id.'"><span class="glyphicon glyphicon-pencil"></span> Edit</a></span>
											<span class="label label-info"><a href="'. $url.'/admin/upload_file/entity/'.$entity->id.'"><span class="glyphicon glyphicon-file"></span> Files</a></span> '
                                            .'</small>',
                        'created_at' =>  '<span class="hidden">'.strtotime($entity->created_at).'</span>'. Carbon::createFromTimeStamp(strtotime($entity->created_at))->toFormattedDateString(),
                        'updated_at' =>  '<span class="hidden">'.strtotime($entity->update_at).'</span>'.Carbon::createFromTimeStamp(strtotime($entity->updated_at))->toFormattedDateString()];

                    if (isset($uploads[$entity->id])) {
                        $pic = $uploads[$entity->id];

                        $picarray = explode('.', $pic);
                            
                        if (isset($picarray[0]) && isset($picarray[1])) {
                            $thumb = $picarray[0].'_t_.'.$picarray[1];
                            $hashes[$entity->id]['thumbnail'] = '<img src="https://s3-us-west-1.amazonaws.com/hys/' . $thumb . '" width="30px" />';
                            $hashes[$entity->id]['profile_link'] = 'https://s3-us-west-1.amazonaws.com/hys/'.$uploads[$entity->id];
                        } else {
                            $hashes[$entity->id]['thumbnail']='';
                            $hashes[$entity->id]['profile_link']='';
                        }
                    } else {
                        $hashes[$entity->id]['thumbnail']='';
                        $hashes[$entity->id]['profile_link']='';
                    }

                    $indexed_pipeline[$entity->id]=json_decode($entity->json_fields, true);
                    
                    $profile = [];

                    foreach ($fields as $f) {
                        if (isset($indexed_pipeline[$entity->id][$f->field_key]) && in_array($f->field_key, $nameFields) && empty($program->link_id)) {
                            $profile[$f->field_key] = '<a href="'. $url.'/admin/edit_entity/'.$entity->id.'">'.$indexed_pipeline[$entity->id][$f->field_key].'</a>';
                        } else {
                            if (isset($indexed_pipeline[$entity->id][$f->field_key])) {
                                $profile[$f->field_key] = $indexed_pipeline[$entity->id][$f->field_key];
                            } else {
                                $profile[$f->field_key]= '';
                            }
                        }
                    }
                    
                    $indexed_pipeline[$entity->id]['hys_profile'] = $e->formatProfile($profile, $fields);

                    if (!empty($details)) {
                        foreach ($details as $name => $detail) {
                            $hashes[$entity->id][$name]= $e_details[$entity->id][$name];
                        }
                    }
                }


                Cache::forget($key.'loading');

                if (!isset($hashes)) {
                    return ['hashes'=>[],'pipeline'=>[]];
                }

                return ['hashes'=>$hashes,'pipeline'=>$indexed_pipeline];
            } else {
                return ['hashes'=>[],'pipeline'=>[]];
            }
        });

        return $cached_vars;
    }

    public function getSponsorships($client_id, $program_id, $trashed)
    {

        $key = 'showallsponsorships-'.$program_id.'-'.$trashed;
        $vars = Cache::tags('sponsorships-'.$client_id)->remember($key, 10080, function () use ($client_id, $program_id, $trashed) {

            if ($program_id == 'all') {
                    $counts = [];
                    $program = 'all';
            } else {
                $program = Program::find($program_id);
                $entity = new Entity;
                $counts = $entity->getProgramCounts($program_id);
            }

                return [
                    'program'   => $program,
                    'trashed'   => $trashed,
                    'counts'    => $counts,
                    'sponsorships' => '1'
                    ];
        });
        return $vars;
    }

    public function getSponsorshipsTable($program_id, $trashed)
    {

        $d = new Donor;


        $entity = Entity::where('program_id', $program_id)->first();

        $client_id = Session::get('client_id');

        if ($trashed) {
            $donorEntities = DonorEntity::onlyTrashed()->where('client_id', $client_id)->get();
        } else {
            $donorEntities = DonorEntity::where('client_id', $client_id)->get();
        }

        if ($program_id=='all') {
            $entities = Entity::withTrashed()->where('client_id', $client_id)->get();
            $d_fields=Donorfield::where('client_id', $client_id)->orderBy('field_order')->take(3)->get();
            $p_fields=Field::where('client_id', $client_id)->orderBy('field_order')->take(3)->get();
            //$details= $d->getAmountDetailsForTable($entities->first()->id);
            $details = [];
        } else {
            $program= Program::find($program_id);
            $details= $d->getAmountDetailsForTable($entity);
            $entities = Entity::withTrashed()->where('program_id', $program_id)->get();
            $d_fields=$donorFields = Donorfield::where('hysform_id', $program->donor_hysform_id)->orderBy('field_order')->take(3)->get();
            $p_fields=Field::where('hysform_id', $program->hysform_id)->orderBy('field_order')->take(3)->get();
        }

        $hashes = [];

        // foreach ($donorEntities as $k=> $de)
        // {
        // 	$d= Donor::withTrashed()->find($de->donor_id);
        // 	if(!isset($d))
        // 			$de->delete();
        // }

        $program = Program::find($program_id);
        $link_id = '';
        if (count($program) && empty($program->link_id)) {
            $link_id = $program->link_id;
        }
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

            $details_display = [];
            
        if (!empty($admin)) {
            if (isset($admin['program'])&&!empty($admin['program'])) {
                $programFields = json_decode($admin['program']);
            } else {
                $programFields= $p_fields;
            }
                
            if (isset($admin['donor'])&&!empty($admin['donor'])) {
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
            //return var_dump($details_display);
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

        return [
            'donorFields' => $donorFields,
            'programFields' => $programFields,
            'program' => $program,
            'program_id' => $program_id,
            'manage'    => $manage,
            'thumb'     => $thumb,
            'created_at' =>$created_at,
            'updated_at' => $updated_at,
            'email' =>$email,
            'username' => $username,
            'amount' => $amount,
            'frequency' => $frequency,
            'until' => $until,
            'last' => $last,
            'next' => $next,
            'method' => $method,
            'donor_created_at' =>$donor_created_at,
            'donor_updated_at' => $donor_updated_at,
            'sponsorship_created_at' => $sponsorship_created_at,
            'trashed'=>$trashed,
            'details_display'=>$details_display
        ];
    }

    public function getSponsorshipsAjax($client_id, $trashed, $url)
    {

        $key = 'sponsorshipsajax-'.$client_id.'-'.$trashed;
        $hashes = [];
        $pipeline = [];

        // Cache::forget($key);

        $vars = Cache::remember($key, 10080, function () use ($client_id, $trashed, $hashes, $pipeline, $key, $url) {
            $loading = Cache::get($key.'loading');
            if (empty($loading)) {
                Cache::put($key.'loading', '1', 5);
                $donors = '';
                $entities = '';
                $entities_list= [];

                foreach (Donor::withTrashed()->where('client_id', $client_id)->get() as $d) {
                    $donors[$d->id]=$d;
                }

                foreach (Entity::withTrashed()->where('client_id', $client_id)->get() as $e) {
                    $entities[$e->id]=$e;
                    $entities_list[]=$e->id;
                }


                $donorFields = Donorfield::where('client_id', $client_id)->orderBy('field_order')->get();
                $entityFields = Field::where('client_id', $client_id)->orderBy('field_order')->get();
            
                if ($trashed=='1') {
                    $donorEntities = DonorEntity::onlyTrashed()->where('client_id', $client_id)->get();
                } else {
                    $donorEntities = DonorEntity::where('client_id', $client_id)->get();
                }

                // return var_dump($donorEntities);
                $tmp_d= new Donor;

                $hashes = [];
                $pipeline = [];

                $uploads= [];

                if (!empty($entities_list)) {
                    $uploads= Upload::whereIn('entity_id', $entities_list)->where('profile', '1')->get()->lists('name', 'entity_id');
                }

                $upload = new Upload;

                $programs = Program::where('client_id', $client_id)->lists('name', 'id');

                foreach ($donorEntities as $k => $de) {
                    if (isset($donors[$de->donor_id])) {
                        $d= $donors[$de->donor_id];
                    } else {
                        $d=null;
                    }

                    $entityProfile = [];
                    $donorProfile = [];
                    $commitment = $de->commitment;

                    if (isset($entities[$de->entity_id])) {
                        $e = $entities[$de->entity_id];
                    } else {
                        $e = null;
                    }

                    $deleted = '';
                    if (!isset($d)) {
                        $deleted = ' (Donor Deleted from System)';
                    } elseif ($d->deleted_at!=null) {
                        $deleted = ' (Donor Archived on '.$d->deleted_at->toFormattedDateString().')';
                    }


                    if (count($e)) {
                        $hashes[$de->id] = ['entity_id' => $de->entity_id,
                            'manage' => '<small class="manage">
										<span class="label label-info"><a href="'. $url.'/admin/edit_entity/'.$de->entity_id . '"><span class="glyphicon glyphicon-pencil"></span> Edit Recipient</a></span>
										<span class="label label-success"><a href="'. $url. '/admin/edit_donor/'.$de->donor_id .'"><span class="glyphicon glyphicon-pencil"></span> Edit Donor</a></span>
										</small>',
                            'hys_entity_program' => $e->program_id,
                            'hys_program' => $de->program_id,
                            'hys_entity_program_name' => (isset($programs[$e->program_id]) ? '<a href="'.$url . '/admin/show_all_sponsorships/'.$e->program_id.'">' . $programs[$e->program_id] . '</a>': ''),
                            'hys_program_name' => (isset($programs[$de->program_id]) ? '<a href="'.$url.'/admin/show_all_sponsorships/'.$de->program_id.'">' .  $programs[$de->program_id] . '</a>': ''),
                            'entity_fields' => json_decode($e->json_fields, true),
                            'belongs_to_sub' => '',
                            'donor_id'  => (count($d) ? $d->id : ''),
                            'donor_fields' => (count($d) ? json_decode($d->json_fields, true) : []),
                            'created_at' => "<span class=\"hidden\"> " . strtotime($e->created_at) ." </span>" . Carbon::createFromTimeStamp(strtotime($e->created_at))->toFormattedDateString(),
                            'updated_at' => "<span class=\"hidden\"> ". strtotime($e->updated_at) ." </span>" . Carbon::createFromTimeStamp(strtotime($e->updated_at))->toFormattedDateString(),
                            'email' => (count($d)? $d->email: '').$deleted,
                            'username' => (count($d) ? $d->username: ''),
                            'amount' => (count($commitment) ? $commitment->amount: ''),
                            'frequency' => (count($commitment) ? $tmp_d->getFrequency($commitment->frequency): ''),
                            'until' => (count($commitment) ? ($commitment->until=='0000-00-00' ? "<span class=\"hidden\"> 0</span>" .'None' : "<span class=\"hidden\"> ".strtotime($commitment->until)." </span>" . Carbon::createFromTimeStamp(strtotime($commitment->until))->toFormattedDateString() ): ''),
                            'last' =>  (count($commitment) ? ($commitment->last=='0000-00-00' ? "<span class=\"hidden\"> 0</span>" . 'None' : "<span class=\"hidden\"> ".strtotime($commitment->last)." </span>" . Carbon::createFromTimeStamp(strtotime($commitment->last))->toFormattedDateString()  ): ''),
                            'next' => (count($commitment) ? $tmp_d->getNextPaymentDate($commitment): ''),
                            'method' => (count($commitment)? $tmp_d->getMethod($commitment->method): ''),
                            'donor_created_at' => (count($d) ? "<span class=\"hidden\"> ". strtotime($d->created_at) ." </span>" . Carbon::createFromTimeStamp(strtotime($d->created_at))->toFormattedDateString(): ''),
                            'donor_updated_at' => (count($d) ? "<span class=\"hidden\"> ". strtotime($d->updated_at) ." </span>" . Carbon::createFromTimeStamp(strtotime($d->updated_at))->toFormattedDateString(): ''),
                            'sponsorship_created_at' => "<span class=\"hidden\"> ".strtotime($de->created_at)." </span>" . Carbon::createFromTimeStamp(strtotime($de->created_at))->toFormattedDateString()];
                        

                        foreach ($entityFields as $f) {
                            if (isset($hashes[$de->id]['entity_fields'][$f->field_key])) {
                                $entityProfile[$f->field_key]= $hashes[$de->id]['entity_fields'][$f->field_key];
                            } else {
                                $entityProfile[$f->field_key]= '';
                            }
                        }

                        $pipeline[$de->id]['hys_profile'] = $e->formatProfile($entityProfile, $entityFields);

                        if (isset($uploads[$de->entity_id])) {
                            $picarray = explode('.', $uploads[$de->entity_id]);
                            $thumb = $picarray[0].'_t_.'.$picarray[1];
                            $pipeline[$de->id]['thumb'] = '<img src=\'https://s3-us-west-1.amazonaws.com/hys/' . $thumb . '\' width="30px" />';
                        } else {
                            $pipeline[$de->id]['thumb']='';
                        }


                        foreach ($donorFields as $f) {
                            if (!empty($hashes[$de->id]['belongs_to_sub'])) {
                                $donorProfiles[$k][$f->field_key]= $hashes[$de->id]['belongs_to_sub'];
                            } else if (isset($hashes[$de->id]['donor_fields'][$f->field_key])) {
                                    $donorProfile[$f->field_key]= $hashes[$de->id]['donor_fields'][$f->field_key];
                            } else {
                                $donorProfile[$f->field_key]= '';
                            }
                        }
                        
                        $pipeline[$de->id]['hys_donor_profile'] = $e->formatProfile($donorProfile, $donorFields);
                    }
                }

                Cache::forget($key.'loading');

                return ['hashes'=>$hashes,'pipeline'=>$pipeline];
            } else {
                return ['hashes'=>[],'pipeline'=>[]];
            }
        });

        return $vars;
    }


    public function reloadSponsorshipsToCache($des, $url = false)
    {
        if (!count($des)) {
            return false;
        }

        if ($url===false) {
            $url = URL::to('');
        }

        //change to multiple donors if it's just one.
        if (isset($des->id)) {
            $des = DonorEntity::withTrashed()->where('id', $des->id)->get();
        }

        if ($des->count()==0) {
            return false;
        }

        $client_id = $des->first()->client_id;

        Cache::tags('sponsorships-'.$client_id)->flush();

        //Clear cache for donors counts
        $d = new Donor;
        $e = new Entity;
        // $d->clearDonorCache($donors->first()->hysform_id,array('donors'),null);

        //first figure out where this DonorEntity belongs in the cache tables
        foreach ($des as $de) {
            if ($de->deleted_at==null) {
                $tables[$de->id]['1']=false;
                $tables[$de->id]['']=true;
            } else {
                $tables[$de->id]['1']=true;
                $tables[$de->id]['']= false;
            }
        }

        $caches=[];
        $key = 'sponsorshipsajax-'.$client_id.'-';
        
        foreach ($tables[$des->first()->id] as $k => $t) {
            $caches[$k]= Cache::get($key.$k);
        }
        $old_caches = $caches;

        $d = new Donor;
        $donor_table = [];
        $entity_table = [];

        //crawl through the cached tables and remove this donor from them all (if it exists, that is.)
        foreach ($caches as $k => $cache) {
            if ($cache!=null) {
                foreach ($des as $de) {
                    if (isset($cache['hashes'][$de->id])) {
                        //Empty out the current contents of the cache for this entity.
                        unset($caches[$k]['pipeline'][$de->id]);
                        unset($caches[$k]['hashes'][$de->id]);
                    }
                    $donor_table[]=$de->donor_id;
                    $entity_table[]=$de->entity_id;
                }
            }
        }

        if (!empty($donor_table)) {
            $donors = Donor::withTrashed()->whereIn('id', $donor_table)->get();
        }
        if (!empty($entity_table)) {
            $entities = Entity::withTrashed()->whereIn('id', $entity_table)->get();
        }

        $programs = Program::where('client_id', $client_id)->get();

        $donorFields = Donorfield::where('client_id', $client_id)->orderBy('field_order')->get();
        $entityFields = Field::where('client_id', $client_id)->orderBy('field_order')->get();

        $tmp_d = new Donor;

        foreach ($des as $de) {
            if ($de->deleted_at==null) {
                if (isset($donors)) {
                    $d= $donors->find($de->donor_id);
                }

                $entityProfile = [];
                $donorProfile = [];
                $commitment = $de->commitment;

                if ($de->program_id!=0) {
                    $program = $programs->find($de->program_id);
                } else {
                    $program = null;
                }

                if (isset($entities)) {
                    $e = $entities->find($de->entity_id);
                }

                $deleted = '';
                if (!isset($d)) {
                    $deleted = ' (Donor Deleted from System)';
                } elseif ($d->deleted_at!=null) {
                    $deleted = ' (Donor Archived on '.$d->deleted_at->toFormattedDateString().')';
                }


                if (count($e)) {
                    $e_program = $programs->find($e->program_id);
                    $de_program = $programs->find($de->program_id);

                    $new_hash[$de->id] = ['entity_id' => $de->entity_id,
                            'manage' => '<small class="manage">
										<span class="label label-info"><a href="'. $url.'/admin/edit_entity/'.$de->entity_id . '"><span class="glyphicon glyphicon-pencil"></span> Edit Recipient</a></span>
										<span class="label label-success"><a href="'. $url. '/admin/edit_donor/'.$de->donor_id .'"><span class="glyphicon glyphicon-pencil"></span> Edit Donor</a></span>
										</small>',
                            'hys_entity_program' => $e->program_id,
                            'hys_program' => $de->program_id,
                            'hys_entity_program_name' => (count($e_program) ? '<a href="'.$url . '/admin/show_all_sponsorships/'.$e->program_id.'">' . $e_program->name . '</a>': ''),
                            'hys_program_name' => (count($de_program) ? '<a href="'.$url.'/admin/show_all_sponsorships/'.$de->program_id.'">' .  $de_program->name . '</a>': ''),
                            'entity_fields' => json_decode($e->json_fields, true),
                            'belongs_to_sub' => '',
                            'donor_id'  => (count($d) ? $d->id : ''),
                            'donor_fields' => (count($d) ? json_decode($d->json_fields, true) : []),
                            'created_at' => "<span class=\"hidden\"> " . strtotime($e->created_at) ." </span>" . Carbon::createFromTimeStamp(strtotime($e->created_at))->toFormattedDateString(),
                            'updated_at' => "<span class=\"hidden\"> ". strtotime($e->updated_at) ." </span>" . Carbon::createFromTimeStamp(strtotime($e->updated_at))->toFormattedDateString(),
                            'email' => (count($d)? $d->email: '').$deleted,
                            'username' => (count($d) ? $d->username: ''),
                            'amount' => (count($commitment) ? $commitment->amount: ''),
                            'frequency' => (count($commitment) ? $tmp_d->getFrequency($commitment->frequency): ''),
                            'until' => (count($commitment) ? ($commitment->until=='0000-00-00' ? "<span class=\"hidden\"> 0</span>" .'None' : "<span class=\"hidden\"> ".strtotime($commitment->until)." </span>" . Carbon::createFromTimeStamp(strtotime($commitment->until))->toFormattedDateString() ): ''),
                            'last' =>  (count($commitment) ? ($commitment->last=='0000-00-00' ? "<span class=\"hidden\"> 0</span".' None' : "<span class=\"hidden\"> ".strtotime($commitment->last)." </span>" . Carbon::createFromTimeStamp(strtotime($commitment->last))->toFormattedDateString()  ): ''),
                            'next' => (count($commitment) ? $tmp_d->getNextPaymentDate($commitment): ''),
                            'method' => (count($commitment)? $tmp_d->getMethod($commitment->method): ''),
                            'donor_created_at' => (count($d) ? "<span class=\"hidden\"> ". strtotime($d->created_at) ." </span>" . Carbon::createFromTimeStamp(strtotime($d->created_at))->toFormattedDateString(): ''),
                            'donor_updated_at' => (count($d) ? "<span class=\"hidden\"> ". strtotime($d->updated_at) ." </span>" . Carbon::createFromTimeStamp(strtotime($d->updated_at))->toFormattedDateString(): ''),
                            'sponsorship_created_at' => "<span class=\"hidden\"> ".strtotime($de->created_at)." </span>" . Carbon::createFromTimeStamp(strtotime($de->created_at))->toFormattedDateString()];
                    

                    foreach ($entityFields as $f) {
                        if (isset($new_hash[$de->id]['entity_fields'][$f->field_key])) {
                            $entityProfile[$f->field_key]= $new_hash[$de->id]['entity_fields'][$f->field_key];
                        } else {
                            $entityProfile[$f->field_key]= '';
                        }
                    }

                    $new_pipeline[$de->id]['hys_profile'] = $e->formatProfile($entityProfile, $entityFields);

                    $pic = Upload::whereEntityId($de->entity_id)->where('profile', 1)->first();
                    if (count($pic)) {
                        $picarray = explode('.', $pic->name);
                        $thumb = $picarray[0].'_t_.'.$picarray[1];
                        $new_pipeline[$de->id]['thumb'] = '<img src=\'https://s3-us-west-1.amazonaws.com/hys/' . $thumb . '\' width="30px" />';
                    } else {
                        $new_pipeline[$de->id]['thumb']='';
                    }


                    foreach ($donorFields as $f) {
                        if (!empty($new_hash[$de->id]['belongs_to_sub'])) {
                            $donorProfiles[$k][$f->field_key]= $new_hash[$de->id]['belongs_to_sub'];
                        } else if (isset($new_hash[$de->id]['donor_fields'][$f->field_key])) {
                                $donorProfile[$f->field_key]= $new_hash[$de->id]['donor_fields'][$f->field_key];
                        } else {
                            $donorProfile[$f->field_key]= '';
                        }
                    }
                    
                    $new_pipeline[$de->id]['hys_donor_profile'] = $e->formatProfile($donorProfile, $donorFields);
                }
            }
        }
        
        //run through the caches and put them back into redis
        foreach ($caches as $k => $cache) {
            if ($cache!=null) {
                foreach ($des as $de) {
                    if ($tables[$de->id][$k]===true&&isset($new_hash[$de->id])) {
                        //Set the new hash and pipeline
                        $cache['hashes'][$de->id] = $new_hash[$de->id];
                        $cache['pipeline'][$de->id] = $new_pipeline[$de->id];
                    }
                }

                //Slap the new data back on the cache!
                Cache::put('sponsorshipsajax-'.$client_id.'-'.$k, $cache, 10080);

                $new_cache = $cache;
            }
        }
        
            // return(array('old'=>$old_caches['']['pipeline'],'new'=>$new_cache['pipeline']));
            // return array('hash'=>$new_hash,'new_pipeline'=>$new_pipeline);
    }


    // formats special fields in a profile for displaying correctly
    public function formatProfile($profile, $fields, $hidden = false)
    {
        foreach ($fields as $field) {
            // format links
            if (isset($field->field_type)) {
                if ($field->field_type == 'hysLink') {
                    if (!empty($profile[$field->field_key])) {
                        $link = explode('|', $profile[$field->field_key]);
                        if (count($link)>1) {
                            $profile[$field->field_key] = '<a href="'.$link[1].'">'.$link[0].'</a>';
                        }
                    }
                }
                
                // format static text
                if ($field->field_type == 'hysStatic') {
                    $profile[$field->field_key] = $field->field_data;
                }
                
                // format dates
                if ($field->field_type == 'hysDate') {
                    if (!empty($profile[$field->field_key])) {
                        $profile[$field->field_key] = Carbon::createFromTimeStamp(strtotime($profile[$field->field_key]))->toFormattedDateString();
                    }
                }

                // format Age
                if ($field->field_type == 'hysAge') {
                    if (!empty($profile[$field->field_key])) {
                        $profile[$field->field_key] = Carbon::createFromTimeStamp(strtotime($profile[$field->field_key]))->age .' ('.
                         Carbon::createFromTimeStamp(strtotime($profile[$field->field_key]))->toFormattedDateString().')';
                    }
                }
                
                // format check boxes (separate with commas)
                if ($field->field_type == 'hysCheckbox') {
                    if (!empty($profile[$field->field_key])) {
                        $data = json_decode($profile[$field->field_key], true);
                        if (is_array($data)) {
                            $profile[$field->field_key] = implode(',', $data);
                        }
                    }
                }
    
                // format Table
                if ($field->field_type == 'hysTable') {
                    if (!empty($profile[$field->field_key])) {
                        $items = explode(',', $field->field_data);
                        $out = '<div class="form-group">';
                        
                        if ($hidden == true) {
                            $out .= '<table class="table table-condensed" style="display: none"><thead><tr>';
                        }
                        
                        if ($hidden == false) {
                            $out .= '<table class="table table-condensed"><thead><tr>';
                        }
                        
                        foreach ($items as $item) {
                            $out .= '<th>'.$item.'</th>';
                        }
                        
                        $out .= '</tr></thead><tbody>';
                        $count = count($items);
                        
                            $table_data = json_decode($profile[$field->field_key], true);
                            $i = 0;
    
                        if (is_array($table_data)) {
                            foreach ($table_data as $td) {
                                $i++;
                                if ($i == 1) {
                                    $out .= '<tr class="'.$field->field_key.'">';
                                }
                                    
                                $out .= '<td>'.$td.'</td>';
                                    
                                if ($i == $count) {
                                    $i = 0;
                                    $out .= '</tr>';
                                }
                            }
                        }
                        
                        $out .= '</tbody></table></div>';
    
                        $profile[$field->field_key] = $out ;
                    }
                }
            } // end if (isset($field->field_type)) {
        } // foreach ($fields as $field) {
        return $profile;
    }

    // formats special fields in a profile for displaying correctly
    public function formatProfileAjax($profile, $fields, $hidden = false)
    {
        foreach ($fields as $field) {
            // format links
            if (isset($field->field_type)) {
                if ($field->field_type == 'hysLink') {
                    if (!empty($profile[$field->field_key])) {
                        $link = explode('|', $profile[$field->field_key]);
                        $profile[] = '<a href="'.$link[1].'">'.$link[0].'</a>';
                        unset($profile[$field->field_key]);
                    }
                }
                
                // format static text
                if ($field->field_type == 'hysStatic') {
                    $profile[] = $field->field_data;
                    unset($profile[$field->field_key]);
                }


                // format hysTextarea; remove all html tags for table-display this keeps csv from writing a blank file.
                if ($field->field_type == 'hysTextarea') {
                    $profile[] = strip_tags($profile[$field->field_key]);
                    unset($profile[$field->field_key]);
                }
                
                // format dates
                if ($field->field_type == 'hysDate') {
                    if (!empty($profile[$field->field_key])) {
                        $profile[] = Carbon::createFromTimeStamp(strtotime($profile[$field->field_key]))->toFormattedDateString();
                        unset($profile[$field->field_key]);
                    }
                }

                // format Age
                if ($field->field_type == 'hysAge') {
                    if (!empty($profile[$field->field_key])) {
                        $profile[] = Carbon::createFromTimeStamp(strtotime($profile[$field->field_key]))->age .' ('.
                         Carbon::createFromTimeStamp(strtotime($profile[$field->field_key]))->toFormattedDateString().')';
                        unset($profile[$field->field_key]);
                    }
                }
                
                // format check boxes (separate with commas)
                if ($field->field_type == 'hysCheckbox') {
                    $options = explode(',', $field->field_data);
                    
                    if (!empty($profile[$field->field_key])) {
                        $data = json_decode($profile[$field->field_key], true);
                        if (is_array($data)) {
                            $values = '';
                            foreach ($options as $k => $v) {
                                if (isset($data[$k])) {
                                    $values .= $v . ',';
                                }
                            }
                            $values = rtrim($values, ",");
                            $profile[] = $values;
                            unset($profile[$field->field_key]);
                        }
                    }
                }
    
                // format Table
                if ($field->field_type == 'hysTable') {
                    if (!empty($profile[$field->field_key])) {
                        $items = explode(',', $field->field_data);
                        $out = '<div class="form-group">';
                        
                        if ($hidden == true) {
                            $out .= '<table class="table table-condensed" style="display: none"><thead><tr>';
                        }
                        
                        if ($hidden == false) {
                            $out .= '<table class="table table-condensed"><thead><tr>';
                        }
                        
                        foreach ($items as $item) {
                            $out .= '<th>'.$item.'</th>';
                        }
                        
                        $out .= '</tr></thead><tbody>';
                        $count = count($items);
                        
                            $table_data = json_decode($profile[$field->field_key], true);
                            $i = 0;
    
                        if (is_array($table_data)) {
                            foreach ($table_data as $td) {
                                $i++;
                                if ($i == 1) {
                                    $out .= '<tr class="'.$field->field_key.'">';
                                }
                                    
                                $out .= '<td>'.$td.'</td>';
                                    
                                if ($i == $count) {
                                    $i = 0;
                                    $out .= '</tr>';
                                }
                            }
                        }
                        
                        $out .= '</tbody></table></div>';
    
                        $profile[] = $out ;
                        unset($profile[$field->field_key]);
                    }
                }
            } // end if (isset($field->field_type)) {
            if (isset($profile[$field->field_key])) {
                $profile[]=$profile[$field->field_key];
                unset($profile[$field->field_key]);
            }
        } // foreach ($fields as $field) {
        return $profile;
    }
    
    // gets sponsors for an entity and returns array with id and name
    public function getSponsors($entity_id)
    {
        $redis = RedisL4::connection();
        $program = new Program;
        $donor = new Donor;

        // $type = $program->getProgramTypeFromEntity($entity_id);
        $k=0;

        foreach (DonorEntity::where('entity_id', $entity_id)->get() as $k => $sponsors) {
            $all[$k] = $this->getDonorName($sponsors->donor_id);
            $c=Commitment::withTrashed()->where('donor_entity_id', $sponsors->id)->first();

            if (count($c)) {
                $all[$k]['amount']=$donor->getFrequencyTotal($c->amount, $c->frequency);
                $all[$k]['frequency']=$donor->getFrequency($c->frequency);
            } else {
                $all[$k]['amount']='';
                $all[$k]['frequency']='';
            }
        }

        // if($type=='funding')
        // {
        // 	foreach(Donation::where('type','1')->where('designation',$entity_id)->get() as $donation)
        // 	{
        // 		$k++;
        // 		$all['funding-'.$k]=$this->getDonorName($donation->donor_id);
        // 		$all['funding-'.$k]['amount']=$donation->amount;
        // 		$all['funding-'.$k]['frequency']='One-Time';
        // 	}
        // }
        if (empty($all)) {
            $all = [];
        }
        return $all;
    }

    // gets entities for a donor and returns array with id and name
    public function getEntitiesFromDonor($donor_id)
    {
        $redis = RedisL4::connection();
        $program = new Program;
        $donor = new Donor;

        // $type = $program->getProgramTypeFromEntity($entity_id);
        $k=0;

        foreach (DonorEntity::where('donor_id', $donor_id)->get() as $k => $sponsors) {
            $all[$k] = $donor->getEntityName($sponsors->entity_id);
            $c=Commitment::withTrashed()->where('donor_entity_id', $sponsors->id)->first();

            if (count($c)) {
                $all[$k]['amount']=$donor->getFrequencyTotal($c->amount, $c->frequency);
                $all[$k]['frequency']=$donor->getFrequency($c->frequency);
            } else {
                $all[$k]['amount']='';
                $all[$k]['frequency']='';
            }
        }

        // if($type=='funding')
        // {
        // 	foreach(Donation::where('type','1')->where('designation',$entity_id)->get() as $donation)
        // 	{
        // 		$k++;
        // 		$all['funding-'.$k]=$this->getDonorName($donation->donor_id);
        // 		$all['funding-'.$k]['amount']=$donation->amount;
        // 		$all['funding-'.$k]['frequency']='One-Time';
        // 	}
        // }
        if (empty($all)) {
            $all = [];
        }
        return $all;
    }

    public function getDonations($entity_id)
    {

        $program = new Program;
        $donor = new Donor;

        foreach (Donation::where('type', '1')->where('designation', $entity_id)->get() as $k => $donation) {
            $all[$k]=$this->getDonorName($donation->donor_id);

            $all[$k]['hysform_id']='';
            $donor=Donor::withTrashed()->find($donation->donor_id);
            
            if (count($donor)) {
                $all[$k]['hysform_id']=$donor->hysform_id;
            }

            $all[$k]['amount']=$donation->amount;

            if ($all[$k]['name']=='No Name Found') {
                unset($all[$k]);
            }
        }

        if (empty($all)) {
            $all = [];
        }
        return $all;
    }
    
    /**
    * retrieves the name of a donor
    * returns array with id and name
    * @param $donor_id = the id of the donor
    * @param $redis = RedisL4::connection() - pass in the connection
    **/
    public function getDonorName($donor_id)
    {
        $donor = Donor::withTrashed()->find($donor_id);
        if (count($donor)) {
            $fields = Donorfield::where('hysform_id', $donor->hysform_id)->where('is_title', 1)->orderBy('field_order', 'asc')->get();
            foreach ($fields as $field) {
                $anames[] = $field->field_key;
            }
            $donorName = ['id' => '', 'hysform_id' => $donor->hysform_id, 'name' => $donor->username, 'email' => $donor->email];
            if (!empty($anames)) {
                $json_fields = json_decode($donor->json_fields, true);
                foreach ($anames as $aname) {
                    if (isset($json_fields[$aname])) {
                        $names[] = $json_fields[$aname];
                    }
                }
            }
            
            if (isset($names)) {
                $n = '';
                foreach ($names as $name) {
                    $n .= "$name ";
                }
                $n = substr($n, 0, -1); // Removes the last space
                
                if ($n != ' ') {
                    $donorName = ['id' => $donor->id, 'hysform_id' => $donor->hysform_id, 'name' => $n, 'email' => $donor->email];
                }
            } else {
                $donorName = ['id' => $donor->id, 'hysform_id' => $donor->hysform_id, 'name' => 'No Name Found', 'email' => $donor->email];
            }
        } else {
            $donorName = ['id' => $donor_id, 'hysform_id' => '', 'name' => 'No Name Found', 'email' => ''];
        }
        
        return $donorName;
    }
    
    /**
    * Retrieves all the program type (contribution or number)
    * from settings and the relevant settings requried for
    * the program type.
    **/
    public function getProgramTypeData($setting_id, $entity_id = null)
    {
        $settings = Setting::find($setting_id);
        $program_settings = json_decode($settings->program_settings);
        
        if ($program_settings->program_type == 'contribution') {
            $sp_nums = explode(',', $program_settings->sp_num);
            foreach ($sp_nums as $num) {
                $new_num[] = ['symbol' => $program_settings->currency_symbol, 'amount' => $num];
            }
            $programData = ['type' => 'contribution', 'sp_num' => $new_num,'currency_symbol'=>$program_settings->currency_symbol];
        }
        
        if ($program_settings->program_type == 'number') {
            $number_sponsors = explode(',', $program_settings->number_spon);
            $sp_amounts = explode(',', $program_settings->sponsorship_amount);
            foreach ($sp_amounts as $sp_amount) {
                $amounts[] = ['symbol' => $program_settings->currency_symbol, 'amount' => $sp_amount];
            }
            $programData = ['type' => 'number', 'number_sponsors' => $number_sponsors, 'sp_amount' => $amounts, 'currency_symbol'=>$program_settings->currency_symbol];
        }
        if ($program_settings->program_type == 'funding') {
            $sp_nums = explode(',', $program_settings->sp_num);
            $funded_amounts = explode(',', $program_settings->number_spon);
            if ($entity_id!=null) {
                $entity=Entity::find($entity_id);
                if (!empty($entity->sp_num)&&!in_array($entity->sp_num, $funded_amounts)) {
                    $funded_amounts[]=$entity->sp_num;
                }
            }

            foreach ($sp_nums as $num) {
                $new_num[] = ['symbol' => $program_settings->currency_symbol, 'amount' => $num];
            }
            $programData = ['type' => 'funding','currency_symbol'=>$program_settings->currency_symbol, 'sp_num' => $new_num,'funded_amounts' => $funded_amounts];
        }
        if ($program_settings->program_type == 'one_time') {
            $sp_amounts = explode(',', $program_settings->sponsorship_amount);
            foreach ($sp_amounts as $sp_amount) {
                $amounts[] = ['symbol' => $program_settings->currency_symbol, 'amount' => $sp_amount];
            }
            $programData = ['type' => 'one_time',  'sp_amount' => $amounts, 'currency_symbol'=>$program_settings->currency_symbol];
        }
        return $programData;
    }

    public function getProgramCounts($program_id)
    {

        $counts= [];

        $counts['all']=Entity::allEntitiesByProgram($program_id)->get()->count();

        $counts['available']=  Entity::allEntitiesByProgram($program_id)->where('status', '0')->get()->count();

        $counts['sponsored'] = Entity::allEntitiesByProgram($program_id)->where('status', '1')->get()->count();

        $counts['unsponsored'] = Entity::allEntitiesByProgram($program_id)->has('donors', '=', '0')->get()->count();

        $counts['trashed'] = Entity::allEntitiesByProgram($program_id)->onlyTrashed()->get()->count();

        $counts['sponsorships'] =0;
        $counts['archived_sponsorships']= 0;

        $program =Program::find($program_id);

        $entities_array=[];
        foreach (Entity::withTrashed()->where('program_id', $program_id)->get() as $entity) {
            $entities_array[]=$entity->id;
        }

        if (!empty($entities_array)) {
            $counts['sponsorships'] = DonorEntity::where('client_id', $program->client_id)->whereIn('entity_id', $entities_array)->count();
            $counts['archived_sponsorships'] = DonorEntity::onlyTrashed()->where('client_id', $program->client_id)->whereIn('entity_id', $entities_array)->count();
        }

        
        return $counts;
    }

    public function getTotal($session_id)
    {

        $redis =RedisL4::connection();

        $frequencies= $redis->hgetall($session_id.':saved_entity_frequency');

        $entities = $redis->hgetall($session_id.':saved_entity_id');

        $saved_designations=$redis->hgetall($session_id.':saved_designations');

        $total=0;

        foreach ($entities as $id => $entity) {
            //This modifies the total depending on the frequency chosen by the user
            if ($frequencies[$id]==1) {
                $total += $entity;
            }
            if ($frequencies[$id]==2) {
                $total += $entity * 3;
            }
            if ($frequencies[$id]==3) {
                $total += $entity * 6;
            }
            if ($frequencies[$id]==4) {
                $total += $entity * 12;
            }
            if ($frequencies[$id]==5) {
                $total += $entity;
            }
        }
        foreach ($saved_designations as $designation) {
            $total +=$designation;
        }

        if ($total==0) {
            return null;
        }
        return $total;
    }
}
