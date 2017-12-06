<?php namespace App\Controllers\Admin;
 
    use Auth;
use BaseController;
use Form;
use Input;
use Redirect;
use Sentry;
use View;
use Setting;
use Session;
use Validator;
use Program;
use Donation;
use Field;
use Cache;
 
use App\Http\Controllers\Controller;

class SettingController extends Controller
{
        
    public function settings()
    {
        $settings = Setting::whereClientId(Session::get('client_id'))->with('programs')->get();
        return view('admin.views.viewSettings')->with('settings', $settings);
    }


    public function addSettings()
    {
        $donation= new Donation;

        return view('admin.views.addSettings')->with([
            'donation'=>$donation]);
    }
        
    public function postAddSettings()
    {
        $data = Input::all();
            

        if ($data['program_type']=='number'||$data['program_type']=='funding') {
            $rules=[
            'name' => 'required',
            'program_type' => 'required',
            'sponsorship_amount' => ['integers','no_blanks'],
            'number_spon' => ['integers','no_blanks']
            ];
        } else {
            $rules = [
                'name' => 'required',
                'program_type' => 'required',
                'sponsorship_amount' => ['integers','no_blanks']
            ];
        }

        $validator = Validator::make($data, $rules);
        
        if ($validator->passes()) {
            $labels = '';
            $number_spon = '';
            $sp_num = '';
            if (isset($data['labels'])) {
                $labels = $data['labels'];
                if (!empty($labels)) {
                    $l = explode(',', $labels);
                    $s = explode(',', $data['sponsorship_amount']);
                    if (count($l)!=count($s)) {
                        Session::flash('message', 'Error: There must be '.count($s).' "Amount Labels"  and '.count($l).' were found. The number of "Sponsorship Amounts" and "Amount Labels" must match up.');
                        Session::flash('alert', 'danger');
                        return redirect('admin/add_settings')
                        ->withErrors($validator)
                        ->withInput();
                    }
                }
            }
                
            if (isset($data['number_spon'])) {
                $number_spon = $data['number_spon'];
            }
                
            if (isset($data['sp_num'])) {
                $sp_num = $data['sp_num'];
            }
                
            $stripe = '';
            if (isset($data['stripe'])) {
                $stripe = $data['stripe'];
            }
            $login_box = '';
            if (isset($data['login_box'])) {
                $login_box = $data['login_box'];
            }
            $checks = '';
            if (isset($data['checks'])) {
                $checks = $data['checks'];
            }
            $designations = '';
            if (isset($data['designations'])) {
                $designations = $data['designations'];
            }
            $sorting = '';
            if (isset($data['sorting'])) {
                $sorting = $data['sorting'];
            }
            $display_all = '';
            if (isset($data['display_all'])) {
                $display_all = $data['display_all'];
            }
            $disable_program_link = '';
            if (isset($data['disable_program_link'])) {
                $disable_program_link= $data['disable_program_link'];
            }
            $wire_transfer = '';
            if (isset($data['wire_transfer'])) {
                $wire_transfer = $data['wire_transfer'];
            }
            $cash = '';
            if (isset($data['cash'])) {
                $cash = $data['cash'];
            }
            $display_percent = '';
            if (isset($data['display_percent'])) {
                $display_percent= $data['display_percent'];
            }
            $display_info = '';
            if (isset($data['display_info'])) {
                $display_info= $data['display_info'];
            }

            $hide_payment_method = isset($data['hide_payment_method'])? 'hidden' : '';

            $hide_frequency = isset($data['hide_frequency']) ? 'hidden' : '';

            $placeholder = isset($data['placeholder'])? $data['placeholder'] : '';

                
            $settings = new Setting;
            $settings->client_id = Session::get('client_id');
            $settings->name = $data['name'];
            $settings->program_settings = json_encode([
                'program_type' => $data['program_type'],
                'sp_num' => $sp_num,
                'labels' => $labels,
                'number_spon' => $number_spon,
                'sponsorship_amount' => $data['sponsorship_amount'],
                'currency_symbol' => $data['currency_symbol'],
                'duration' => $data['duration'],
                'stripe' => $stripe,
                'login_box' => $login_box,
                'checks'    => $checks,
                'designations' => $designations,
                'sorting'   => $sorting,
                'display_all'=>$display_all,
                'wire_transfer' => $wire_transfer,
                'cash'      =>  $cash,
                'hide_payment_method' => $hide_payment_method,
                'hide_frequency'    => $hide_frequency,
                'placeholder' =>    $placeholder,
                'disable_program_link' => $disable_program_link,
                'display_info' =>$display_info,
                'display_percent' => $display_percent
            ]);
                
            if (isset($data['allow_email'])) {
                $settings->allow_email = $data['allow_email'];
            }
            if (isset($data['show_payment'])) {
                $settings->show_payment = $data['show_payment'];
            }
            $settings->info = $data['info'];
            $settings->text_front = $data['text_front'];
            $settings->text_profile = $data['text_profile'];
            $settings->text_checkout = $data['text_checkout'];
            $settings->text_account = $data['text_account'];
            $settings->save();
                
            Session::flash('message', 'Settings Saved.');
            Session::flash('alert', 'success');
            return redirect('admin/settings');
        }
            
        Session::flash('message', 'There was a problem with your submission. Please see below for details');
        Session::flash('alert', 'danger');
        return redirect('admin/add_settings')
            ->withErrors($validator)
            ->withInput();
    }
        
