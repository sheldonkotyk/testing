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
use FormArchive;
use User;
use Mail;
use Notification;
    
use App\Http\Controllers\Controller;

class FormArchiveController extends Controller
{

    public function submitForm($type, $id, $program_id, $form_id)
    {
        $hysform = Hysform::find($form_id);
        $fields = Field::whereHysformId($form_id)->orderBy('field_order')->get();
        $program = Program::find($program_id);
        if ($type == 'entity') {
            $pfs = Field::whereHysformId($program->hysform_id)->orderBy('field_order')->get();
            $the_entity = Entity::withTrashed()->find($id);
            $entity = json_decode($the_entity->json_fields, true);
        } elseif ($type == 'donor') {
            $pfs = Donorfield::whereHysformId($program->donor_hysform_id)->orderBy('field_order')->get();
            $the_donor = Donor::withTrashed()->find($id);
            $entity = json_decode($the_donor->json_fields, true);
        }
            
        // retrieve only name and id fields
        foreach ($pfs as $pf) {
            if ($pf->is_title == 1 || $pf->field_type == 'hysCustomid') {
                $profile_fields[] = $pf;
            }
        }

        return view('admin.views.submitForm', [
            'fields' => $fields,
            'profile_fields' => $profile_fields,
            'entity' => $entity,
            'id' => $id,
            'program_id' => $program_id,
            'hysform' => $hysform
        ]);
    }
                
    public function postSubmitForm($type, $id, $program_id, $form_id)
    {
        $data = Input::all();
        unset($data['_token']);
        $form = new FormArchive;
        $form->client_id = Session::get('client_id');
        $form->program_id = $program_id;
        $form->hysform_id = $form_id;
        if ($type == 'entity') {
            $form->entity_id = $id;
        } elseif ($type == 'donor') {
            $form->donor_id = $id;
        }
        $user = Sentry::getUser();
        $form->admin_id = $user->id;
        $form->form_info = json_encode($data);
        $form->save();
            
        $formdata = $this->getArchivedForm($form->id);
            
        // admin group ids
        $notification = Notification::where('program_id', $program_id)->where('item_id', $form_id)->first();
            
        if (!empty($notification->groups)) {
            $notify_ids = json_decode($notification->groups, true);
                
            // admins who belong to those groups
            $admins = User::whereIn('group_id', $notify_ids)->select('first_name', 'last_name', 'email')->get();
            foreach ($admins as $admin) {
                $name = ''.$admin->first_name.' '.$admin->last_name.'';
                $subject = ''.$formdata['hysform']->name.' submitted by '.$formdata['admin']->first_name.' '.$formdata['admin']->last_name.'';
    
                Mail::queue('emails.emailArchivedForm', $formdata, function ($message) use ($admin, $formdata, $name, $subject) {
                    $message->to($admin->email, $name)->subject($subject);
                });
                unset($name);
                unset($subject);
            }
        }
            
        if ($type == 'entity') {
            return redirect('admin/list_archived_forms/entity/'.$id.'/'.$form_id.'');
        } elseif ($type == 'donor') {
            return redirect('admin/list_archived_forms/donor/'.$id.'/'.$form_id.'');
        }
    }
        
    public function getArchivedForm($archived_form_id)
    {
        $form = FormArchive::find($archived_form_id);
        $form_data = json_decode($form->form_info, true);
        $fields = Field::whereHysformId($form->hysform_id)->get();
            
        foreach ($fields as $f) {
            $field_keys[] = $f->field_key;
        }
            
        foreach ($form_data as $key => $value) {
            if (!in_array($key, $field_keys)) {
                $field_label = '';
                $field = Field::where('field_key', $key)->where('client_id', Session::get('client_id'))->first();
                if ($field) {
                    $field_label = $field->field_label;
                }
            } else {
                foreach ($fields as $f) {
                    if ($f->field_key == $key) {
                        $field_label = $f->field_label;
                            
                        if ($f->field_type == 'hysTable') {
                            $items = explode(',', $f->field_data);
                            $out = '<div class="form-group">';
                                
                            $out .= '<table class="table table-condensed"><thead><tr>';
                        
                            foreach ($items as $item) {
                                $out .= '<th>'.$item.'</th>';
                            }
                                
                            $out .= '</tr></thead><tbody>';
                            $count = count($items);
                                
                            $table_data = $value;
                            if (!is_array($table_data)) {
                                $table_data = json_decode($value, true);
                            }
                            $i = 0;
            
                            if (is_array($table_data)) {
                                foreach ($table_data as $td) {
                                    $i++;
                                    if ($i == 1) {
                                        $out .= '<tr class="'.$f->field_key.'">';
                                    }
                                        
                                    $out .= '<td>'.$td.'</td>';
                                        
                                    if ($i == $count) {
                                        $i = 0;
                                        $out .= '</tr>';
                                    }
                                }
                            }
                                
                            $out .= '</tbody></table></div>';
                            $value = $out;
                        }
                    }
                }
            }
                
            if (!empty($field_label)) {
                $form_info[] = ['field_key' => $key, 'field_label' => $field_label, 'data' => $value];
            }
        }
            
        $admin = Sentry::findUserById($form->admin_id);
        $hysform = Hysform::find($form->hysform_id);
        $type = 'entity';
        $id = $form->entity_id;
        if ($form->donor_id > 0) {
            $type = 'donor';
            $id = $form->donor_id;
        }
        return $formdata = [
            'admin' => $admin,
            'form' => $form,
            'form_info' => $form_info,
            'hysform' => $hysform,
            'fields' => $fields,
            'type' => $type,
            'id' => $id
        ];
    }
        
