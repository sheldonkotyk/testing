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
use Donorfield;
use Session;
use DB;
use Hysform;
use Donation;
use Cache;
use Entity;
use Donor;

use App\Http\Controllers\Controller;

class FieldController extends Controller
{
    

    public function getFieldTypes($type)
    {
        $field_types = ['hysText' => 'Text', 'hysTextarea' => 'Textarea', 'hysStatic' => 'Static Text','hysAge' => 'Age', 'hysDate' => 'Date', 'hysLink' => 'Link', 'hysSelect' => 'Select List', 'hysCheckbox' => 'Checkbox', 'hysTable' => 'Table'];
        if ($type == 'entity'||$type == 'donor') {
            $field_types['hysCustomid'] = 'Auto increment ID';
        }

            $donation= new Donation;
            $gateway= $donation->checkUseCC(Session::get('client_id'));

        if ($type=='donor'&&$gateway) {
            $field_types['hysGatewayAddress']='Payment Gateway Address';
            $field_types['hysGatewayCity']='Payment Gateway City';
            $field_types['hysGatewayState']='Payment Gateway State';
            $field_types['hysGatewayZipCode']='Payment Gateway Zip Code';
        }

        return $field_types;
    }
    public function addFormField($id, $type)
    {
        $hysform = Hysform::find($id);

        $type_name=$hysform->getFormType($id);

        $field_types=$this->getFieldTypes($type);

        $donation= new Donation;
        $gateway= $donation->checkUseCC(Session::get('client_id'));

        return view('admin.views.addFormField')->with([
                'hysform'=> $hysform,
                'type'=> $type,
                'type_name'=>$type_name,
                'field_types'=> $field_types,
                'gateway'=> $gateway]);
    }
        
    public function postAddFormField($id, $type)
    {
 // $type is entity, donor, submit
        $data = Input::all();
            
        $rules = [
            'field_label' => 'required|alpha_num_spaces|min:3|not_in:id,program id,Program id,Program Id,Program ID,status,Status,sp amount,sp num,updated at,Updated at,Updated At,Username,username,email,Email,password,Password,last login,Last login,Last Login'
        ];
           
        $validator = Validator::make($data, $rules);
            
        // check to make sure there isn't already an auto increment field for this form
        if ($data['field_type'] == 'hysCustomid') {
            if ($type=='entity') {
                $check = Field::whereHysformId($id)->where('field_type', 'hysCustomId')->first();
            }
            if ($type=='donor') {
                $check = Donorfield::whereHysformId($id)->where('field_type', 'hysCustomId')->first();
            }
                
            if ($check) {
                return redirect('admin/add_form_field/'.$id.'/'.$type.'')
                    ->with('alert', 'danger')
                    ->with('message', 'You already have an auto-increment field in this form. You cannot have more than one.')
                    ->withInput();
            }
        }
            
        if ($validator->passes()) {
            if ($type == 'donor') {
                $max = DB::table('donorfields')->where('hysform_id', $id)->max('field_order');
                $fields = new Donorfield;
                $keyprefix = 'dn';
                //remove the cache for this hysform
                Cache::forget('donorfieldoptions-'.$id);
                $d = new Donor;
                $d->clearDonorCache($id);
            } else {
                $max = DB::table('fields')->where('hysform_id', $id)->max('field_order');
                $fields = new Field;
                $keyprefix = 'en';
                //remove the cache for this program
                Cache::forget('entityfieldoptions-'.$id);
                $e = new Entity;
                foreach (Program::where('hysform_id', $id)->get() as $p) {
                    $e->clearEntityCache($p->id);
                }
            }
                
            if (!$max) {
                $order = 1;
            } else {
                $order = $max + 1; // add to the end
            }
                
            $key = strtolower($data['field_label']); // make it lowercase
            $key = preg_replace("/[^A-Za-z0-9 ]/", '', $key); // remove any non-alphanumeric characters
            $key = str_replace(" ", "_", $key); // replace spaces with underscore
            $key = ''.$keyprefix.'_'.$key.''; // makes it unique for donors and entities in case fiels have the same name
                
            // if the key is more than 20 characters - truncate
            $count = strlen($key);
            if ($count > 20) {
                $num = $count - 20;
                $key = substr($key, 0, -$num);
            }
                
                        
            $fields->client_id = Session::get('client_id');
            $fields->hysform_id = $id;
            $fields->field_key = $key;
            $fields->field_label = $data['field_label'];
            $fields->field_data = $data['field_data'];
            $fields->field_type = $data['field_type'];
            $fields->permissions = $data['permissions'];
                
            if (isset($data['required'])) {
                $fields->required = $data['required'];
            }
            if (isset($data['is_title'])) {
                $fields->is_title = $data['is_title'];
            }
            if (isset($data['sortable'])) {
                $fields->sortable = $data['sortable'];
            }
            if (isset($data['filter'])) {
                $fields->filter = $data['filter'];
            }

            $fields->field_order = $order;
            $fields->save();
                
            return redirect('admin/manage_form/'.$id.'');
        }
        return redirect('admin/add_form_field/'.$id.'/'.$type.'')
            ->withErrors($validator)
            ->withInput();
    }
        