    public function editSettings($settings_id)
    {

        $settings = Setting::where('client_id', Session::get('client_id'))->find($settings_id);

        if (count($settings)==0) {
            return "Error: Donor Form Not Found.";
        }
        $settings = $settings->toArray();
        $setting= new Setting;
        $program_settings = json_decode($settings['program_settings']);
        $donation= new Donation;

        // $hysforms = $setting->getFieldsFromSettings($settings_id);

        unset($settings['program_settings']);
            
        foreach ($program_settings as $k => $v) {
            $settings[$k] = $v;
        }

        $program_type_allowed = $this->isProgramTypeAllowed($settings_id);

            
        return view('admin.views.editSettings')
            ->with([
                'settings'=> $settings,
                'program_type_allowed' => $program_type_allowed,
                'donation'  =>$donation,
                // 'hysforms' => $hysforms
                ]);
    }

    public function isProgramTypeAllowed($settings_id)
    {

        $programs=Program::whereClientId(Session::get('client_id'))->where('setting_id', $settings_id)->where('link_id', '!=', '0')->get();

        $return_vals=[];
        foreach ($programs as $program) {
                $return_vals[$program->id] = $program->name;
        }

        if (empty($return_vals)) {
            return "true";
        } else {
            return $return_vals;
        }
    }
        