    public function viewArchivedForm($archived_form_id)
    {
        $formdata = $this->getArchivedForm($archived_form_id);
        return view('admin.views.viewArchivedForm', $formdata);
    }
        
    public function editArchivedForm($archived_form_id)
    {
        $formdata = $this->getArchivedForm($archived_form_id);
        return view('admin.views.editSubmitForm', $formdata);
    }
        
    public function postEditArchivedForm($archived_form_id)
    {
        $data = Input::all();
        unset($data['_token']);
            
        $form = FormArchive::find($archived_form_id);
        $form->form_info = json_encode($data);
        $form->save();
                        
        return redirect('admin/view_archived_form/'.$archived_form_id.'');
    }
        
    public function listArchivedForms($type, $id, $hysform_id)
    {
        if ($type == 'entity') {
            $type_id = 'entity_id';
            $donor = new Donor;
            $name = $donor->getEntityName($id);
            $entity = Entity::withTrashed()->where('id', $id)->first();
            $program_id = $entity->program_id;
        } elseif ($type == 'donor') {
            $type_id = 'donor_id';
            $entity = new Entity;
            $name = $entity->getDonorName($id);
            $program_id = 'donor';
        }
            
        $forms = FormArchive::whereClientId(Session::get('client_id'))->whereHysformId($hysform_id)->where($type_id, $id)->select('id', 'created_at')->get();
        $hysform = Hysform::find($hysform_id);
            
        // submit only forms
        $program = Program::find($program_id);
        $submit_ids = explode(',', $program->entity_submit);
        $submit = Hysform::whereClientId(Session::get('client_id'))->whereIn('id', $submit_ids)->get();
            
        return view('admin.views.listArchivedForms', [
            'forms' => $forms,
            'hysform' => $hysform,
            'name' => $name['name'],
            'profile' => ['id'=>$id,'program_id'=>$program->id],
            'type' => $type,
            'program_id' => $program_id,
            'program' => $program,
            'submit' => $submit,
            'entity' => ($type=='entity' ? Entity::find($id) : [])
        ]);
    }
        
    public function archivedReport()
    {
        $forms = Hysform::whereClientId(Session::get('client_id'))->where('type', 'submit')->get();
        $programs = Program::whereClientId(Session::get('client_id'))->get();
            
        return view('admin.views.archivedReport', [
            'hysforms' => $forms,
            'programs' => $programs
        ]);
    }
        
    public function postArchivedReport()
    {
        $data = Input::all();
        $rules = [
            'date_from' => 'date_format:Y-m-d|required',
            'date_to' => 'date_format:Y-m-d|required'
        ];
        $validator = Validator:: make($data, $rules);
            
        if ($validator->passes()) {
            $pfs = [];
                
            $fields = Field::whereHysformId($data['hysform_id'])->get();
            $archived_forms = FormArchive::whereClientId(Session::get('client_id'))
                ->where('hysform_id', $data['hysform_id'])
                ->where('program_id', $data['program_id'])
                ->whereBetween('created_at', [$data['date_from'], $data['date_to']])
                ->lists('id');
                
            $forms = [];
            foreach ($archived_forms as $af) {
                $forms[] = $this->getArchivedForm($af);
            }
                
            $program = Program::find($data['program_id']);
                
            if ($data['type'] == 'entity') {
                if (!empty($program->hysform_id)) {
                    $pfs = Field::whereHysformId($program->hysform_id)->orderBy('field_order')->get();
                }
            } elseif ($data['type'] == 'donor') {
                $pfs = DonorField::whereHysformId($program->donor_hysform_id)->orderBy('field_order')->get();
                if (!empty($program->donor_hysform_id)) {
                    $pfs = Field::whereHysformId($program->donor_hysform_id)->orderBy('field_order')->get();
                }
            }
                
            // retrieve only name and id fields
            foreach ($pfs as $pf) {
                if ($pf->is_title == 1 || $pf->field_type == 'hysCustomid') {
                    $profile_fields[] = $pf;
                }
            }
                
            foreach ($fields as $field) {
                $profile_fields[] = $field;
            }
            return view('admin.views.viewFormArchiveReport', [
                'fields' => $fields,
                'forms' => $forms,
                'profile_fields' => $profile_fields,
                'date_from' => $data['date_from'],
                'date_to' => $data['date_to']
            ]);
        }
    }
}