    public function editFormField($id, $type)
    {
        if ($type == 'donor') {
            $field = Donorfield::find($id);
        } else {
            $field = Field::find($id);
        }
            
        $field_types=$this->getFieldTypes($type);
            
        $hysform= Hysform::find($field->hysform_id);

        $donation= new Donation;
        $gateway= $donation->checkUseCC(Session::get('client_id'));
            
        return view('admin.views.editFormField', [
            'field' => $field,
            'type' => $type,
            'field_types' => $field_types,
            'hysform'=> $hysform,
            'gateway' => $gateway
        ]);
    }
        
    public function postEditFormField($id, $type)
    {
        $data = Input::all();
            
        if ($type == 'donor') {
            $fields = Donorfield::find($id);
            //remove the cache for this hysform
            Cache::forget('donorfieldoptions-'.$fields->hysform_id);
            $d = new Donor;
            $d->clearDonorCache($id);
        } else {
            $fields = Field::find($id);
            //remove the cache for this hysform
            Cache::forget('entityfieldoptions-'.$fields->hysform_id);
            $e = new Entity;
            foreach (Program::where('hysform_id', $id)->get() as $p) {
                $e->clearEntityCache($p->id);
            }
        }
            
        // check to make sure there isn't already an auto increment field for this form
        if ($data['field_type'] == 'hysCustomid') {
            $check = Field::whereHysformId($id)->where('field_type', 'hysCustomId')->first();
            if ($check) {
                return redirect('admin/manage_form/'.$id.'')
                    ->with('alert', 'danger')
                    ->with('message', 'You already have an auto-increment field in this form. You cannot have more than one.')
                    ->withInput();
            }
        }
            
        $fields->field_label = preg_replace("/[^A-Za-z0-9_ ]/", '', $data['field_label']);

        $fields->field_type = $data['field_type'];

        if ($fields->field_type=='hysSelect'||$fields->field_type=='hysTable') {
            $tmp_data= preg_replace("/[^A-Za-z0-9_., ]/", '', $data['field_data']);

            $array= explode(',', $tmp_data);

            if (count($array)>1) {
                $fields->field_data =  implode(',', array_map('trim', explode(',', $tmp_data)));
            } else {
                $fields->field_data = $tmp_data;
            }
        } else {
            $fields->field_data = preg_replace("/[^A-Za-z0-9_., ]/", '', $data['field_data']);
        }
            
        $fields->permissions = $data['permissions'];
            
        if (isset($data['required'])) {
            $fields->required = $data['required'];
        } else {
            $fields->required = 0;
        }
            
        if (isset($data['is_title'])) {
            $fields->is_title = $data['is_title'];
        } else {
            $fields->is_title = 0;
        }
        if ($type!='donor') {
            if (isset($data['sortable'])) {
                $fields->sortable = $data['sortable'];
            } else {
                $fields->sortable = 0;
            }
            if (isset($data['filter'])) {
                $fields->filter = $data['filter'];
            } else {
                $fields->filter = 0;
            }
        }

        $fields->save();

            
            
        return redirect('admin/manage_form/'.$fields->hysform_id.'');
    }
        
    public function deleteFormField($id, $type)
    {
        return view('admin.views.deleteFormField')->with('id', $id);
    }
    public function deleteForm($id)
    {
        return view('admin.views.deleteForm')->with('id', $id);
    }
        
    public function postDeleteFormField($id, $type)
    {
        if ($type == 'donor') {
            $field = Donorfield::find($id);
            //remove the cache for this hysform
            Cache::forget('donorfieldoptions-'.$field->hysform_id);
            $d = new Donor;
            $d->clearDonorCache($id);
        } else {
            $field = Field::find($id);
            //remove the cache for this hysform
            Cache::forget('entityfieldoptions-'.$field->hysform_id);
            $e = new Entity;
            foreach (Program::where('hysform_id', $id)->get() as $p) {
                $e->clearEntityCache($p->id);
            }
        }
        $hysform_id = $field->hysform_id;
        $field->delete();
            
            
            
        return redirect('admin/manage_form/'.$hysform_id.'');
    }
        
    // AJAX function for updating field order
    public function updateFieldsOrder($type)
    {
        $data = Input::all();
            
        foreach ($data['item'] as $key => $value) {
            if ($type == 'donor') {
                $field = Donorfield::find($value);
                    
                //remove the cache for this hysform
                Cache::forget('donorfieldoptions-'.$field->hysform_id);
                $d = new Donor;
                $d->clearDonorCache($field->hysform_id);
            } else {
                $field = Field::find($value);
                //remove the cache for this hysform
                Cache::forget('entityfieldoptions-'.$field->hysform_id);
                $e = new Entity;
                foreach (Program::where('hysform_id', $field->hysform_id)->get() as $p) {
                    $e->clearEntityCache($p->id);
                }
            }
            $field->field_order = $key;
            $field->save();
        }
/*
        foreach($data['item'] as $key => $value) {
            $field = $type::find($value);
				
            $field->field_order = $key;
            $field->save();
        }
*/
    }
}
