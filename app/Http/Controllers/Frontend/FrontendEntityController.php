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
use Client;
use Program;
use Field;
use Session;
use DB;
use Entity;
use Upload;
use Setting;
use Donor;
use Donation;
use URL;
use Emailtemplate;
    
use App\Http\Controllers\Controller;

class FrontendEntityController extends Controller
{
    

    function createFrontendSession()
    {
        $time = time(); // get current unix time
        $ssid = sha1($time . 'EVERSP'); // encrypt and salt it for fun
        $redis = RedisL4::connection();
                
        $redis->hset($ssid, 'logged_in', 'false');
         //Tracks frontend user for 20 min
        $redis->expire($ssid, 3600);
            
        return $ssid;
    }

    public function SaveEntity($client_id, $program_id, $entity_id, $session_id = null)
    {
        $redis = RedisL4::connection();
            
        $data = Input::all();

        if ($session_id == null || $redis->exists($session_id) != 1) {
            $session_id=$this->createFrontendSession();
        }

        if (!empty($data['sponsorship_amount'])) {
            //If the sponsorship amount was entered, trim the spaces
            $data['sponsorship_amount'] = trim($data['sponsorship_amount']);
                
            //and take out all non numerical characters except '.' and ','
            $data['sponsorship_amount'] = preg_replace("/[^0-9,.]/", "", $data['sponsorship_amount']);
        }

        $rules = [
            'sponsorship_amount' => ['required','numeric']
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->passes()) {
            $sp_amount=$data['sponsorship_amount'];
            //if($redis->exists($session_id)!=1)
                //return redirect('frontend/view_entity/'.$this->createFrontendSession().'/'.$client_id.'/'.$program_id.'/'.$entity_id);

            if (isset($data['frequency'])) {
                $default_frequency= $data['frequency'];
            } else {
                $default_frequency = 1; //Default frequency is one month, may be a good idea to move this to the DB end for the client to set
            }

            if (isset($data['monthly'])) {
                $default_frequency = 1;
            }
                

            $redis->hset($session_id.':saved_entity_id', $entity_id, $sp_amount);
            $redis->hset($session_id.':saved_entity_frequency', $entity_id, $default_frequency);

            $redis->expire($session_id.':saved_entity_id', 3600);
            $redis->expire($session_id.':saved_entity_frequency', 3600);

            $d= new Donor;

            $name= $d->getEntityName($entity_id, true);
                

            $redis->hset($session_id.':messages', '1', '<strong>'.$name['name'].'</strong> has been added to your order!');
            $redis->expire($session_id.':messages', 3600);

            return redirect('frontend/order/'.$client_id.'/'.$program_id.'/'.$session_id);
        }

        return redirect('frontend/view_entity/'.$client_id.'/'.$program_id.'/'.$entity_id.'/'.$session_id)
            ->withErrors($validator)
            ->withInput();
    }

    public function GetSaveEntity($client_id, $program_id, $entity_id, $session_id, $amount, $frequency)
    {
        $redis = RedisL4::connection();
            
        $data = Input::all();

        if ($session_id == null || $redis->exists($session_id) != 1) {
            $session_id=$this->createFrontendSession();
        }

        if (!empty($amount)) {
            //If the sponsorship amount was entered, trim the spaces
            $data['sponsorship_amount'] = trim($amount);
                
            //and take out all non numerical characters except '.' and ','
            $data['sponsorship_amount'] = preg_replace("/[^0-9,.]/", "", $data['sponsorship_amount']);
        }

        $rules = [
            'sponsorship_amount' => ['required','numeric']
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->passes()) {
            $sp_amount=$data['sponsorship_amount'];
            //if($redis->exists($session_id)!=1)
                //return redirect('frontend/view_entity/'.$this->createFrontendSession().'/'.$client_id.'/'.$program_id.'/'.$entity_id);

            if (isset($frequency)) {
                $default_frequency= $frequency;
            } else {
                $default_frequency = 1; //Default frequency is one month, may be a good idea to move this to the DB end for the client to set
            }

            $redis->hset($session_id.':saved_entity_id', $entity_id, $sp_amount);
            $redis->hset($session_id.':saved_entity_frequency', $entity_id, $default_frequency);

            $redis->expire($session_id.':saved_entity_id', 3600);
            $redis->expire($session_id.':saved_entity_frequency', 3600);

            $d= new Donor;

            $name= $d->getEntityName($entity_id, true);
                

            $redis->hset($session_id.':messages', '1', '<strong>'.$name['name'].'</strong> has been added back to your order!');
            $redis->expire($session_id.':messages', 3600);

            return redirect('frontend/order/'.$client_id.'/'.$program_id.'/'.$session_id);
        }

        return redirect('frontend/view_entity/'.$client_id.'/'.$program_id.'/'.$entity_id.'/'.$session_id)
            ->withErrors($validator)
            ->withInput();
    }
        

