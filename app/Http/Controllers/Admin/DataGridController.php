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
use Environment;
use StdClass;
use User;
use Datatables;

use App\Http\Controllers\Controller;

class DataGridController extends Controller
{

    public function DisplayTable($program_id)
    {
    
        $entities = Entity::allEntitiesByProgram($program_id)->get();
        $fields = Field::allFieldsByProgram($program_id)->orderBy('field_order')->get();

        $hashes=[];
        foreach ($entities as $entity) {
            $hashes[$entity->id] = "id:{$entity->id}";
        }
            
            $redis = RedisL4::connection();
            $profiles=[];
        foreach ($hashes as $k => $hash) {
            $profiles[$k] = $redis->hgetall($hash);
        }
            
            // process the profiles for any special field types
            $processed=[];

        foreach ($profiles as $k => $profile) {
            $profile['hysmanage']=$k;
                
            foreach ($fields as $field) {
                // format links
                if (!isset($profile[$field['field_key']])) {
                    $profile[$field['field_key']]='';
                }

                if ($field['field_type'] == 'hysLink') {
                    if (isset($profile[$field['field_key']])) {
                        if ($profile[$field['field_key']]!='') {
                            $link = explode('|', $profile[$field['field_key']]);
                            if (isset($link[1])) {
                                $profile[$field['field_key']] = '<a href="'.$link[1].'">'.$link[0].'</a>';
                            }
                        }
                    }
                }
                    
                if ($field['field_type'] == 'hysStatic') {
                    $profile[$field['field_key']] = $field['field_data'];
                }
            }
            $processed[] = $profile;
        }

        return view('admin.views.displayDataTable')
                    ->with('processed', $processed)
                    ->with('program_id', $program_id)
                    ->with('fields', $fields);
    }
}
