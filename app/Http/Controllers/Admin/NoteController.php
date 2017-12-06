<?php  namespace App\Controllers\Admin;
 
    use Auth;
use BaseController;
use Form;
use Input;
use Redirect;
use Sentry;
use View;
use Validator;
use Session;
use DB;
use Category;
use Note;
use Response;
use Hysform;
use Program;
use Donor;
use RedisL4;
use Entity;
use Upload;
use Emailset;
use Emailtemplate;
    
use App\Http\Controllers\Controller;

class NoteController extends Controller
{
        
    /**
        *
        *  @param $id = entity_id
        *  @param $type = entity or donor
        *  @param $program_id = program_id or donor
        *  @param $cat = category_id for filtering notes
        *
        **/
        
    private function getCategories($type, $program_id)
    {
        if ($type == 'entity') {
            $categories = Category::where('program_id', $program_id)->get();
        } elseif ($type == 'donor') {
            $categories = Category::where('program_id', 'donor')->get();
        }
            
        return $categories;
    }
        
    // check to see if a category exists
    // if it doesn't we create it
    // then return the id of the category
    private function catExist($type, $program_id, $data)
    {
        $exist = false;
        $categories = $this->getCategories($type, $program_id);
        foreach ($categories as $category) {
            if ($category->category == $data) {
                $exist = true;
                $category_id = $category->id;
                break;
            }
        }
        // if it doesn't exist, create it
        if ($exist == false) {
            $category = new Category;
            $category->category = $data;
            if ($type == 'entity') {
                $category->program_id = $program_id;
            } elseif ($type == 'donor') {
                $category->program_id = 'donor';
            }
            $category->save();
            $category_id = $category->id;
        }
            
        return $category_id;
    }
                
    public function viewNotes($id, $type, $program_id, $cat = null)
    {
        $categories = $this->getCategories($type, $program_id);
        $redis = RedisL4::connection();
        $emailsets = false;
        $template_errors=[];
        if ($type == 'entity') {
            $entity = Entity::where('client_id', Session::get('client_id'))->withTrashed()->find($id);
            if (count($entity)==0) {
                return "Error: Entity Not Found.";
            }
                
            $donor = new Donor;
            $entity = $donor->getEntityName($id);
            $name = $entity['name'];
            $the_donor='';
        } elseif ($type == 'donor') {
            $donor = Donor::where('client_id', Session::get('client_id'))->withTrashed()->find($id);
            if (count($donor)==0) {
                return "Error: Donor Not Found.";
            }
                
            $e = new Entity;
            $donor = $e->getDonorName($id);
            $the_donor= Donor::withTrashed()->find($id)->first();
            $name = $donor['name'];
        }
            
        if ($cat != null) {
            $notes = Note::where(''.$type.'_id', $id)->where('category_id', $cat)->orderBy('created_at', 'desc')->get();
        } else {
            $notes = Note::where(''.$type.'_id', $id)->orderBy('created_at', 'desc')->get();
        }
            
        // submit only forms
        $program = Program::find($program_id);
        $submit_ids = [];
        $submit = [];
        if (!empty($program->entity_submit)) {
            $submit_ids = explode(',', $program->entity_submit);
            $submit = Hysform::whereClientId(Session::get('client_id'))->whereIn('id', $submit_ids)->get();
        }

        //get The thumbnail for the entity
        $upload = new Upload;
        if ($type=='entity') {
            $entity= Entity::withTrashed()->find($id);
            $hysform='';
            $donor= null;
            $uploads = Entity::withTrashed()->find($id)->uploads()->where('profile', 1)->first();
            $years = [];
        }
        if ($type=='donor') {
            $entity= '';
            $donor= Donor::withTrashed()->find($id);
            $hysform=Hysform::find($donor->hysform_id);
            $uploads = Donor::withTrashed()->find($id)->uploads()->where('profile', 1)->first();
            $years= $donor->getYears($donor);
            $emailset = new Emailset;
            $emailsets = $emailset->getEmailSets($donor->hysform_id);
            if (!empty($emailsets['default_emailset'])) {
                $t = new Emailtemplate;
                $e_s= Emailset::where('id', $emailsets['default_emailset']['id'])->get();
                $template_errors = $t->templateErrors($e_s);
            }
        }
        $profileThumb = '';
        if (!empty($uploads)) {
            $profileThumb = $uploads->makeAWSlinkThumb($uploads);
        }
            
        return view('admin.views.viewNotes', [
            'id' => $id,
            'name' => $name,
            'categories' => $categories,
            'notes' => $notes,
            'cat' => $cat,
            'program' => $program,
            'hysform'=> $hysform,
            'program_id' => $program_id,
            'type' => $type,
            'submit' => $submit,
            'donor' => $donor,
            'profileThumb' => $profileThumb,
            'entity'    => $entity,
            'emailsets'=>$emailsets,
            'years' => $years,
            'template_errors' => $template_errors
            ]);
    }
        
    public function listCat($type, $program_id)
    {
        $categories = $this->getCategories($type, $program_id);
                
        $cat = [];
        foreach ($categories as $category) {
            $cat[] = $category->category;
        }
            
        return Response::json($cat);
    }
                
    public function postNewNote($type, $program_id)
    {
        $data = Input::all();
        $rules = [
            'note' => 'required|min:3'
        ];
           
        $validator = Validator::make($data, $rules);
            
        if ($validator->passes()) {
            // get the category info
            if (empty($data['categories'])) {
                $data['categories'] = 'uncategorized';
            }
                
            $category_id = $this->catExist($type, $program_id, $data['categories']);
                                    
            // get the current user
            $user = Sentry::getUser();
                
            $note = new Note;
            $type_id = ''.$type.'_id';
            $note->$type_id = $data['entity_id'];
            $note->user_id = $user->id;
            $note->category_id = $category_id;
            $note->note = $data['note'];
            $note->save();
                
            return redirect('admin/notes/'.$data['entity_id'].'/'.$type.'/'.$program_id.'');
        }
            
            
        return redirect('admin/notes/'.$data['entity_id'].'/'.$type.'/'.$program_id.'')
            ->withErrors($validator)
            ->withInput();
    }
        
    public function editNote($note_id, $program_id)
    {
        $note = Note::find($note_id);
            
        if (!empty($note->entity_id)) {
            $type = 'entity';
        } else {
            $type = 'donor';
        }
            
        return view('admin.views.editNote')
            ->with('note', $note)
            ->with('program_id', $program_id)
            ->with('type', $type);
    }
        
    public function postEditNote($note_id, $program_id)
    {
        $data = Input::all();
            
        $rules = [
            'note' => 'required|min:3'
        ];
           
        $validator = Validator::make($data, $rules);
            
        if ($validator->passes()) {
            $note = Note::find($note_id);

            if (!empty($note->entity_id)) {
                $type = 'entity';
                $id = $note->entity_id;
            } else {
                $type = 'donor';
                $id = $note->donor_id;
            }

            // get the category info
            if (!empty($data['categories'])) {
                $category_id = $this->catExist($type, $program_id, $data['categories']);

                $note->category_id = $category_id;
            }
                
            // get the current user
            $user = Sentry::getUser();
                
            $note->user_id = $user->id;
            $note->note = $data['note'];
            $note->save();
                
            return redirect('admin/notes/'.$id.'/'.$type.'/'.$program_id.'');
        }
                    
        return redirect('admin/edit_note/'.$note_id.'/'.$program_id.'')
            ->withErrors($validator)
            ->withInput();
    }
}