    public function postEditSettings($settings_id)
    {
        $data = Input::all();
            
        //This change made to keep client from changing program type.
        $settings = Setting::find($settings_id)->toArray();
        $program_settings = json_decode($settings['program_settings']);
        $program_type= $program_settings->program_type;

        if ($program_type=='number'||$program_type=='funding') {
            $rules=[
            'name' => 'required',
            'sponsorship_amount' => ['integers','no_blanks'],
            'number_spon' => ['integers','no_blanks']
            ];
        } else {
            $rules = [
                'name' => 'required',
                //'program_type' => 'required',
                'sponsorship_amount' => ['integers','no_blanks'],
                'sp_num'    => ['integers','no_blanks']
            ];
        }
            
        $validator = Validator::make($data, $rules);
        
        if ($validator->passes()) {
            $labels = '';
            $number_spon = '';
            $sp_num = '';

                


            if (isset($data['labels'])) {
                $labels = $data['labels'];
                if (!empty($labels)) {
                    $l = explode(',', $labels);
                    $s = explode(',', $data['sponsorship_amount']);
                    if (count($l)!=count($s)) {
                        Session::flash('message', 'Error: There must be '.count($s).' "Amount Labels"  and '.count($l).' were found. The number of "Sponsorship Amounts" and "Amount Labels" must match up.');
                        Session::flash('alert', 'danger');
                        $labels='';
                        return redirect('admin/edit_settings/'.$settings_id.'')
                        ->withErrors($validator)
                        ->withInput();
                    }
                }
            }
                
            if (isset($data['number_spon'])) {
                $number_spon = $data['number_spon'];
            }
                
            if (isset($data['sp_num'])) {
                $sp_num = $data['sp_num'];
            }
                
            $allow_email = '';
            if (isset($data['allow_email'])) {
                $allow_email = $data['allow_email'];
            }
            $show_payment = '';
            if (isset($data['show_payment'])) {
                $show_payment = $data['show_payment'];
            }
            $stripe = '';
            if (isset($data['stripe'])) {
                $stripe = $data['stripe'];
            }
            $login_box = '';
            if (isset($data['login_box'])) {
                $login_box = $data['login_box'];
            }
            $checks = '';
            if (isset($data['checks'])) {
                $checks = $data['checks'];
            }
            $designations = '';
            if (isset($data['designations'])) {
                $designations = $data['designations'];
            }
            $sorting = '';
            if (isset($data['sorting'])) {
                $sorting = $data['sorting'];
            }
            $display_all='';
            if (isset($data['display_all'])) {
                $display_all = $data['display_all'];
            }
            $display_percent = '';
            if (isset($data['display_percent'])) {
                $display_percent= $data['display_percent'];
            }
            $display_info = '';
            if (isset($data['display_info'])) {
                $display_info= $data['display_info'];
            }

            $wire_transfer = '';
            if (isset($data['wire_transfer'])) {
                $wire_transfer = $data['wire_transfer'];
            }
            $cash = '';
            if (isset($data['cash'])) {
                $cash = $data['cash'];
            }
            $hide_payment_method = isset($data['hide_payment_method'])? 'hidden' : '';

            $hide_frequency = isset($data['hide_frequency']) ? 'hidden' : '';

            $placeholder = isset($data['placeholder'])? $data['placeholder'] : '';

                
            $settings = Setting::find($settings_id);
            $settings->name = $data['name'];
            $settings->program_settings = json_encode([
                'program_type' => $program_type,
                'sp_num' => $sp_num,
                'labels' => $labels,
                'number_spon' => $number_spon,
                'sponsorship_amount' => $data['sponsorship_amount'],
                'currency_symbol' => $data['currency_symbol'],
                'duration' => $data['duration'],
                'stripe' => $stripe,
                'login_box' => $login_box,
                'checks'    => $checks,
                'designations' => $designations,
                'sorting'   => $sorting,
                'display_all'=>$display_all,
                'wire_transfer' => $wire_transfer,
                'cash'      =>  $cash,
                'hide_payment_method' => $hide_payment_method,
                'hide_frequency'    => $hide_frequency,
                'placeholder'   =>  $placeholder,
                'display_info' =>$display_info,
                'display_percent' => $display_percent
            ]);
            $settings->allow_email = $allow_email;
            $settings->show_payment = $show_payment;
            $settings->info = $data['info'];
            $settings->text_front = $data['text_front'];
            $settings->text_profile = $data['text_profile'];
            $settings->text_checkout = $data['text_checkout'];
            $settings->text_account = $data['text_account'];
            $settings->save();

            $program = new Program;
            $program->flushListFromSettings($settings->id);
                
            Session::flash('message', 'Settings Saved.');
            Session::flash('alert', 'success');
            return redirect('admin/settings');
        }
            
        Session::flash('message', 'There was a problem with your submission. Please see below for details');
        Session::flash('alert', 'danger');
        return redirect('admin/edit_settings/'.$settings_id.'')
            ->withErrors($validator)
            ->withInput();
    }
        
    public function removeSettings($settings_id)
    {
        $settings = Setting::find($settings_id);
        $settings->delete();
            
        return redirect('admin/settings');
    }

    public function fixFundingSettings()
    {
        $i=0;
        $program = new Program;
        foreach (Setting::all() as $setting) {
            $p_s = (array) json_decode($setting->program_settings);

            if ($p_s['program_type']=='funding') {
                $i++;
                $p_s['display_info']='1';
                $p_s['display_percent']='1';
                $setting->program_settings= json_encode($p_s);
                $setting->save();
                $program->flushListFromSettings($setting->id);
            }
        }
        return 'Fixed '. $i. ' Settings';
    }
}