    public function ViewEntity($client_id, $program_id, $entity_id, $session_id = null)
    {

        if ($session_id == null) {
            $session_id=$this->createFrontendSession();
        }

        $redis = RedisL4::connection();

        if ($redis->exists($session_id)!=1) {
            $session_id=$this->createFrontendSession();
        }

        $entity_ids = $redis->hgetall($session_id.':saved_entity_id');

        $already_saved = false;
        if (isset($entity_ids[$entity_id])) {
            $already_saved= true;
        }

        $program= new Program;

        $program_ids=$program->getPrograms($client_id, $program_id);

        if ($program_ids[0]==false) {
            return $program_ids[1];
        }

        if (count($program_ids)==1) {
            $program= Program::where('client_id', $client_id)->find($program_id);
            $entity = Entity::where('client_id', $client_id)->find($entity_id);

            $settings= $program->getSettings($program_ids[0]);

            if (empty($settings->display_all)) {
                $next = Entity::allEntitiesByPrograms($program_ids)->where('client_id', $client_id)->where('status', 0)->where('id', '>', $entity_id)->min('id');
                $prev = Entity::allEntitiesByPrograms($program_ids)->where('client_id', $client_id)->where('status', 0)->where('id', '<', $entity_id)->max('id');
            } else {
                $next = Entity::allEntitiesByPrograms($program_ids)->where('client_id', $client_id)->where('id', '>', $entity_id)->min('id');
                $prev = Entity::allEntitiesByPrograms($program_ids)->where('client_id', $client_id)->where('id', '<', $entity_id)->max('id');
            }
                
            if (!count($entity)) {
                return redirect('/frontend/view_all/'.$client_id.'/'.$program_id.'/'.$session_id);
            }
        } else {
            $entity = Entity::where('client_id', $client_id)->find($entity_id);

            $next=null;
            $prev=null;

            if (count($entity)) {
                $program= Program::where('client_id', $client_id)->find($entity->program_id);
            } else {
                return redirect('/frontend/view_all/'.$client_id.'/'.$program_id.'/'.$session_id);
            }
        }
            

        $profile = json_decode($entity->json_fields, true);
        $profile['id'] = $entity_id;

        if (!isset($entity)) {
            return "Entity not available";
        }

            
        //Only get fields fields designated as 'public'
            
        $public_fields = Field::where('client_id', $client_id)->where('hysform_id', $program->hysform_id)->where('permissions', '=', 'public')->orderBy('field_order')->get();

        $donor= new Donor;

        $title=$donor->getEntityName($entity_id, true);
        $title=$title['name'];

        $upload=new Upload;

        $file_links=[];
        $image_links=[];

        foreach ($entity->uploads as $k => $file) {
            if ($file->permissions=='public') {
                if ($file->profile==1) {
                    $link = $upload->makeAWSlink($file);
                    $thumblink= $upload->makeAWSlinkThumb($file);
                }

                if ($file->profile!=1) {
                    if ($file->type=='image') {
                        $image_links[$k]['original'] = $upload->makeAWSlink($file);
                        $image_links[$k]['thumbnail'] = $upload->makeAWSlinkThumb($file);
                    } else {
                        $file_links[$k]['file_link']=$upload->makeAWSlink($file);
                        ;
                        $file_links[$k]['file_name']=$upload->makeAWSlinkThumb($file);
                        ;
                    }
                }
            }
        }

        // Limit the extra images and files to the three newest ones. (four including the profile photo.)
        $file_links=array_slice($file_links, -3, 3);
        $image_links=array_slice($image_links, -3, 3);

        if (isset($link)&&count($image_links)>0) {
            array_unshift($image_links, ['original' => $link,'thumbnail' => $thumblink]);
        }

        //This is for the dropdown list of values for the donor to select from
        $setting= Setting::find($program->setting_id);

        if (empty($setting)) {
            return "Error: No settings are associated with this program.";
        }

        $program_settings = (array) json_decode($setting->program_settings);

        $currency_symbol= $program_settings['currency_symbol'];
        $disable_program_link='';
        if (isset($program_settings['disable_program_link'])) {
            $disable_program_link=$program_settings['disable_program_link'];
        }

        $profilePicThumb = '';
        if (isset($link)) {
            $profilePic=$link;
            $profilePicThumb=$thumblink;
        } else {
            $program_settings = (array) json_decode(Setting::find($program->setting_id)->program_settings);

            if (isset($program_settings['placeholder'])&&$program_settings['placeholder']!='') {
                $profilePic=$program_settings['placeholder'];
            } else {
                $profilePic=URL::to('/images/placeholder.gif');
            }
        }

        if ($program_settings['program_type'] == 'contribution') {
            $sp_amount = explode(',', $program_settings['sponsorship_amount']);

            if (!empty($program_settings['labels'])) {
                $labels = explode(',', $program_settings['labels']);
                
                if (count($labels)==count($sp_amount)) {
                    foreach ($labels as $k => $label) {
                        $sp_amount[$k]= $sp_amount[$k].' '.$label;
                    }
                }
            }
                    
            $vars = ['sp_num' => $entity->sp_num, 'sp_amount' => $sp_amount, 'symbol' => $program_settings['currency_symbol']];
            $vars['program_type']='contribution';
        }

        if ($program_settings['program_type'] == 'funding') {
            $sp_amount = explode(',', $program_settings['sponsorship_amount']);
            $vars = ['sp_num' => $entity->sp_num, 'sp_amount' => $sp_amount, 'symbol' => $program_settings['currency_symbol']];
            $vars['program_type']='funding';
        }

        $display_info=false;
        if (isset($program_settings['display_info'])) {
            $display_info=$program_settings['display_info'];
        }

        $display_percent=false;
        if (isset($program_settings['display_percent'])) {
            $display_percent=$program_settings['display_percent'];
        }

        $start_date=$entity->created_at->toFormattedDateString();
        $d= new Donor;

        $entity_percent=$d->getPercent($entity->id);
        if ($entity_percent>100) {
            $entity_percent = 100;
        }
        if ($program_settings['program_type']=='number'||$entity_percent==100) {
            $entity_info= $d->getInfo($entity->id);
        } else {
            $entity_info= $program_settings['currency_symbol'].$d->getInfo($entity->id);
        }

        if ($program_settings['program_type'] == 'number') {
            if (empty($entity->sp_amount)) {
                $tmp_amt=explode(',', $program_settings['sponsorship_amount']);
                $entity->sp_amount=$tmp_amt[0];
                $entity->save();
            }
            $vars = ['sp_num' => $entity->sp_num, 'sp_amount' => $entity->sp_amount, 'symbol' => $program_settings['currency_symbol']];
            $vars['program_type']='number';
        }
        if ($program_settings['program_type'] == 'one_time') {
            if (empty($entity->sp_amount)) {
                $sp_amount = explode(',', $program_settings['sponsorship_amount']);
            } else {
                $sp_amount = [$entity->sp_amount];
            }
            $vars = ['sp_num' => $entity->sp_num, 'sp_amount' => $sp_amount, 'symbol' => $program_settings['currency_symbol']];
            $vars['program_type']='one_time';
        }

        $hide_frequency= isset($program_settings['hide_frequency']) ? $program_settings['hide_frequency'] : '' ;
            
        //This is simply extracting the profile page text from the settings
        $text_profile = Setting::find($program->setting_id)->text_profile;
        $info = Setting::find($program->setting_id)->info;

        //Parse short codes for sponsorship info
        $e_temp= new Emailtemplate;
        $info=$e_temp->parseShortCodes($profile, $info);

        $new_profile= $entity->formatProfile($profile, $public_fields);

        $dntns = new Donation;
        //Checks for API Key
        $useStripe = $dntns->checkUseStripe($client_id);
        //Checks for the client enabling Stripe in the program Settings

        $e = new Entity;
        $total = $e->getTotal($session_id);

        $payment_array=$donor->getPaymentOptions($program->id, $client_id);

        $client = Client::find($client_id);


        return view('frontend.views.printEntity', [
            'title'         =>  $title,
            'vars'          =>  $vars,
            'session_id'    =>  $session_id,
            'entity_id'     =>  $entity_id,
            'entity'        =>  $entity,
            'client_id'     =>  $client_id,
            'client'        =>  $client,
            'profile'       =>  $new_profile,
            'public_fields' =>  $public_fields,
            'program_id'    =>  $program_id,
            'text_profile'  =>  $text_profile,
            'info'          =>  $info,
            'donor'         => new Donor,
            'profilePic'    =>  $profilePic,
            'profilePicThumb' => $profilePicThumb,
            'image_links'   =>  $image_links,
            'file_links'        =>  $file_links,
            'start_date'    =>  $start_date,
            'entity_percent'=> $entity_percent,
            'entity_info' => $entity_info,
            'disable_program_link' =>$disable_program_link,
            'payment_options'       => $payment_array['payment_options'],
            'hide_payment_method'   => $payment_array['hide_payment_method'],
            'default_payment_method'=> $payment_array['default_payment_method'],
            'display_info' => $display_info,
            'display_percent' => $display_percent,
            'hide_frequency' => $hide_frequency,
            'next'          => $next,
            'prev'          => $prev,
            'total'         => $total,
            'currency_symbol'   => $currency_symbol,
            'already_saved' => $already_saved,
            ]);
    }
    
    public function viewRandomEntity($client_id, $program_id)
    {
        $entities=Entity::where('client_id', $client_id)->where('program_id', $program_id)->where('status', '0')->get();
        if ($entities->count()>0) {
            $random_entity= $entities->random(1);
            return redirect('frontend/view_entity/'.$client_id.'/'.$program_id.'/'.$random_entity->id);
        } else {
            return "Error: No Recipients found in this program.";
        }
    }
}
