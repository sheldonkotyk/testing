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
use Session;
use DB;
use Donor;
use Donorfield;
use Upload;
use Hash;
use Entity;
use Field;
use Program;
use Client;
use Response;
use DonorEntity;
use Hysform;
use Carbon;
use Mail;
use Emailtemplate;
use Emailset;
use Designation;
    
use App\Http\Controllers\Controller;

class DesignationController extends Controller
{
        
    public function viewAllDesignations()
    {
        $designations = Designation::whereClientId(Session::get('client_id'))->get();
            
        return view('admin.views.viewAllDesignations', ['designations' => $designations]);
    }
        
    public function addDesignation()
    {

        $hysforms = [];
        foreach (Hysform::whereClientId(Session::get('client_id'))->where('type', 'donor')->get() as $hysform) {
            $hysforms[$hysform->id] = $hysform->name;
        }

        $emailsets = Emailset::whereClientId(Session::get('client_id'))->get();
            
        return view('admin.views.addDesignation', [
            'hysforms' => $hysforms,
            'emailsets' => $emailsets
            ]);
    }
        
    public function postAddDesignation()
    {
        $data = Input::all();
        $rules = [
            'name' => 'required'
        ];
           
        $validator = Validator::make($data, $rules);
            
        if ($validator->passes()) {
            $designation = new Designation;
            $designation->client_id = Session::get('client_id');
            if (isset($data['hysform'])) {
                $designation->hysforms = json_encode($data['hysform']);
            }
            $designation->emailset_id = $data['emailset_id'];
            $designation->code = $data['code'];
            $designation->name = $data['name'];
            $designation->save();
                
            return redirect('admin/all_designations');
        }
            
        return redirect('admin/add_designation')
            ->withErrors($validator)
            ->withInput();
    }
        
    public function editDesignation($designation_id)
    {

        $designation = Designation::where('client_id', Session::get('client_id'))->find($designation_id);
        if (count($designation)==0) {
            return "Error: Designation could not be found.";
        }

        if (!empty($designation->hysforms)) {
            $used_forms = json_decode($designation->hysforms, true);
        } else {
            $used_forms = [];
        }

        if (count($used_forms)>1) {
            $used_form= reset($used_forms);
        } else {
            $used_form = $used_forms;
        }

        $hysforms = [];
        foreach (Hysform::whereClientId(Session::get('client_id'))->where('type', 'donor')->get() as $hysform) {
            $hysforms[$hysform->id] = $hysform->name;
        }

        $designation->hysforms= json_decode($designation->hysforms);
        $designation->save();

        $emailsets = Emailset::whereClientId(Session::get('client_id'))->get();
        return view('admin.views.editDesignation', [
            'designation' => $designation,
            'used_form' => $used_form,
            'hysforms' => $hysforms,
            'emailsets' => $emailsets
        ]);
    }
        
    public function postEditDesignation($designation_id)
    {
        $data = Input::all();
        $rules = [
            'name' => 'required',
            'donation_amounts'=> ['integers','no_blanks']
        ];
           
        $validator = Validator::make($data, $rules);
            
        if ($validator->passes()) {
            $designation = Designation::find($designation_id);
            $designation->emailset_id = $data['emailset_id'];
            if (isset($data['hysform'])) {
                $designation->hysforms = $data['hysform'];
            } else {
                $designation->hysforms = '';
            }
            $designation->info = $data['info'];
            $designation->donation_amounts= $data['donation_amounts'];
            $designation->code = $data['code'];
            $designation->name = $data['name'];
            $designation->save();
                
            return redirect('admin/all_designations');
        }
            
        return redirect('admin/edit_designation/'.$designation_id.'')
            ->withErrors($validator)
            ->withInput();
    }
        
    public function removeDesignation($designation_id)
    {
        $designation = Designation::find($designation_id);
        $designation->delete();
            
        return redirect('admin/all_designations');
    }
}
