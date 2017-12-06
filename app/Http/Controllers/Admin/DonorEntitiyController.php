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
    
use App\Http\Controllers\Controller;

class DonorEntityController extends Controller
{
    
    public function allSponsorships()
    {
        $e = new Entity;
        $d = new Donor;
        $redis = RedisL4::connection();
        $allSponsorships = DonorEntity::whereClientId(Session::get('client_id'))->get();
            
        // need to pipeline this
        foreach ($allSponsorships as $sponsorship) {
            $data[] = [
                'id' => $sponsorship->id,
                'entity' => $d->getEntityName($sponsorship->entity_id),
                'donor' => $e->getDonorName($sponsorship->donor_id),
                'created_at' => $sponsorship->created_at,
                'updated_at' => $sponsorship->updated_at
            ];
        }
            
        return view('admin.views.allSponsorships')->with('sponsorships', $data);
    }

    //This function checks the DonorEntity Table for errors and outputs them.
    public function checkDonorEntityErrors($fix = false)
    {

        $no_program_errors = [];
        $program_and_client_do_not_match_errors = [];
        $error_dates=[];
        $fixed_client_errors=0;
        foreach (DonorEntity::all() as $de) {
            if ($de->program_id!=0) {
                $p= Program::find($de->program_id);
                if ($p==null) {
                    $error_dates['no_program'][]=$de->created_at->toFormattedDateString();
                    if (!isset($no_program_errors[$de->client_id])) {
                        $no_program_errors[$de->client_id]=1;
                    } else {
                        $no_program_errors[$de->client_id]++;
                    }
                } else {
                    if ($p->client_id!=$de->client_id) {
                        if ($fix) {
                            $e=Entity::find($de->entity_id);
                            if ($e!=null) {
                                $fixed_client_errors++;
                                $de->program_id=$e->program_id;
                                $de->save();
                            }
                        }
                        $error_dates['client_mismatch'][]=$de->created_at->toFormattedDateString();
                        if (!isset($program_and_client_do_not_match_errors[$de->client_id])) {
                            $program_and_client_do_not_match_errors[$de->client_id]=1;
                        } else {
                            $program_and_client_do_not_match_errors[$de->client_id]++;
                        }
                    }
                }
            }
        }
        return var_dump(['programs_missing'=>$no_program_errors, 'client_mismatch'=>$program_and_client_do_not_match_errors,'error_dates'=>$error_dates,'errors_fixed' => $fixed_client_errors]);
    }
}
