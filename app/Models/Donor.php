<?php

namespace App\Models;

use Carbon\Carbon;
use Cartalyst\Sentry\Facades\Laravel\Sentry;
use Hugofirth\Mailchimp\Facades\MailchimpWrapper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class Donor extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public function scopeAllDonorsByDonorprogram($query, $id)
    {
        return $query->whereDonorprogramId($id);
    }
   
    public function uploads()
    {
        return $this->hasMany('App\Models\Upload');
    }

    public function rand_string($length)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        return substr(str_shuffle($chars), 0, $length);
    }

    /**
    * retrieves the name of an entity
    * returns array with id and name
    * @param $entity_id = the id of the entity
    * @param $redis = RedisL4::connection() - pass in the connection
    **/
    public function getEntityName($entity_id, $public = false)
    {
        $entity = Entity::withTrashed()->where('id', $entity_id)->first();
        if ($entity) {
            $entityName = ['id' => $entity_id, 'name' => ' ', 'program_id' => $entity->program_id];
            $program = Program::find($entity->program_id);
            $anames=[];
            if (count($program)) {
                $fields=[];
                if ($public==true) {
                    $fields = Field::where('hysform_id', $program->hysform_id)->where('is_title', 1)->where('permissions', '=', 'public')->orderBy('field_order', 'asc')->get();
                }
                if ($public=='donor') {
                    $fields = Field::where('hysform_id', $program->hysform_id)->where('is_title', 1)->where('permissions', '!=', 'admin')->orderBy('field_order', 'asc')->get();
                }
                if ($public==false) {
                    $fields = Field::where('hysform_id', $program->hysform_id)->where('is_title', 1)->orderBy('field_order', 'asc')->get();
                }
                
                foreach ($fields as $field) {
                    $anames[] = $field->field_key;
                }
                $names = [];
                if (!empty($anames)) {
                    $json_fields = json_decode($entity->json_fields, true);
                    foreach ($anames as $aname) {
                        if (isset($json_fields[$aname])) {
                            $names[] = $json_fields[$aname];
                        }
                    }
                } else {
                    return $entityName = ['id' => $entity_id, 'name' => ' ', 'program_id' => ''];
                }
                
                $n = '';
                foreach ($names as $name) {
                    $n .= "$name ";
                }
                $n = substr($n, 0, -1); // Removes the last space
                
                if ($n != ' ') {
                    $entityName['name'] = $n;
                }
            }
            return $entityName;
        } else {
            return $entityName = ['id' => $entity_id, 'name' => ' ', 'program_id' => ''];
        }
    }
    
    /**
    * gets sponsorships for a donor and returns array with id, name, sponsorship amount
    * retrieve deleted with $trashed = true
    **/
    public function getSponsorships($donor_id, $trashed = false)
    {
        $all = [];
        $client_id= Session::get('client_id');
        $redis = RedisL4::connection();
        $donorEntities= DonorEntity::where('donor_id', $donor_id)->get();
        $entities= Entity::withTrashed()->where('client_id', $client_id)->get();

        foreach (Commitment::where('donor_id', $donor_id)->where('type', '1')->where('donor_entity_id', '!=', '0')->get() as $commitment) {
            if (!count($donorEntities->find($commitment->donor_entity_id))) {
                //if the commitment has no Donor_Entity, make a new one.
                $de= new DonorEntity;
                $de->donor_id=$commitment->donor_id;
                $de->entity_id=$commitment->designation;
                $de->client_id=$commitment->client_id;

                $entity=Entity::find($commitment->designation);
                if (count($entity)) {
                    $de->program_id=$entity->program_id;
                } else {
                    $de->program_id='0';
                }
                $de->save();

                //Set the New donor_entity to the commitment.
                $commitment->donor_entity_id=$de->id;
                $commitment->save();

                $e = new Entity;
                $donor_name=$e->getDonorName($donor_id);

                Session::flash('message', 'Note: Problems with '.$donor_name['name'].'\'s sponsorships were resolved <br>'.
                    'you will want to <strong><a href="'.URL::to('admin/donations_by_donor', [$donor_id]).'">check '.$donor_name['name'].'\'s commitments</strong></a> to make sure the program assignment is correct for their sponsorships.');
                Session::flash('alert', 'info');
            }
        }

        if ($trashed == true) {
            foreach (DonorEntity::onlyTrashed()->where('donor_id', $donor_id)->get() as $sponsorship) {
                $info = $this->getEntityName($sponsorship->entity_id);

                $info['created'] = $sponsorship->created_at;
                $info['deleted'] = $sponsorship->deleted_at;
                $info['donor_entity_id'] = $sponsorship->id;

                
                $commitment = Commitment::withTrashed()->where('donor_entity_id', $sponsorship->id)->first();
                
                $info['commit'] = '';
                if (isset($commitment->amount)) {
                    $info['commit'] = number_format($commitment->amount, 2, '.', '');
                }
                $info['method']='';
                if (isset($commitment->method)) {
                    $info['method']=$this->getMethod($commitment->method);
                }
                $info['deleted']='';
                $entity= $entities->find($sponsorship->entity_id);
                
                if (count($entity)&&$entity->deleted_at!=null) {
                    $info['deleted']='1';
                }

                $all[] = $info;
            }
        } else {
            $donor = new Donor;
            
            foreach (DonorEntity::where('donor_id', $donor_id)->get() as $sponsorship) {
                $info = $this->getEntityName($sponsorship->entity_id);

                $program = Program::find($info['program_id']);
                $info['allow_email']='';
                if (count($program)) {
                    $settings = Setting::find($program->setting_id);
                    $program_settings = json_decode($settings->program_settings, true);

                    //This makes the display of donor correspondence and file uploads dependent on each recipient's program,
                    //not the program the donor logged in with.
                    $info['allow_email']=$settings->allow_email;
                } else {
                    $program_settings = [];
                }
                $info['created'] = $sponsorship->created_at;
                $info['donor_entity_id'] = $sponsorship->id;
                if (!empty($program_settings['currency_symbol'])) {
                    $info['currency_symbol'] = $program_settings['currency_symbol'];
                } else {
                    $info['currency_symbol'] = '$';
                }

                
                $commitment = Commitment::where('donor_entity_id', $sponsorship->id)->first();
                $info['commit'] = '';
                if (isset($commitment->amount)) {
                    $info['commit'] = number_format($commitment->amount, 2, '.', '');
                }
                $info['method']='';
                if (isset($commitment->method)) {
                    $info['method']=$this->getMethod($commitment->method);
                }
                            
                $info['until']='';
                if (isset($commitment->until)) {
                    $info['until'] = $commitment->until;
                }
                
                $info['commitment_id'] = '';
                if (isset($commitment->id)) {
                    $info['commitment_id'] = $commitment->id;
                }



                $info['frequency']='';
                $info['next']='';
                $info['frequency_total']='';
                if (isset($commitment->frequency)) {
                    $info['frequency']=$this->getFrequency($commitment->frequency);
                    $info['next']=$this->whatHappensNext($commitment);
                    $info['frequency_total']=$this->getFrequencyTotal($commitment->amount, $commitment->frequency);
                }

                if (!empty($program_settings)&&$program_settings['program_type']=='funding'&&isset($commitment)&&$commitment->type=='1') {
                    $info['funding_info']=$program_settings['currency_symbol'].$donor->getFundingInfo(Entity::find($commitment->designation));
                    $info['funding_percent']=$donor->getFundingPercent(Entity::find($commitment->designation));
                }

                $info['deleted']='';
                $entity= $entities->find($sponsorship->entity_id);
                
                if (count($entity)&&$entity->deleted_at!=null) {
                    $info['deleted']='1';
                }

                $info['status']='1';
                if (count($entity)) {
                    $info['status']=$entity->status;
                }

                $info['entity_id']='';
                if (count($entity)) {
                    $info['entity_id']=$entity->id;
                }

                    
                
                if (isset($commitment->id)) {
                    $all[$commitment->id] = $info;
                } else {
                    $all[]=$info;
                }
            }
        }


        return $all;
    }


    public function getFundingEntities($client_id)
    {


        $s_array=[];
        //find all the settings that are set to 'funding'
        foreach (Setting::where('client_id', $client_id)->get() as $setting) {
            $s = json_decode($setting->program_settings);

            if ($s->program_type=='funding') {
                $s_array[]=$setting->id;
            }
        }

        if (empty($s_array)) {
            return false;
        }

        // get all the programs that are setting
        $p_list= Program::where('client_id', $client_id)->whereIn('setting_id', $s_array)->get()->lists('id');

        $entities= [];

        if (empty($p_list)) {
            return false;
        }

        foreach (Entity::where('client_id', $client_id)->whereIn('program_id', $p_list)->get() as $entity) {
            $info = $this->getEntityName($entity->id);

            if (!empty($info)) {
                $entities[]=$info;
            }
        }

        return $entities;
    }


    public function clearDonorCache($hysform_id, $reload = null, $trashed_options = null)
    {

        if ($trashed_options==null) {
            $trashed_options = ['','1'];
        }

        if ($reload==null) {
            $reload= ['donors','data'];
        }

        if (in_array('donors', $reload)) {
            Cache::forget('showalldonors-'.$hysform_id);
        }

        if (in_array('data', $reload)) {
            foreach ($trashed_options as $trashed) {
                Cache::forget('showalldonorsajax-'.$hysform_id.'-'.$trashed);
            }
        }
    }

    public function reloadDonorCaches($hysform_id, $reload, $trashed_options)
    {

        //queue reload of Donors table
        $hysform=Hysform::find($hysform_id);
        $cache_data['hysform_id']=$hysform_id;
        $cache_data['client_id'] = $hysform->client_id;
        $cache_data['url']= URL::to('');
        
        if ($reload==null) {
            $cache_data['reload']= ['donors','data'];
        }

        if ($trashed_options==null) {
            $cache_data['trashed_options'] =['','1'];
        }

        Queue::push('reloadDonorCache', $cache_data);
    }
    

    public function removeDonorsFromTrashedCache($donors)
    {

        if (!count($donors)) {
            return false;
        }

        $hysform_id = $donors->first()->hysform_id;

        //Update the cache, without renewing it.
        $cache= Cache::get('showalldonors-'.$hysform_id);

        $count = $donors->count();

        //modify the cache count to reflect deleting the donor from the trashed cache table
        if ($cache!=null) {
            $cache['counts']['trashed']-=$count;
            Cache::put('showalldonors-'.$hysform_id, $cache, 30);
        }

        $trashed_cache= Cache::get('showalldonorsajax-'.$hysform_id.'-1');
        $trash_mod = false;
        
        foreach ($donors as $donor) {
            if ($trashed_cache!=null&&isset($trashed_cache['hashes'][$donor->id])) {
                $trash_mod = true;
                //Remove the donor from the cache
                unset($trashed_cache['pipeline'][$donor->id]);
                unset($trashed_cache['hashes'][$donor->id]);
            }
        }

        if ($trash_mod) {
            //Set the cache with the donor removed
            Cache::put('showalldonorsajax-'.$hysform_id.'-1', $trashed_cache, 1440);
        }

        return true;
    }


    //This function accepts donors and if the cache exists, crawls through it and updates all the donors in the cache.
    //Also moving them to the trash or back if need be.
    public function reloadDonorsToCache($donors, $url = false)
    {
        
        if ($url===false) {
            $url = URL::to('');
        }

        if (!count($donors)) {
            return false;
        }

        //change to multiple donors if it's just one.
        if (isset($donors->id)) {
            $donors= Donor::withTrashed()->where('id', $donors->id)->get();
        }

        //Clear cache for donors counts
        $d = new Donor;
        $e = new Entity;
        $d->clearDonorCache($donors->first()->hysform_id, ['donors'], null);


        //Reload Sponsorships cache here!
        $donor_id_array = [];
        foreach ($donors as $donor) {
            $donor_id_array[]=$donor->id;
        }

        $e->reloadSponsorshipsToCache(DonorEntity::whereIn('donor_id', $donor_id_array)->get());


        //first figure out where this donor belongs in the cache tables
        foreach ($donors as $donor) {
            if ($donor->deleted_at==null) {
                $tables[$donor->id]['1']=false;
                $tables[$donor->id]['']=true;
            } else {
                $tables[$donor->id]['1']=true;
                $tables[$donor->id]['']= false;
            }
        }

        $caches=[];

        foreach ($tables[$donors->first()->id] as $k => $t) {
            $caches[$k]= Cache::get('showalldonorsajax-'.$donors->first()->hysform_id.'-'.$k);
        }

        $old_cache=$caches[''];
        
        $d = new Donor;

        $details_display= $d->getDonorAmountDetailsForWholeTable();
        
        $details= $d->getDonorAmountDetailsForWholeTable($donors, 'all');

        $fields= $fields = Donorfield::where('hysform_id', $donors->first()->hysform_id)->orderBy('field_order')->get();

        // get name fields for linking in view
        $nameFields=[];
        //get all title fields (except date and age type fields)
        $nf = Donorfield::where('hysform_id', $donors->first()->hysform_id)->where('is_title', 1)->where('field_type', '!=', 'hysDate')->where('field_type', '!=', 'hysAge')->orderBy('field_order')->get();
        foreach ($nf as $fk) {
            $nameFields[] = $fk->field_key;
        }

        //crawl through the cached tables and remove this donor from them all (if it exists, that is.)
        foreach ($caches as $k => $cache) {
            if ($cache!=null) {
                foreach ($donors as $donor) {
                    if (isset($cache['hashes'][$donor->id])) {
                        //Empty out the current contents of the cache for this entity.
                        unset($caches[$k]['pipeline'][$donor->id]);
                        unset($caches[$k]['hashes'][$donor->id]);
                    }
                }
            }
        }

        foreach ($donors as $donor) {
            $new_hash[$donor->id] = [
                'manage'=>'<small class="manage">
										<span class="label label-success"><a href="'. $url.'/admin/edit_donor/'.$donor->id.'"><span class="glyphicon glyphicon-pencil"></span> Edit</a></span> '
                                        . ' <span class="label label-success"><a href="'. $url.'/admin/upload_file/donor/'.$donor->id.'"><span class="glyphicon glyphicon-file"></span> Files</a></span>
										<span class="label label-success"><a href="'. $url.'/admin/sponsorships/'.$donor->id.'"><span class="glyphicon glyphicon-link"></span> Sponsor</a></span>
										<span class="label label-success"><a href="'. $url.'/admin/donations_by_donor/'.$donor->id.'"><span class="glyphicon glyphicon-usd"></span> Donations</a></span>
										</small>',
                'created_at' =>  '<span class="hidden">'.strtotime($donor->created_at).'</span>'.Carbon::createFromTimeStamp(strtotime($donor->created_at))->toFormattedDateString(),
                'updated_at' => '<span class="hidden">'.strtotime($donor->updated_at).'</span>'.Carbon::createFromTimeStamp(strtotime($donor->updated_at))->toFormattedDateString(),
                'email'=>$donor->email,
                'username'=>$donor->username];

            $new_pipeline[$donor->id]=json_decode($donor->json_fields, true);


            $profile = [];

            foreach ($fields as $f) {
                if (in_array($f->field_key, $nameFields)&&isset($new_pipeline[$donor->id][$f->field_key])) {
                    $profile[$f->field_key] = '<a href="'. $url.'/admin/edit_donor/'.$donor->id.'">'.$new_pipeline[$donor->id][$f->field_key].'</a>';
                } else {
                    if (isset($new_pipeline[$donor->id][$f->field_key])) {
                        $profile[$f->field_key]= $new_pipeline[$donor->id][$f->field_key];
                    } else {
                        $profile[$f->field_key]= '';
                    }
                }
            }


            $new_pipeline[$donor->id]['hys_profile'] = $e->formatProfile($profile, $fields);

            if (!empty($details_display)) {
                foreach ($details_display as $name => $detail) {
                    $new_hash[$donor->id][$name]= $details[$donor->id][$name];
                }
            }
        }


        //run through the caches and put them back into redis
        foreach ($caches as $k => $cache) {
            if ($cache!=null) {
                $mod= false;
                foreach ($donors as $donor) {
                    if ($tables[$donor->id][$k]===true) {
                            $mod = true;
                            //Set the new hash and pipeline
                            $cache['hashes'][$donor->id]=$new_hash[$donor->id];
                            $cache['pipeline'][$donor->id]=$new_pipeline[$donor->id];
                    }
                }

                if (isset($mod)) { //Slap the new data back on the cache!
                    Cache::put('showalldonorsajax-'.$donors->first()->hysform_id.'-'.$k, $cache, 1440);
                }
            }
        }

        return true;
    }




    public function getDonors($hysform_id, $trashed)
    {

        $key = 'showalldonors-'.$hysform_id;

        //It's become needful to turn off the Caching of the Donor counts because of the Auto-Emailing feature being added to the page
        // Cache::forget($key);

        $vars = Cache::remember($key, 10080, function () use ($hysform_id) {
            $donor = new Donor;

            $hysform = Hysform::find($hysform_id);
            $counts = $donor->getHysformCounts($hysform_id);
            $emailsettings = Emailsetting::where('client_id', $hysform->client_id)->first();
            $mailchimp_list_name= $donor->getMailchimpListName($emailsettings, $hysform);

            $reports = Report::whereHysformId($hysform_id)->get();

            $emailset = new Emailset;
            $emailsets = $emailset->getEmailSets($hysform->id);

            $donation = new Donation;
            $years = $donation->getAllYears($hysform_id);
            $template_errors=[];
            if (!empty($emailsets['default_emailset'])) {
                $t = new Emailtemplate;
                $e_s= Emailset::where('id', $emailsets['default_emailset']['id'])->get();
                $template_errors = $t->templateErrors($e_s);
            }

            return  [
                    'hysform_id'    => $hysform_id,
                    'hysform'       => $hysform,
                    'counts'        => $counts,
                    'reports'       => $reports,
                    'mailchimp_list_name'           => $mailchimp_list_name,
                    'emailsets' => $emailsets,
                    'years' => $years,
                    'template_errors' => $template_errors
                ];
        });

        $vars['trashed']=$trashed;

        return $vars;
    }

    public function getDonorsTable($hysform_id, $trashed)
    {
        
        

        $user= Sentry::getUser();
        $redis = RedisL4::connection();
        $d=new Donor;

        $a_donor=Donor::where('hysform_id', $hysform_id)->first();
        $details= $d->getDonorAmountDetailsForWholeTable();

        $hash = "admin:{$user->id}:donor-$hysform_id";
        $admin = $redis->hgetall($hash);
        $manage = false;
        $thumb = false;
        $created_at = false;
        $updated_at = false;
        $email = false;
        $username = false;
        $details_display=[];
        $offset =0;
        $nowrap=['0'=>'',
                        '1'=>'',
                        '2'=>'',
                        '3'=>'',
                        '4'=>'',
                        '5'=>'',
                        '6'=>''];

        if (!empty($admin['donor'])) {
            $fields = json_decode($admin['donor']);
            if (isset($fields->manage)) {
                $nowrap['0'] = '1';
                $offset = 1;
                $manage = $fields->manage;
                unset($fields->manage);
            }
            if (isset($fields->email)) {
                $nowrap['1'] = '1';
                $email = $fields->email;
                unset($fields->email);
            }
            if (isset($fields->username)) {
                $nowrap['2'] = '1';
                $username = $fields->username;
                unset($fields->username);
            }
            if (isset($fields->thumb)) {
                $nowrap['3'] = '1';
                $thumb = $fields->thumb;
                unset($fields->thumb);
            }
            if (isset($fields->created_at)) {
                $nowrap['4'] = '1';
                $created_at = $fields->created_at;
                unset($fields->created_at);
            }
            if (isset($fields->updated_at)) {
                $nowrap['5'] = '1';
                $updated_at = $fields->updated_at;
                unset($fields->updated_at);
            }
            $i = 6;
            foreach ($details as $name => $detail) {
                $n=strtolower(str_replace(' ', '_', $name));
                if (isset($fields->{$n})) {
                    $nowrap[$i] = '1';
                    $i++;
                    $details_display[$n]=$name;
                    unset($fields->{$n});
                }
            }
                //Set nowrap settings for datatables.
            $client_fields_count=   count((array)$fields);
            $to_splice=[];
            foreach ((array)$fields as $f) {
                $to_splice[]='';
            }
            array_splice($nowrap, $offset, 0, $to_splice);
        }
        


        if (!isset($fields)) {
            $fields = Donorfield::where('hysform_id', $hysform_id)->orderBy('field_order')->take(6)->get();
            $manage = true;
            $thumb = false;
        }

        foreach ($fields as $k => $f) {
            if (!isset($f->field_key)) {
                unset($fields->{$k});
            }
        }
        
        // get name fields for linking in view
        $nameFields=[];
        $nf = Donorfield::where('hysform_id', $hysform_id)->where('is_title', 1)->orderBy('field_order')->get();
        foreach ($nf as $fk) {
            $nameFields[] = $fk->field_key;
        }

        
        $years = Cache::remember('allyears-'.$hysform_id, 10080, function () use ($hysform_id) {

            //The slowdown is here!
            $donation = new Donation;
            return $donation->getAllYears($hysform_id);
        });

        $emailset = new Emailset;
        $emailsets = $emailset->getEmailSets($hysform_id);

        
        

        $vars = [
            'fields' => $fields,
            'nameFields' => $nameFields,
            'manage' => $manage,
            'thumb' => $thumb,
            'created_at' => $created_at,
            'updated_at' => $updated_at,
            'trashed' => $trashed,
            'email' => $email,
            'username' => $username,
            'hysform_id' => $hysform_id,
            'details_display' => $details_display,
            'nowrap'        => json_encode($nowrap),
            'number_of_donors' => Donor::where('hysform_id', $hysform_id)->count(),
            'years' => $years,
            'emailsets' => $emailsets

        ];

        return $vars;
    }


    public function getDonorsAjax($hysform_id, $url, $trashed)
    {
        // check for saved preferences
        // if none load first 6 fields

        $key= 'showalldonorsajax-'.$hysform_id.'-'.$trashed;

         // Cache::forget($key);

        // return array('hashes'=>array(),'pipeline'=>array());

        $cached_vars = Cache::remember($key, 10080, function () use ($hysform_id, $url, $trashed, $key) {
            $loading = Cache::get($key.'loading');
            if (empty($loading)) { //switch back to   if(empty($loading))
                Cache::put($key.'loading', '1', 5);

                $d = new Donor;
                $e = new Entity;
                
                $details= $d->getDonorAmountDetailsForWholeTable();

                // $details = array();

                if ($trashed == true) {
                    $donors = Donor::where('hysform_id', $hysform_id)->onlyTrashed()->get();
                } else {
                    $donors = Donor::where('hysform_id', $hysform_id)->get();
                }
                
                $fields = Donorfield::where('hysform_id', $hysform_id)->orderBy('field_order')->get();

                // get name fields for linking in view
                $nameFields=[];

                //get all title fields (except date and age fields)
                $nf = Donorfield::where('hysform_id', $hysform_id)->where('is_title', 1)->where('field_type', '!=', 'hysDate')->where('field_type', '!=', 'hysAge')->orderBy('field_order')->get();
                foreach ($nf as $fk) {
                    $nameFields[] = $fk->field_key;
                }

                if (!empty($details)) {
                    $whole_table_details = $d->getDonorAmountDetailsForWholeTable($donors, 'all');
                }

                foreach ($donors as $donor) {
                    $hashes[$donor->id] = [
                        'manage'=>'<small class="manage">
											<span class="label label-success"><a href="'. $url.'/admin/edit_donor/'.$donor->id.'"><span class="glyphicon glyphicon-pencil"></span> Edit</a></span> '
                                            . ' <span class="label label-success"><a href="'. $url.'/admin/upload_file/donor/'.$donor->id.'"><span class="glyphicon glyphicon-file"></span> Files</a></span>
											<span class="label label-success"><a href="'. $url.'/admin/sponsorships/'.$donor->id.'"><span class="glyphicon glyphicon-link"></span> Sponsor</a></span>
											<span class="label label-success"><a href="'. $url.'/admin/donations_by_donor/'.$donor->id.'"><span class="glyphicon glyphicon-usd"></span> Donations</a></span>
											</small>',
                        'created_at' =>  '<span class="hidden">'.strtotime($donor->created_at).'</span>'.Carbon::createFromTimeStamp(strtotime($donor->created_at))->toFormattedDateString(),
                        'updated_at' => '<span class="hidden">'.strtotime($donor->updated_at).'</span>'.Carbon::createFromTimeStamp(strtotime($donor->updated_at))->toFormattedDateString(),
                        'email'=>$donor->email,
                        'username'=>$donor->username];


                    $pipeline[$donor->id]=json_decode($donor->json_fields, true);

                    $profile = [];

                    foreach ($fields as $f) {
                        if (in_array($f->field_key, $nameFields)&&isset($pipeline[$donor->id][$f->field_key])) {
                            $profile[$f->field_key] = '<a href="'. $url.'/admin/edit_donor/'.$donor->id.'">'.$pipeline[$donor->id][$f->field_key].'</a>';
                        } else {
                            if (isset($pipeline[$donor->id][$f->field_key])) {
                                $profile[$f->field_key]= $pipeline[$donor->id][$f->field_key];
                            } else {
                                $profile[$f->field_key]= '';
                            }
                        }
                    }

                    $pipeline[$donor->id]['hys_profile'] = $e->formatProfile($profile, $fields);

                    if (!empty($details)) {
                        foreach ($details as $name => $detail) {
                                // $n=strtolower(str_replace(' ', '_', $name));
                                $hashes[$donor->id][$name]= $whole_table_details[$donor->id][$name];
                                // $hashes[$donor->id][$n]= '';
                        }
                    }
                }

                Cache::forget($key.'loading');
                if (!isset($hashes)) {
                    return ['hashes'=>[],'pipeline'=>[]];
                }

                return ['hashes'=>$hashes,'pipeline'=>$pipeline];
            } else {
                return ['hashes'=>[],'pipeline'=>[]];
            }
        });

        return $cached_vars;
    }

    public function getFundedEntities($client_id, $donor_id)
    {

        $settings= Setting::where('client_id', $client_id)->get();

        $funding_settings_array=[];

        foreach ($settings as $s) {
            $temp_s= json_decode($s->program_settings);
            if ($temp_s->program_type=='funding') {
                $funding_settings_array[]=$s->id;
            }
        }


        if (empty($funding_settings_array)) {
            return [];
        }

        $funding_list= implode(',', $funding_settings_array);
                
        $entities= [];
        foreach (Entity::where('client_id', $client_id)->get() as $e) {
            $p= Program::find($e->program_id);
            
            if (count($p)) {
                $s=Setting::find($p->setting_id);

                if (count($s)) {
                    $settings=json_decode($s->program_settings);

                    $donations= Donation::where('type', '1')->where('designation', $e->id)->where('donor_id', $donor_id)->count();

                    if ($settings->program_type=='funding'&&$donations) {
                        $entities[]=$e;
                    }
                }
            }
        }

        $redis = RedisL4::connection();
        $donor= new Donor;
        $all = [];
        foreach ($entities as $entity) {
                $info = $this->getEntityName($entity->id);
                
                $program = Program::find($entity->program_id);
                $settings = Setting::find($program->setting_id);
                $program_settings = json_decode($settings->program_settings, true);

                $info['created'] = $entity->created_at;
                
                
                $info['funding_percent']=$donor->getFundingPercent($entity);
            if ($info['funding_percent']!='100') {
                $info['funding_info']=$program_settings['currency_symbol'].$donor->getFundingInfo(Entity::find($entity->id));
            } else {
                $info['funding_info']=$donor->getFundingInfo(Entity::find($entity->id));
            }


                $all[$entity->id] = $info;
        }

            return $all;
    }

    public function whatHappensNext($commitment)
    {
        
        $amount= number_format($this->getFrequencyTotal($commitment->amount, $commitment->frequency));

        $on= $this->getNextPaymentDate($commitment);

        $first='';
        //$on= $commitment->last;

        if ($commitment->method=='1') {
            $first = "Payment of $".$amount." by Cash reminder email will be sent";
        }

        if ($commitment->method=='2') {
            $first="Payment of $".$amount." by Check reminder email will be sent";
        }

        if ($commitment->method=='3') {
            $first = "Credit Card will be charged $".$amount;
        }

        if ($commitment->method=='4') {
            $first = "Payment of $".$amount." by Wire Transfer reminder email will be sent";
        }

        if ($commitment->method=='5') {
            $first = "Payment of $".$amount." via Authorize ARB is expected";
        }


        return $first. $on;
    }

    public function getNextPaymentDate($commitment)
    {
        $date=Carbon::createFromTimeStamp(strtotime($commitment->last));

        $new_date=Carbon::now();
        if ($commitment->last=='0000-00-00'||$commitment->last==null) {
            return ' today';
        }

        if ($commitment->frequency=='1') {
            $new_date= $date->addMonth();
        }

        if ($commitment->frequency=='2') {
            $new_date=$date->addMonths(3);
        }

        if ($commitment->frequency=='3') {
            $new_date=$date->addMonths(6);
        }

        if ($commitment->frequency=='4') {
            $new_date=$date->addMonths(12);
        }
        
        if ($new_date<=Carbon::now()) {
            return ' today';
        }

        return '<span class="hidden">'.strtotime($new_date).'</span>'.' on '.$new_date->toFormattedDateString();
    }

    public function getLastFromNext($commitment, $next)
    {
        $date=Carbon::createFromTimeStamp(strtotime($next));

        // if($commitment->last=='0000-00-00')
        // 	return false;

        if ($commitment->frequency=='1') {
            $new_date= $date->subMonth();
        }

        if ($commitment->frequency=='2') {
            $new_date=$date->subMonths(3);
        }

        if ($commitment->frequency=='3') {
            $new_date=$date->subMonths(6);
        }

        if ($commitment->frequency=='4') {
            $new_date=$date->subMonths(12);
        }
        
        if (isset($new_date)) {
            return $new_date;
        } else {
            return false;
        }
    }


    /**
    * gets Commitments for a donor and returns array with id, name, sponsorship amount, frequency and 'until'
    * retrieve deleted with $trashed = true
    **/
    public function getCommitments($donor_id, $trashed = false)
    {
        $redis = RedisL4::connection();
        if ($trashed == true) {
            foreach (Commitment::onlyTrashed()->where('donor_id', $donor_id)->where('type', 2)->get() as $commitment) {
                $info['id'] = $commitment->id;
                $info['designation'] = $commitment->designation;
                $info['created'] = $commitment->created_at;
                $info['deleted'] = $commitment->deleted_at;
                $info['name'] = Designation::where('id', $commitment->designation)->pluck('name');
                $info['commitment_id'] = $commitment->id;
                $info['method']=$this->getMethod($commitment->method);

                $info['commit']='';
                if (isset($commitment->amount)) {
                    $info['commit']= $commitment->amount;
                }
                
                $all[] = $info;
            }
        } else {
            foreach (Commitment::where('donor_id', $donor_id)->where('type', 2)->get() as $commitment) {
                $info['id'] = $commitment->id;
                $info['designation'] = $commitment->designation;
                $info['created'] = $commitment->created_at;
                $info['commitment_id'] = $commitment->id;
                $info['name'] = Designation::withTrashed()->where('id', $commitment->designation)->pluck('name');
                $info['method']='';
                $info['next']=$this->whatHappensNext($commitment);
                
                if (!empty($commitment->method)) {
                    $info['method']=$this->getMethod($commitment->method);
                }
                
                $info['commit'] = '';
                if (!empty($commitment->amount)) {
                    $info['commit'] = $commitment->amount;
                }
                                
                if (!empty($commitment->until)) {
                    $info['until'] = $commitment->until;
                }
                
                $info['frequency'] = '';
                if (!empty($commitment->frequency)) {
                    $info['frequency'] = $this->getFrequency($commitment->frequency);
                }
                
                $all[] = $info;
            }
        }
        
        if (empty($all)) {
            $all = [];
        }
        return $all;
    }

    public function addDonationOrder($session_id, $program_id, $client_id, $donor_id, $method_name)
    {
            $data = Input::all();
            $donation = new Donation;
            $alert = 'success'; // use this as a flag for in case we run into problems
            $message = null;
            $redis=RedisL4::connection();
            $donor= new Donor;
            
            $designations = $redis->hgetall($session_id.':saved_designations');
            $entity_donations = $redis->hgetall($session_id.':saved_entity_id');
            $entity_frequencies = $redis->hgetall($session_id.':saved_entity_frequency');
            $designation_frequencies = $redis->hgetall($session_id.':saved_designation_frequency');
            
            $description = '';
            $program= new Program;
            $program_ids=$program->getPrograms($client_id, $program_id);
            
        if ($program_id=='none') {
            $currency='$'; //Makes dollars the default if there is no program
        } else {
            $temp_program=Program::find($program_ids[0]);
            $temp_program_settings = (array) json_decode(Setting::find($temp_program->setting_id)->program_settings);
            $currency=$temp_program_settings['currency_symbol'];
        }

            $names=[];
        foreach ($designations as $desig_id => $desig_amt) {
            $d = $this->getDesignation(2, $desig_id);
            $names[] = $d['name'].' @ '.$currency.$desig_amt.' ('.$donor->getFrequency($designation_frequencies[$desig_id]).')';
            $description .= $d['name'].' ';
        }
            $num_of_entities=0;
        foreach ($entity_donations as $en_id => $en_amt) {
            $num_of_entities++;
            $d = $this->getDesignation(1, $en_id);
            if ($donor->getFrequency($entity_frequencies[$en_id])=='Monthly') {
                $names[] = $d['name'].' @ '.$currency.$en_amt.' ('.$donor->getFrequency($entity_frequencies[$en_id]).')';
            } elseif ($donor->getFrequency($entity_frequencies[$en_id])=='One-Time') {
                $names[] = $d['name'].' @ '.$currency.$en_amt.' (Paid '.$donor->getFrequency($entity_frequencies[$en_id]).')';
            } else {
                $names[] = $d['name'].' @ '.$currency.$en_amt.' per Month (Paid '.$donor->getFrequency($entity_frequencies[$en_id]).')';
            }

            $description .= $d['name'].' ';
        }
            
            // create stripe customer
        if (($data[$method_name] == '3') && isset($data['number'])) {
            // validate data
            $rules = [
            'firstName' => 'required',
            'lastName' => 'required',
            'number' => 'required',
            'cvv' => 'required',
            'expiryMonth' => 'required',
            'expiryYear' => 'required'
            ];
                
            $validator = Validator::make($data, $rules);
            
            if ($validator->fails()) {
                $redis->hset($session_id.':messages', '10', 'There was an problem. Please try again.');
                $redis->expire($session_id.':messages', 3600);
                return false;
            }
                
            try {
                $card = [
                'firstName' => $data['firstName'],
                'lastName' => $data['lastName'],
                'number' => $data['number'],
                'cvv' => $data['cvv'],
                'expiryMonth' => $data['expiryMonth'],
                'expiryYear' => $data['expiryYear']
                ];
                $flname = $data['firstName'].' '.$data['lastName'];
                $params = ['description' => $flname];
                    
                $params['donor_id']=$donor_id;
                $response = $donation->createCustomer($card, $params, $client_id);
                    

                if ($response->success) {
                // customer create was successful: update database
                } else {
            // payment failed: display message to customer
                    $message = $response->result;
                    $redis->hset($session_id.':messages', '200', $message);
                    $redis->expire($session_id.':messages', 3600);
                    $alert = 'danger';
                    return false;
                }
            } catch (\Exception $e) {
                $message = $e->getMessage();
                $redis->hset($session_id.':messages', '20', $message);
                $redis->expire($session_id.':messages', 3600);
                $alert = 'danger';
                return false;
            }
        }

            $total_amount= 0;
        foreach ($designations as $ds) {
            $total_amount+=$ds;
        }
            
        foreach ($entity_donations as $id => $ed) {
            $total_amount+=$this->getFrequencyTotal($ed, $entity_frequencies[$id]);
        }

            //This will convert the total to a xx.xx format that Stripe can read
            $total_amount=number_format($total_amount, 2, '.', '');

            
            // create charge
        if ($data[$method_name] == '3') {
            if ($donation->isDonorCardActive($donor_id, $client_id)) {
                try {
                    $params = [
                        'amount' => $total_amount,
                        'currency' => 'usd',
                        'donor_id'=> $donor_id,
                        'description' => $description
                    ];
                    $response = $donation->createCharge($params, $client_id);
                        
                    if ($response->success) {
                        $message = 'Donation of $'.$total_amount.' Successful.';
                        $alert = 'success';
                        $result = $response->result;
                    } else {
                        $message = $response->result;
                        $redis->hset($session_id.':messages', '10', $message);
                        $redis->expire($session_id.':messages', 3600);
                        $alert = 'danger';
                        return false;
                    }
                } catch (\Exception $e) {
                    $message = $e->getMessage();
                    $alert = 'danger';
                    return false;
                }
            } else {
                $message = "Donor Card Information not stored correctly. Have Admin check payment Gateway settings.";
                $redis->hset($session_id.':messages', '10', $message);
                $redis->expire($session_id.':messages', 3600);
                return false;
            }
        }
            
        if ($alert == 'success') {
            foreach ($designations as $desig_id => $desig_amt) {
                if ($data[$method_name]=='3') { //If Credit card!
                    $donation= new Donation;
                    $donation->client_id = $client_id;
                    $donation->donor_id = $donor_id;
                        
                    $donation->type=2;
                    $donation->amount = $desig_amt;
                    $donation->designation = $desig_id;
                    $donation->method = $data[$method_name];
                    if ($designation_frequencies[$desig_id]==5) {
                        $donation->one_time=1;
                    }
                    if (isset($result)) {
                        $d_result = '(Transaction Reference = '.$result.')';
                        $donation->result = $d_result;
                    }
                        
                    $donation->save();
                }
            }

            foreach ($entity_donations as $en_id => $en_amt) {
                if (!isset($temp_en_id)) {
                        $temp_en_id=$en_id;
                }

                if ($data[$method_name]=='3') { //If Credit card!
                    $donation= new Donation;
                    $donation->client_id = $client_id;
                    $donation->donor_id = $donor_id;

                    $donation->type=1;
                    $donation->amount = $this->getFrequencyTotal($en_amt, $entity_frequencies[$en_id]);
                    $donation->designation = $en_id;
                    $donation->method = $data[$method_name];

                    if ($entity_frequencies[$en_id]==5) {
                        $donation->one_time=1;
                    }

                    if (isset($result)) {
                        $e_result = '(Transaction Reference = '.$result.')';
                        $donation->result = $e_result;
                    }
                        
                    $donation->save();
                    $this->setStatus($en_id);
                }
            }
                

            if (isset($temp_en_id)) {
                if ($program_id==null) {
                    $program_id = DB::table('entities')->where('id', $temp_en_id)->pluck('program_id');
                    $emailset_id=$d['emailset_id'];
                    // Log::info('Situation 1 -- Emailset_id: '.$emailset_id);
                } else {
                    $temp_en= Entity::find($temp_en_id);
                    if (count($temp_en)) {
                         $t_program= Program::find($temp_en->program_id);
                        if (count($t_program)) {
                            $emailset_id=$t_program->emailset_id;
                        }
                         // Log::info('Situation 2 -- Emailset_id: '.$emailset_id);
                    }

                    if (empty($emailset_id)) {
                        $emailset_id=$temp_program->emailset_id;
                        // Log::info('Situation 3 -- Emailset_id: '.$emailset_id);
                    }
                }

                $prog = new Program;
                $program_ids=$prog->getPrograms($client_id, $program_id);

                $program= Program::find($program_ids[0]);
                $program_settings = (array) json_decode(Setting::find($program->setting_id)->program_settings);
                $currency= $program_settings['currency_symbol'];
            } else {
                $currency='$'; //If no entities are selected, then dollars is the default
                if ($program_id=='none') {
                    if (empty($designations)) {
                        $emailset_id= null;
                        // Log::info('Situation 4 -- Emailset_id: '.$emailset_id);
                    } else {
                        reset($designations);
                        $designation= Designation::find(key($designations));
                        if ($designation!=null) {
                            $emailset_id= $designation->emailset_id;
                        } else {
                            $emailset_id=null;
                        }

                        // Log::info('Situation 5 -- Emailset_id: '.$emailset_id);
                    }
                } else {
                    $emailset_id=Program::find($program_id)->pluck('emailset_id');
                }
            }

                
            // send email receipt
            $email = new Emailtemplate;
            $admin_email= new Emailtemplate;

            $details['donor'] = $email->getDonor($donor_id);
            $donor= new Donor;
            $method = $donor->getMethod($data[$method_name]);
                
            $details['donation']=['designations'=>implode('<br/>', $names),'total_amount'=>$total_amount,'method'=>$method,'date'=> Carbon::now()->toFormattedDateString()];

            $entity = new Entity;
            $donor = $entity->getDonorName($donor_id);
            $toDonor = ['type' => 'donor', 'name' => $donor['name'], 'email' => $donor['email'], 'id' => $donor_id];
                
            if ($data[$method_name]=='3') { //If Credit card! Then send Receipt Email
                $emailSent = $email->sendEmail($emailset_id, $details, 'pay_receipt', $toDonor, $client_id);
            }
                
            if ($num_of_entities>0) {
                $to=['type' => 'admin','email' => '' ,'name' => 'admin'];
                $emailSent=false;
                $i=0;
                foreach ($entity_donations as $entity_id => $en_amount) {
                    $i++;
                    $d = $this->getDesignation(1, $entity_id);
                    $details['entity'] = $email->getEntity($entity_id);
                    $details['donation'] = [
                        'amount'=> $en_amount,
                        'method'=>$method,
                        'frequency' => $this->getFrequency($entity_frequencies[$entity_id]),
                        'date'=> Carbon::now()->toFormattedDateString(),
                        'total_amount' => $this->getFrequencyTotal($en_amount, $entity_frequencies[$entity_id]),
                        'donor_email'   =>$donor['email']
                        ];
                    if (!empty($names)) {
                        $details['donation']['designations'] = implode('<br/>', $names);
                    } else {
                        $details['donation']['designations'] = '';
                    }
                        
                    $adminEmailSent = $admin_email->sendEmail($emailset_id, $details, 'new_donor_admin', $to);

                    $the_donor = Donor::find($donor_id);
                    $new_donor_details['donor']= $email->getDonor($donor_id);
                    $new_donor_details['entity']= $email->getEntity($entity_id);
                    $new_donor_details['other']= ['date'=> Carbon::now()->toFormattedDateString(),'username'=>$the_donor->username];
                    $new_donor_details['donation']= $details['donation'];

                    $emailSent = $email->sendEmail($emailset_id, $new_donor_details, 'new_donor', $toDonor);
                }
            }

            if ($emailSent == true) {
                //$message .= "<p>Donation receipt emailed to donor.</p>";
            } else {
                //$message .= "<p>Donation receipt not emailed to donor.</p>";
            }
        }
            
        if (isset($message)) {
            $redis->hset($session_id.':messages', '10', $message);
            $redis->expire($session_id.':messages', 3600);
        }

            return true;
    }
    
        
    /**
    * Returns all donors (profiles and fields) associated with a hysform_id
    **/
    public function allDonors($hysform_id)
    {
        $donors = Donor::where('hysform_id', $hysform_id)->get();
        $fields = Donorfield::where('hysform_id', $hysform_id)->orderBy('field_order')->get();
        $processed = [];

        foreach ($donors as $donor) {
            $d = [];
            foreach ($donor->attributes as $key => $value) {
                if ($key!='json_fields') { //Exclude json_fields from this portion (it comes in later)
                    $d[$key] = $value;
                }
            }
            $json_fields= json_decode($donor->json_fields, true);

            if (empty($json_fields)) {
                $merge = $d;
            } else {
                $merge = array_merge($json_fields, $d);
            }
            
            $processed[$donor->id] = $merge;
        }

        $hysform = Hysform::find($hysform_id);
        
        return ['hysform' => $hysform, 'fields' => $fields, 'profiles' => $processed];
    }
    
    
    /**
     * oneDonor returns profile of one donor.
     *
     * @access public
     * @param mixed $donor_id
     * @return void
     */
    public function oneDonor($donor_id)
    {
        $profile = Donor::find($donor_id);
        $out_profile = [];
         
        if (count($profile)) {
            $out_profile = json_decode($profile->json_fields, true);
            $out_profile['client_id'] = $profile->client_id;
            $out_profile['hysform_id'] = $profile->hysform_id;
            $out_profile['username'] = $profile->username;
            $out_profile['email'] = $profile->email;
        }
        

        return $out_profile;
    }

    /**
    * Checks to see if any sponsorships exist, if there is one,
    * it takes the first and returns the program settings
    * This is primarily to display the Settings account text on the donor account page
    **/
    public function getSettings($donor_id, $program_id = null)
    {

        if ($program_id!=null) {
                $program = Program::find($program_id);
                $settings = Setting::find($program->setting_id);
                return $settings;
        } else {
            $sponsorships = $this->getSponsorships($donor_id);


            if (isset($sponsorships[0])) {
                $entity_id = $sponsorships[0]['id'];
                $entity = Entity::find($entity_id);
                $program = Program::find($entity->program_id);
                $settings = Setting::find($program->setting_id);
                return $settings;
            }
        }
            return null;
    }
    
    /**
    * Status is set in entities
    * Status options = 0,1,2
    *  0 = available
    *  1 = sponsored not available
    *  2 = available but checked out
    **/
    public function setStatus($entity_id, $dontClearCache = null)
    {
        $entity = Entity::withTrashed()->find($entity_id);
        
        if (count($entity)) {
            if ($entity->trashed()) {
                $entity->restore();
            }
            $program = Program::find($entity->program_id);
            
            if (count($program)) {
                $setting = Setting::find($program->setting_id);
                $program_settings = json_decode($setting->program_settings);
                $program_type = $program_settings->program_type;
                $current = $entity->status;
                
                if ($program_type == 'contribution') {
                    $commitments = Commitment::where('designation', $entity_id)->get();
                    
                    if (empty($entity->sp_num)) {
                        $sp_nums = explode(',', $program_settings->sp_num);
                        $entity->sp_num = $sp_nums[0];
                        $entity->save();
                    }

                    $total = 0;
                    foreach ($commitments as $c) {
                        $total = $total + $c->amount;
                    }
                }

                if ($program_type == 'funding') {
                    //$donations = Donation::where('type','1')->where('designation',$entity_id)->get();
                    
                    $total = DB::table('donations')->where('deleted_at', null)->where('type', '1')->where('designation', $entity_id)->sum('amount');

                    //return var_dump($total);
                    if (empty($entity->sp_num)) {
                        $entity->sp_num=$program_settings->number_spon;
                        $entity->save();
                    }

                    if ($total >= $entity->sp_num) {
                        //Delete commitments to this entity, because the funding has been fulfilled.
                        $commitments= Commitment::where('type', '1')->where('designation', $entity_id)->get();
                        if (count($commitments)) {
                            foreach ($commitments as $c) {
                                $DonorEntity = DonorEntity::find($c->donor_entity_id);
                                $DonorEntity->delete();
                                $c->delete();
                            }
                            $donor=new Donor;
                            $redis=RedisL4::connection();
                            $name= $donor->getEntityName($entity->id);

                            $oldmessage = Session::get('message');
                            if ($oldmessage=='Sponsorship Successfully Restored.') {
                                Session::flash('message', 'You cannot restore the sponsorship because the funding for <a href="'.URL::to('admin/edit_entity/'.$entity_id).'">'.$name['name'].'</a> is complete. <br/>If you <a href="'.URL::to('admin/edit_entity/'.$entity_id).'">change the "Funding Level Required" for '.$name['name'].'</a> then, you may restore this sponsorship.');
                                Session::flash('alert', 'warning');
                            } else {
                                Session::flash('message', Session::get('message').'<br/>The funding for <a href="'.URL::to('admin/edit_entity/'.$entity_id).'">'.$name['name'].'</a> is complete. '.count($commitments).' ' .(count($commitments)>1 ? ' sponsorships have been archived.' : ' sponsorship has been archived.'));
                                Session::flash('alert', 'info');
                            }
                        }
                    }
                }
                
                if ($program_type == 'number') {
                    $total = DonorEntity::whereEntityId($entity_id)->count();

                    // if(count(explode(',',$program_settings->number_spon))==1)
                    // {
                    // 	$entity->sp_num=$program_settings->number_spon;
                    // }
                    
                    if (empty($entity->sp_num)) {
                        $entity->sp_num=$program_settings->number_spon;
                        $entity->save();
                    }
                }

                if ($program_type == 'one_time') {
                    $entity->status = 0;
                    $entity->save();
                    return $entity->status;
                }



                if ($total >= $entity->sp_num) {
                    $entity->status = 1;
                } else {
                    $entity->status = 0;
                }


                
                if ($entity->status != $current) {
                    if ($current != 2 && $entity->status == 0) {
                        $entity->wait_time = Carbon::now();
                    } elseif ($entity->status == 1) {
                        $entity->wait_time = '0000-00-00';
                    }
                }
                $entity->save();

                 // Clear the Entities_list cache
                if ($dontClearCache==null) {
                    Cache::forget('entities_list-'.$entity->client_id);
                    $entity->reloadEntitiesToCache($entity);
                }
                
                return $entity->status;
            } else {
                return 'program not found';
            }
        } else {
            return 'entity not found';
        }
    }

    public function getPercent($entity_id)
    {

        $p= new Program;

        $type= $p->getProgramTypeFromEntity($entity_id);

        $entity = Entity::find($entity_id);

        if ($type=='funding') {
            return $this->getFundingPercent($entity);
        } elseif ($type=='contribution') {
            return $this->getContributionPercent($entity);
        } elseif ($type=='number') {
            return $this->getNumberPercent($entity_id);
        }
    }

    public function getInfo($entity_id)
    {

        $p= new Program;

        $type= $p->getProgramTypeFromEntity($entity_id);

        $entity = Entity::find($entity_id);

        if ($type=='funding') {
            return $this->getFundingInfo($entity);
        } elseif ($type=='contribution') {
            return $this->getContributionInfo($entity);
        } elseif ($type=='number') {
            return $this->getNumberInfo($entity);
        }
    }

    public function getContributionPercent($entity, $total_so_far = null)
    {
        if ($total_so_far==null) {
            $total_so_far= Commitment::where('type', '1')->where('designation', $entity->id)->sum('amount');
        }

        if ($entity->sp_num!=0) {
            return number_format($total_so_far/$entity->sp_num *100, 0, '', '');
        } else {
            return 0;
        }
    }

    public function getNumberPercent($entity_id)
    {
        $numOfSponsors= $this->getNumberOfSponsors($entity_id);
        $numNeeded= $this->getNumberOfSponsorsNeeded($entity_id);

        if ($numNeeded!=0) {
            return number_format($numOfSponsors/$numNeeded *100, 0, '', '');
        } else {
            return 0;
        }
    }

    public function getAmountToDate($entity_id)
    {
        return  number_format(Donation::where('type', '1')->where('designation', $entity_id)->sum('amount'));
    }

    public function getDonorAmountToDate($donor_id)
    {
        return  number_format(Donation::where('type', '1')->where('donor_id', $donor_id)->sum('amount'));
    }

    public function getMonthlyCommitmentAmount($entity_id)
    {
        $total= Commitment::where('type', '1')->where('designation', $entity_id)->sum('amount');
        return $total;
    }

    public function getNumberOfSponsors($entity_id)
    {
        return DonorEntity::where('entity_id', $entity_id)->count();
    }

    public function getNumberOfSponsorsNeeded($entity_id)
    {
        $entity= Entity::withTrashed()->find($entity_id);
        return $entity->sp_num;
    }

    public function getAmountDetails($entity_id)
    {

        $p= new Program;

        $entity=Entity::withTrashed()->find($entity_id);
        if (!count($entity)) {
            return [];
        }

        $type= $p->getProgramTypeFromEntity($entity_id);
        $settings= $p->getSettingsFromEntity($entity_id);

        $details = [];
        if ($type=='funding') {
            $details[$settings->currency_symbol . $this->getAmountToDate($entity_id)] = 'To Date';
            $details[$this->getFundingPercent($entity). '%'] =   'Complete';
        } elseif ($type=='contribution') {
            $details[$settings->currency_symbol . $this->getAmountToDate($entity_id)] = 'To Date';
            $details[$settings->currency_symbol . $this->getMonthlyCommitmentAmount($entity_id)] = 'Monthly';
            $details[$this->getContributionPercent($entity). '%'] =   'Complete';
        } elseif ($type=='number') {
            $details[$settings->currency_symbol . $this->getAmountToDate($entity_id)] = 'To Date';
            $details[$this->getNumberOfSponsors($entity_id).' of '. $this->getNumberOfSponsorsNeeded($entity_id)] =  'Sponsors';
            $details[$this->getNumberPercent($entity_id). '%'] =   'Complete';
        } elseif ($type=='one_time') {
            $details[$settings->currency_symbol . $this->getAmountToDate($entity_id)] = 'To Date';
        } else {
            return [];
        }

        return $details;
    }

    public function getAmountDetailsForTable($entity, $program_id = null)
    {
        //Turn this function off for now, it's too slow!!!
        //return array();

        $details = [ 'To Date' => '', 'Percent Complete' => '', 'Monthly Total' => '', 'Number of Sponsors' => '' ];
        if ($entity == '0') {
            $details = [];
            $p=new Program;

            $type= $p->getProgramType($program_id);

            if ($type=='funding') {
                $details['To Date'] = '';
                $details['Percent Complete'] =   '';
            } elseif ($type=='contribution') {
                $details['To Date'] = '';
                $details['Monthly Total'] = '';
                $details['Percent Complete'] =   '';
            } elseif ($type=='number') {
                $details['To Date'] = '';
                $details['Number of Sponsors'] = '';
                $details['Percent Complete'] =   '';
            }

            
            return $details;
        }

        $p= new Program;

        if (count($entity)==0) {
            return $details;
        }

        $type= $p->getProgramTypeFromEntity($entity->id);
        $settings= $p->getSettingsFromEntity($entity->id);

        if ($type=='funding') {
            $details['To Date'] = $settings->currency_symbol . $this->getAmountToDate($entity->id);
            $details['Percent Complete'] =   $this->getFundingPercent($entity).'%';
        } elseif ($type=='contribution') {
            $details['To Date'] = $settings->currency_symbol . $this->getAmountToDate($entity->id);
            $details['Monthly Total'] = $settings->currency_symbol . $this->getMonthlyCommitmentAmount($entity->id);
            $details['Percent Complete'] =   $this->getContributionPercent($entity).'%';
        } elseif ($type=='number') {
            $details['To Date'] = $settings->currency_symbol . $this->getAmountToDate($entity->id);
            $details['Number of Sponsors'] = $this->getNumberOfSponsors($entity->id).' of '. $this->getNumberOfSponsorsNeeded($entity->id);
            $details['Percent Complete'] =   $this->getNumberPercent($entity->id).'%';
        }
/*
		else
			return 0;
*/

        return $details;
    }


    public function getAmountDetailsForWholeTable($program_id, $entities = null)
    {
        //Turn this function off for now, it's too slow!!!

        // return array();

        $p= new Program;
        
        $type= $p->getProgramType($program_id);

        $settings= $p->getSettings($program_id);
        
        $details = [];

        $toDateAmounts=[];


        if ($entities == null) {
            $details = [];

            if ($type=='funding') {
                $details['To Date'] = '';
                $details['Percent Complete'] =   '';
                $details['info'] = '';
            } elseif ($type=='contribution') {
                $details['To Date'] = '';
                $details['Monthly Total'] = '';
                $details['Percent Complete'] =   '';
                $details['info'] = '';
            } elseif ($type=='number') {
                $details['To Date'] = '';
                $details['Number of Sponsors'] = '';
                $details['Percent Complete'] =  '';
                $details['info'] = '';
            }
            
            return $details;
        }

        $entities_list = $entities->lists('id');

        if (!empty($entities_list)) {
            foreach (Donation::whereIn('designation', $entities_list)->where('type', '1')->get() as $donation) {
                if (!isset($toDateAmounts[$donation->designation])) {
                    $toDateAmounts[$donation->designation] = $donation->amount;
                } else {
                    $toDateAmounts[$donation->designation] += $donation->amount;
                }

                if ($type=='funding') {
                    if (isset($howManyDonations[$donation->designation])) {
                        $howManyDonations[$donation->designation] ++;
                    } else {
                        $howManyDonations[$donation->designation] = 1;
                    }
                }
            }
        }

        if ($type=='contribution'&&!empty($entities_list)) {
            foreach (Commitment::where('type', '1')->whereIn('designation', $entities_list)->get() as $commitment) {
                if (isset($monthlyCommitmentAmounts[$commitment->designation])) {
                    $monthlyCommitmentAmounts[$commitment->designation]+=$commitment->amount;
                } else {
                    $monthlyCommitmentAmounts[$commitment->designation]=$commitment->amount;
                }

                if (isset($howManyDonors[$commitment->designation])) {
                    $howManyDonors++;
                } else {
                    $howManyDonors[$commitment->designation]=1;
                }
            }
        }

        if ($type=='number'&&!empty($entities_list)) {
            foreach (DonorEntity::whereIn('entity_id', $entities_list)->get()->lists('entity_id') as $donorEntity) {
                if (isset($numberOfSponsors[$donorEntity])) {
                    $numberOfSponsors[$donorEntity]++;
                } else {
                    $numberOfSponsors[$donorEntity]=1;
                }
            }
        }
        
        foreach ($entities as $entity) {
            if (!isset($toDateAmounts[$entity->id])) {
                $toDateAmounts[$entity->id]='0';
                $howManyDonations[$entity->id] ='0';
            }

            if ($type=='funding') {
                $details[$entity->id]['To Date'] = $settings->currency_symbol . $toDateAmounts[$entity->id];
                $details[$entity->id]['Percent Complete'] =   $this->getFundingPercent($entity, $toDateAmounts[$entity->id]);
                $details[$entity->id]['info']= $this->getFundingInfo($entity, $toDateAmounts[$entity->id], $howManyDonations[$entity->id]);
            } elseif ($type=='contribution') {
                if (!isset($monthlyCommitmentAmounts[$entity->id])) {
                    $monthlyCommitmentAmounts[$entity->id]='0';
                }
                if (!isset($howManyDonors[$entity->id])) {
                    $howManyDonors[$entity->id]='0';
                }

                $details[$entity->id]['To Date'] = $settings->currency_symbol . $toDateAmounts[$entity->id];
                $details[$entity->id]['Monthly Total'] = $settings->currency_symbol . $monthlyCommitmentAmounts[$entity->id];
                $details[$entity->id]['Percent Complete'] =   $this->getContributionPercent($entity, $monthlyCommitmentAmounts[$entity->id]);
                $details[$entity->id]['info']= $this->getContributionInfo($entity, $monthlyCommitmentAmounts[$entity->id], $howManyDonors[$entity->id]);
            } elseif ($type=='number') {
                if (isset($numberOfSponsors[$entity->id])) {
                    $numOfSponsors=$numberOfSponsors[$entity->id];
                } else {
                    $numOfSponsors= '0';
                }

                if ($entity->sp_num>0) {
                    $percent = number_format($numOfSponsors/$entity->sp_num *100, 0, '', '');
                } else {
                    $percent = '0';
                }

                $details[$entity->id]['To Date'] = $settings->currency_symbol . $toDateAmounts[$entity->id];
                $details[$entity->id]['Number of Sponsors'] = $numOfSponsors.' of '. $entity->sp_num;
                $details[$entity->id]['Percent Complete'] =   $percent;
                $details[$entity->id]['info']= $this->getNumberInfo($entity, $numOfSponsors);
            }
        }

        return $details;
    }

    public function getDonorAmountDetails($donor_id)
    {

        //Pull the settings from the first program belonging to the donor.
        $p= Program::where('client_id', Session::get('client_id'))->get()->first();
        $settings= $p->getSettings($p->id);

        //If not set, default to dollars
        if (empty($settings->currency_symbol)) {
            $currency_symbol= '$';
        } else {
            $currency_symbol= $settings->currency_symbol;
        }

        $de = DonorEntity::where('donor_id', $donor_id)->get();
        $donor = Donor::withTrashed()->where('id', $donor_id)->get()->first();

        $details = [];
        $details[$currency_symbol . $this->getDonorAmountToDate($donor_id)] = 'To Date';

        if ($de->count()=='1') {
            $details[$de->count()] =  'Sponsorship';
        } else {
            $details[$de->count()] =  'Sponsorships';
        }

        $details['Since'] = $donor->created_at->toFormattedDateString();

        return $details;
    }

    public function getDonorAmountDetailsForWholeTable($donors = null, $selected_fields = null)
    {

        $all_fields=  [
                'To Date'=> '',
                // 'Sponsorships'=>'',
                // 'Broken Sponsorships'=>'',
                'Credit Card' => '',
                'Who Added' => '',
                'Last Login' => '',
                'Do Not Email' => ''
                ];

        if ($donors==null) {
            return $all_fields;
        }

        if ($selected_fields=='all') {
            $selected_fields = $all_fields;
        }

        if (count($donors)) {
            $client_id= $donors->first()->client_id;
        } else {
            return false;
        }

        if (isset($selected_fields['Broken Sponsorships'])||isset($selected_fields['Sponsorships'])) {
            $des = DonorEntity::where('client_id', $client_id)->get();
            $commitments=Commitment::where('client_id', $client_id)->where('type', '1')->get();

            if (isset($selected_fields['Broken Sponsorships'])) {
                foreach ($commitments as $c) {
                    //If this commitment belongs to a donor in this table
                    if (count($donors->find($c->donor_id))) {
                        $de=$des->find($c->donor_entity_id);

                        //If there is a commitment with no donor_entity, it's broken, count it.
                        if (!count($de)) {
                            if (!isset($donor_array[$c->donor_id]['Broken Sponsorships'])) {
                                $donor_array[$c->donor_id]['Broken Sponsorships']=1;
                            } else {
                                $donor_array[$c->donor_id]['Broken Sponsorships']++;
                            }
                        } //If a commitment has a blank amount, count as broken.
                        elseif ($c->amount=='0.00') {
                            if (!isset($donor_array[$c->donor_id]['Broken Sponsorships'])) {
                                $donor_array[$c->donor_id]['Broken Sponsorships']=1;
                            } else {
                                $donor_array[$c->donor_id]['Broken Sponsorships']++;
                            }
                            $de_array[$de->id]=$c->id;
                        } else {
                            $de_array[$de->id]=$c->id;
                        }
                    }
                }
            }

            foreach ($des as $de) {
                //If this commitment belongs to a donor in this table
                if (count($donors->find($de->donor_id))) {
                    //If there is a commitment with no DonorEntity, count it as broken.
                    if (!isset($de_array[$de->id])) {
                        if (isset($selected_fields['Broken Sponsorships'])) {
                            if (!isset($donor_array[$de->donor_id]['Broken Sponsorships'])) {
                                $donor_array[$de->donor_id]['Broken Sponsorships']=1;
                            } else {
                                $donor_array[$de->donor_id]['Broken Sponsorships']++;
                            }
                        }
                    } else {
                        if (isset($selected_fields['Sponsorships'])) {
                            if (!isset($donor_array[$de->donor_id]['Sponsorships'])) {
                                $donor_array[$de->donor_id]['Sponsorships']=1;
                            } else {
                                $donor_array[$de->donor_id]['Sponsorships']++;
                            }
                        }
                    }
                }
            }
        }


        if (isset($selected_fields['To Date'])) {
            $donations= Donation::where('client_id', $client_id)->get();

            foreach ($donations as $donation) {
                //Removed this if statment to reduce database queries!
                // if(count($donors->find($donation->donor_id)))
                // {
                if (!isset($donor_array[$donation->donor_id]['To Date'])) {
                    $donor_array[$donation->donor_id]['To Date']= number_format($donation->amount);
                } else {
                    $donor_array[$donation->donor_id]['To Date']+= number_format($donation->amount);
                }
                // }
            }
        }

        $admins =[];
        foreach (User::where('client_id', $client_id)->get() as $admin) {
            $admins[$admin->id]=$admin->first_name. ' ' . $admin->last_name;
        }

        foreach ($donors as $donor) {
            if (!isset($donor_array[$donor->id]['To Date'])&&isset($selected_fields['To Date'])) {
                $donor_array[$donor->id]['To Date'] = '0';
            }

            if (!isset($donor_array[$donor->id]['Sponsorships'])&&isset($selected_fields['Sponsorships'])) {
                $donor_array[$donor->id]['Sponsorships'] = '';
            }

            if (!isset($donor_array[$donor->id]['Broken Sponsorships'])&&isset($selected_fields['Broken Sponsorships'])) {
                $donor_array[$donor->id]['Broken Sponsorships'] = '';
            }


            //Check if the Donor has a credit card on file
            if (isset($selected_fields['Credit Card'])) {
                $donor_array[$donor->id]['Credit Card']= (!empty($donor->stripe_cust_id)||!empty($donor->authorize_profile) ? 'Saved' : 'None');
            }
            
            if (isset($selected_fields['Do Not Email'])) {
                $donor_array[$donor->id]['Do Not Email']= ($donor->do_not_email==1 ? 'Enabled' : 'Disabled');
            }

            if (isset($selected_fields['Last Login'])) {
                if ($donor->last_login=='0000-00-00 00:00:00') {
                    $donor_array[$donor->id]['Last Login']= '<span class="hidden">0</span>'.'Never';
                } else {
                    $donor_array[$donor->id]['Last Login']= '<span class="hidden">'.strtotime($donor->last_login).'</span>'. Carbon::createFromTimeStamp(strtotime($donor->last_login))->toFormattedDateString();
                }
            }

            if (isset($selected_fields['Who Added'])) {
                if (empty($donor->who_added)) {
                    $donor_array[$donor->id]['Who Added']='';
                } else {
                    $who_added=json_decode($donor->who_added);

                    if ($who_added->type=='admin') {
                        if (isset($admins[$who_added->id])) {
                            $admin_name= $admins[$who_added->id];
                        } else {
                            $admin_name= 'Unknown Admin';
                        }
                        
                        $csv='';
                        if ($who_added->method=='csv') {
                            $csv=' - CSV';
                        }
                        $donor_array[$donor->id]['Who Added']=  $admin_name .$csv ;
                    } elseif ($who_added->type=='donor') {
                        $donor_array[$donor->id]['Who Added']=  'Self Signup' ;
                    }
                }
            }
        }

        return $donor_array;
    }


    public function getFundingPercent($entity, $total = null)
    {
        if ($total==null) {
            $total = DB::table('donations')->where('deleted_at', null)->where('type', '1')->where('designation', $entity->id)->sum('amount');
        }


        if ($total==0) {
            return 0;
        } elseif ($entity->sp_num==0) {
            return 0;
        } else {
            if ($total/$entity->sp_num *100>100) {
                return '100';
            }
            return number_format($total/$entity->sp_num *100);
        }
    }


    public function getNumberInfo($entity, $numOfSponsors = null)
    {

        if (!empty($entity) && $entity->sp_num>0) {
            if ($numOfSponsors==null) {
                $numOfSponsors = $this->getNumberOfSponsors($entity->id);
            }

            $needed = $entity->sp_num-$numOfSponsors;

            $s = 's';
            if ($numOfSponsors==1) {
                $s='';
            }

            $os= 's';
            if ($needed==1) {
                $os='';
            }

            if ($needed <1) {
                return $numOfSponsors." Sponsor".$s." | <strong>Complete!</strong>";
            } else {
                return $numOfSponsors." Sponsor".$s." | ".$needed." Needed";
            }
        } else {
            return '';
        }
    }

    public function getContributionInfo($entity, $total = null, $num_donors = null)
    {

        if ($total==null) {
            $total= Commitment::where('type', '1')->where('designation', $entity->id)->sum('amount');
        }

        if ($num_donors==null) {
            $num_donors = Commitment::where('type', '1')->where('designation', $entity->id)->count();
        }

        $remaining= number_format($entity->sp_num-$total);
        $s = 's';
        if ($num_donors==1) {
            $s='';
        }

        if ($remaining<0||$remaining==0) {
            $remaining=0;
            return $num_donors." Sponsor".$s." | <strong>Complete!</strong>";
        }
        if ($entity->sp_num==0) {
            return  $num_donations." Sponsors";
        } else {
            return  $remaining . " To Go | ".$num_donors." sponsor".$s;
        }
    }

    public function getFundingInfo($entity, $total = null, $num_donations = null)
    {

        if ($total==null) {
            $total = DB::table('donations')->where('deleted_at', null)->where('type', '1')->where('designation', $entity->id)->sum('amount');
        }
        if ($num_donations==null) {
            $num_donations = DB::table('donations')->where('deleted_at', null)->where('type', '1')->where('designation', $entity->id)->count();
        }


        $remaining= number_format($entity->sp_num-$total);
        $s = 's';
        if ($num_donations==1) {
            $s = '';
        }
        if ($remaining<0||$remaining==0) {
            $remaining=0;
            return $num_donations." Donation".$s." | <strong>Funding Complete!</strong>";
        }
        if ($entity->sp_num==0) {
            return  $num_donations." Donations";
        } else {
            return  $remaining . " To Go | ".$num_donations." Donation".$s;
        }
    }

    // public function getFundingInfo($entity->id)
    // {

    // 	$total= Commitment::where('type','1')->where('designation',$entity->id)->sum('amount');

    // 	$num_donors = Commitment::where('type','1')->where('designation',$entity->id)->count();

    // 	$remaining= number_format($entity->sp_num-$total);
    // 	$s = 's';
    // 	if($num_donors==1)
    // 		$s='';

    // 	if($remaining<0||$remaining==0)
    // 	{
    // 		$remaining=0;
    // 		return $num_donors." Sponsor".$s." | <strong>Complete!</strong>";
    // 	}
    // 	if($entity->sp_num==0)
    // 		return  $num_donations." Sponsors";
    // 	else
    // 		return  $remaining . " To Go | ".$num_donors." sponsor".$s;

    // }

    public function getFundingRemaining($entity_id)
    {

        $total = DB::table('donations')->where('deleted_at', null)->where('type', '1')->where('designation', $entity_id)->sum('amount');
        $num_donations = DB::table('donations')->where('deleted_at', null)->where('type', '1')->where('designation', $entity_id)->count();
        $entity= Entity::find($entity_id);

        $remaining= number_format($entity->sp_num-$total);

        if ($remaining<0) {
            $remaining=0;
        }
        if ($entity->sp_num==0) {
            return  '';
        } else {
            return  $remaining;
        }
    }


     /**
     * returns the designation whether it is an entity or an actual designation.
     *
     * @access public
     * @param mixed $type
     * @param mixed $designation_id
     * @param mixed $redis
     * @return void
     */
    public function getDesignation($type, $designation_id)
    {
        if ($type == 1) {
            $donor = new Donor;
            $entity = $donor->getEntityName($designation_id);
            $program = Program::find($entity['program_id']);
            if (!count($program)) {
                return $designation = ['name'=> 'Error: Designation Not Found'];
            }
            $designation = ['name' => $entity['name'], 'emailset_id' => $program->emailset_id];
        } elseif ($type == 2) {
            $d = Designation::find($designation_id);

            if (isset($d)) {
                $designation = ['code' => $d->code, 'name' => $d->name, 'emailset_id' => $d->emailset_id];
            } else {
                $designation = ['name'=> 'Error: Designation Not Found'];
            }
        }
        return $designation;
    }

     /**
     * returns the designation whether it is an entity or an actual designation.
     *
     * @access public
     * @param mixed $type
     * @param mixed $commitment
     * @param mixed $redis
     * @return void
     */
    public function getCommitmentDesignation($commitment)
    {

        $designation = ['name'=> 'Error: Designation Not Found'];
        if ($commitment->type == 1) {
            $donor = new Donor;
            $entity = $donor->getEntityName($commitment->designation);

            $program = Program::find($entity['program_id']);

            if (!count($program)) {
                return $designation = ['name'=> 'Error: Designation Not Found'];
            }

            $designation = ['name' => $entity['name'], 'emailset_id' => $program->emailset_id];

            //Check the Donor_Entity to see if the sponsorship is a sub-sponsorship
            $donor_entity= DonorEntity::find($commitment->donor_entity_id);
            if (count($donor_entity)&&$donor_entity->program_id!='0') {
                $a_program= Program::find($donor_entity->program_id);
                //If it's a sub-program, use the donor_entity Program emailset
                if (count($a_program)&&$a_program->emailset_id) {
                    $designation['emailset_id']=$a_program->emailset_id;
                }
            }
        } elseif ($commitment->type == 2) {
            $d = Designation::find($commitment->designation);

            if (isset($d)) {
                $designation = ['code' => $d->code, 'name' => $d->name, 'emailset_id' => $d->emailset_id];
            } else {
                $designation = ['name'=> 'Error: Designation Not Found'];
            }
        }
        return $designation;
    }
    


    public function getHysformCounts($hysform_id)
    {

        $counts= [];

        $counts['all'] = Donor::where('hysform_id', $hysform_id)->get()->count();

        $counts['trashed'] = Donor::where('hysform_id', $hysform_id)->onlyTrashed()->get()->count();

        return $counts;
    }

    public function getPaymentOptions($program_id, $client_id = null)
    {

        if ($client_id==null) {
            $client_id=Session::get('client_id');
        }

        $p = new Program;
        $dntns = new Donation;

        $program_settings= (array) $p->getSettings($program_id);

        if (count($program_settings)) {
            $checks='';
            if (isset($program_settings['checks'])) {
                $checks=$program_settings['checks'];
            }
            $login_box='';
            if (isset($program_settings['login_box'])) {
                $login_box=$program_settings['login_box'];
            }

            $cash='';
            if (isset($program_settings['cash'])) {
                $cash=$program_settings['cash'];
            }

            $wire_transfer=isset($program_settings['wire_transfer'])? $program_settings['wire_transfer']: '';

            $hide_payment_method=isset($program_settings['hide_payment_method'])? $program_settings['hide_payment_method']: '';


            $designations_allowed='';
            if (isset($program_settings['designations'])) {
                $designations_allowed=$program_settings['designations'];
            }
            


            $useCC=$dntns->checkuseCC($client_id);
            if (!isset($program_settings['stripe'])||$program_settings['stripe']!='1') {
                $useCC=false;
            }


            $payment_options= [];
            $default_payment_method= '';
            if ($cash=='1') {
                $payment_options['1']= 'Cash';
                 $default_payment_method = '1';
            }
            if ($checks=='1') {
                $payment_options['2'] = 'Check';
                 $default_payment_method = '2';
            }
            if ($wire_transfer=='1') {
                $payment_options['4'] = 'Wire Transfer';
                 $default_payment_method = '4';
            }

            if ($useCC==true) {
                $payment_options['3'] = 'Credit Card';
                $default_payment_method = '3';
            }
            
            return [
                'payment_options'=>$payment_options,
                'default_payment_method' => $default_payment_method,
                'hide_payment_method'=> $hide_payment_method,
                'useCC'=>$useCC];
        }
    }
    
    /**
     * convert the method id stored in the donation table to human readable format.
     *
     * @access public
     * @param mixed $method_id
     * @return void
     */
    public function getMethod($method_id = null)
    {
        $method = '';
        switch ($method_id) {
            case 1:
                $method = 'Cash';
                break;
            case 2:
                $method = 'Check';
                break;
            case 3:
                $method = 'Credit Card';
                break;
            case 4:
                $method = 'Wire Transfer';
                break;
            case 5:
                $method = 'Authorize ARB Subscription';
                break;
        }
        
        return $method;
    }


    /**
     * Return an array of the available payment methods
     *
     * @access public
     * @param mixed $method_id
     * @return void
     */
    public function getMethods()
    {
        $client_id=Session::get('client_id');

        if (isset($client_id)) {
            $client= Client::find($client_id);
        }

        if (count($client)) {
            if ($client->arb_enabled=='1') {
                return ['1'=>'Cash','2' => 'Check','3'=>'Credit Card','4'=>'Wire Transfer','5'=>'Authorize ARB Subscription'];
            } else {
                return ['1'=>'Cash','2' => 'Check','3'=>'Credit Card','4'=>'Wire Transfer'];
            }
        } else {
            return ['1'=>'Cash','2' => 'Check','3'=>'Credit Card','4'=>'Wire Transfer'];
        }
    }



    public function getFrequencyTotal($amount, $frequency)
    {
        $total=0;

        //Zero and 5 are both one-time donations
        if ($frequency==0) {
            $total += $amount;
        }
        if ($frequency==1) {
            $total += $amount;
        }
        if ($frequency==2) {
            $total += $amount * 3;
        }
        if ($frequency==3) {
            $total += $amount * 6;
        }
        if ($frequency==4) {
            $total += $amount * 12;
        }
        if ($frequency==5) {
            $total += $amount;
        }

        return $total;
    }
    
    public function getFrequency($frequency_id)
    {
        switch ($frequency_id) {
            case 0:
                $frequency = 'One-Time';
                break;
            case 1:
                $frequency = 'Every Month';
                break;
            case 2:
                $frequency = 'Every 3 Months';
                break;
            case 3:
                $frequency = 'Every 6 Months';
                break;
            case 4:
                $frequency = 'Every Year';
                break;
            case 5:
                $frequency = 'One-Time';
                break;
        }
        return $frequency;
    }

    public function getFrequencies($type = false)
    {
        if ($type=='funding'||$type=='one_time') {
            return ['1' => 'Every Month','5' => 'One-Time'];
        } else {
            return ['1' => 'Every Month' ,'2' =>'Every 3 Months', '3' =>'Every 6 Months', '4' =>'Every Year'];
        }
    }


        
    public function getDesignationFrequencies()
    {
        return ['1' => 'Every Month' ,'2' =>'Every 3 Months', '3' =>'Every 6 Months', '4' =>'Every Year','5' => 'One-Time'];
    }

    public function getAddressInfo($donor_id, $gateway)
    {
        $info=[];
        $donor= Donor::find($donor_id);
        if (!count($donor)) {
            return [];
        }

        $fields=Donorfield::where('hysform_id', $donor->hysform_id)->where(function ($query) {
            $query->where('field_type', 'hysGatewayAddress')
                ->orWhere('field_type', 'hysGatewayCity')
                ->orWhere('field_type', 'hysGatewayState')
                ->orWhere('field_type', 'hysGatewayZipCode');
        })
        ->get();

        if (!count($fields)) {
            return [];
        }

        $profile = $this->oneDonor($donor_id);

        // return var_dump(array($fields,$profile));

        foreach ($fields as $key => $field) {
            if (isset($profile[$field->field_key])) {
                $type=$field->field_type;
                
            
                if ($type=='hysGatewayAddress') {
                    $type='billingAddress1';
                }
                if ($type=='hysGatewayCity') {
                    $type='billingCity';
                }
                if ($type=='hysGatewayState') {
                    $type='billingState';
                }
                if ($type=='hysGatewayZipCode') {
                    $type='billingPostCode';
                }

                    $info[$type]=$profile[$field->field_key];
            } else {
                $info[$field->field_type]='';
            }
        }
        return $info;
    }

    public function syncDonorsToMailchimp($donors, $old_email = null)
    {
        $batch= [];
        $batch_unsubscribe= [];

        if (isset($donors->id)) {
            $donors= Donor::withTrashed()->where('id', $donors->id)->get();
        }

        $hysform_id = $donors->first()->hysform_id;
        $hysform = Hysform::find($hysform_id);

        if (empty($hysform->mailchimp_list_id)) {
            return false;
        }

        $emailsettings= Emailsetting::where('client_id', $hysform->client_id)->first();

        if (empty($emailsettings->mailchimp_api)) {
            return false;
        }

        $name = $this->getMailchimpListName($emailsettings, $hysform);

        $fields= DonorField::where('hysform_id', $hysform_id)->where('is_title', '1')->get();
        $titles = [];
        
        foreach ($fields as $field) {
            $titles[]= $field->field_key;
        }

        foreach ($donors as $donor) {
            $title1='';
            $title2='';
            $i=0;
            if (!empty($titles)) {
                $decoded=json_decode($donor->json_fields);
                foreach ($titles as $t) {
                    if (isset($decoded->$t)&&!empty($decoded->$t)) {
                        if ($i==0) {
                            $title1= $decoded->$t;
                        } else {
                            $title2=$title2 .$decoded->$t." ";
                        }
                    }
                        $i++;
                }
                $title1=trim($title1);
                $title2=trim($title2);
            }

            if ($donor->trashed()) {
                $batch_unsubscribe[$donor->email]=[
                        'email'=>$donor->email];
            } else {
                $batch[$donor->email]=[
                    'email'=> [
                        'email'=>$donor->email
                        ],
                    'merge_vars'=>[
                        "FNAME"=>$title1,
                        "LNAME"=>$title2]];
            }
        }

        if (!empty($batch)) {
            Config::set('mailchimp::apikey', $emailsettings->mailchimp_api);
            $result = MailchimpWrapper::lists()->batchSubscribe($hysform->mailchimp_list_id, $batch, false, true, false);
        }

        if (!empty($batch_unsubscribe)) {
            Config::set('mailchimp::apikey', $emailsettings->mailchimp_api);
            MailchimpWrapper::lists()->batchUnsubscribe($hysform->mailchimp_list_id, $batch_unsubscribe, false, false, false);
        }

        if (!empty($old_email)) {
            $batch_unsubscribe[]=[
                        'email'=>$old_email];
            MailchimpWrapper::lists()->batchUnsubscribe($hysform->mailchimp_list_id, $batch_unsubscribe, false, false, false);
        }

        $result['name']=$name;

        return $result;
    }

    public function getMailchimpListName($emailsettings, $hysform = null)
    {


        if (isset($hysform)&&empty($hysform->mailchimp_list_id)) {
            return false;
        }

        if (!count($emailsettings)) {
            if (isset($hysform)&&!empty($hysform->mailchimp_list_id)) {
                Session::flash('message', 'Error: You don\'t have your <a href="'.URL::to('admin/edit_client_account#emailsettings').'">Mailchimp API key input.</a> Because of this, you cannot connect to Mailchimp.');
                Session::flash('alert', 'danger');
            }

            return false;
        }


        Config::set('mailchimp::apikey', $emailsettings->mailchimp_api);

        if (empty($emailsettings->mailchimp_api)) {
            return false;
        }

        $lists = [];
        try {
            $lists=MailchimpWrapper::lists()->getList()['data'];
        } catch (\Exception $e) {
            Session::flash('message', "Error connecting to Mailchimp: ".$e->getMessage()
                .'<br> Mailchimp Syncing has been disabled, please <a href="'.URL::to('admin/edit_client_account#emailsettings').'"> Re-input your Mailchimp API Key</a>');
            Session::flash('alert', 'danger');
            $emailsettings->mailchimp_api= '';
            $emailsettings->save();

            // $hysforms = Hysform::where('client_id',$emailsettings->client_id)->where('mailchimp_list_id','!=','')->get();
            // foreach($hysforms as $hysform)
            // {
            // 	$hysform->mailchimp_list_id='';
            // 	$hysform->save();
            // }

            return false;
        }

        if (empty($lists)) {
            Session::flash('message', 'Error: Your Mailchimp API Key is correct, but we found no lists.<br> You must first <a href="https://mailchimp.com">login to Mailchimp</a> and create a list before you can Sync your Donors.');
            Session::flash('alert', 'danger');

            $hysforms = Hysform::where('client_id', $emailsettings->client_id)->where('mailchimp_list_id', '!=', '')->get();
            foreach ($hysforms as $hysform) {
                $hysform->mailchimp_list_id='';
                $hysform->save();
            }

            return false;
        }

        if (empty($hysform)) {
            $list_processed = [];
            foreach ($lists as $list) {
                $list_processed[$list['id']]= $list['name'];
            }

            return $list_processed;
        }
        if (empty($hysform->mailchimp_list_id)) {
            return false;
        }

        $name = '';
        foreach ($lists as $list) {
            if ($list['id']==$hysform->mailchimp_list_id) {
                $name = $list['name'];
            }
        }

        if (empty($name)) {
            Session::flash('message', "Error connecting to Mailchimp List, the list could not be found, it was probably deleted<br> "
                .' Mailchimp Syncing has been disabled for <strong>'.$hysform->name.'</strong>, please <a href="'.URL::to('admin/edit_form/', [$hysform->id]).'"> Reset your Mailchimp List here.</a>');
            Session::flash('alert', 'danger');

            $hysform->mailchimp_list_id='';
            $hysform->save();
            return false;
        }

        return $name;
    }

    public function sendNotifyDonor($donor, $emailset_id)
    {

        if (count($donor)&&!empty($donor->email)) {
            //make random password eight characters long
            $random_password = $donor->rand_string(8);
            $hashed_password = Hash::make($random_password);
            $donor->password=$hashed_password;
            $donor->save();

            $e = new Entity;
            $name = $e->getDonorName($donor->id);
            $to = ['type' => 'donor', 'name' => $name['name'], 'email' => $donor->email, 'id' => $donor->id];
            $details['login_info'] = ['email'=>$donor->email,'username'=>$donor->username,'password'=>$random_password];
            $emailtemplate= new Emailtemplate;
            $details['donor'] = $emailtemplate->getDonor($donor->id);
            //Email the donor and give them their auto generated temporary password
            $sent = $emailtemplate->sendEmail($emailset_id, $details, 'notify_donor', $to);
            
            return $sent;
        }

        return false;
    }

    public function getYears($donor)
    {
        $donation = new Donation;
        return $donation->getYears($donor);
    }

    public function queueSendYearEndDonor($donor_id, $emailset_id, $year = null)
    {

            $sum = Donation::where('donor_id', $donor_id)->whereBetween('created_at', [Carbon::createFromDate($year, 1, 1),Carbon::createFromDate($year, 12, 31)])->sum('amount');

        if ($sum>0) {
            Queue::push('prepYearEndStatement', ['donor_id'=>$donor_id,'emailset_id'=>$emailset_id,'year' => $year]);
            return true;
        } else {
            return false;
        }
    }
}
