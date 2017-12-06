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
use Hash;
use Response;
use Carbon;
use Mail;
use Commitment;
use Donation;
use Donor;
use DonorEntity;
use Program;
use URL;
use Entity;
use Log;
    
use App\Http\Controllers\Controller;

class CommitmentController extends Controller
{
        
    public function editCommitment($commitment_id)
    {
        $commitment = Commitment::find($commitment_id);

        $donor_entity = DonorEntity::find($commitment->donor_entity_id);

        $dntns = new Donation;
        $donor = new Donor;
        $e = new Entity;

        $the_donor = $e->getDonorName($commitment->donor_id);

        $next = $donor->whatHappensNext($commitment);
            
        $programs = [];

        if (count($donor_entity)) {
            // get primary program and sub_programs
            $entity = Entity::withTrashed()->find($donor_entity->entity_id);

            if (count($entity)) {
                $program = Program::find($entity->program_id);
                $other_programs = Program::where('client_id', Session::get('client_id'))->where('link_id', $entity->program_id)->get();

                $programs= [
                    '0' => 'Select a Program',
                    $program->id => $program->name . ' (Parent Program)'];
                
                    
                foreach ($other_programs as $o_p) {
                    $programs[$o_p->id] = $o_p->name . ' (Sub Program)';
                }
            }

            if ($donor_entity->program_id != '0') {
                $a_program=Program::find($donor_entity->program_id);
                    
                if (count($a_program)) {
                    $programs[$donor_entity->program_id]= $a_program->name;
                }
            }
        }

        return view('admin.views.editCommitment', [
            'commitment'    => $commitment,
            'the_donor'     => $the_donor,
            'dntns'         => $dntns,
            'next'          => $next,
            'programs'      => $programs,
            'donor_entity'  => $donor_entity
        ]);
    }


        
    public function postEditCommitment($commitment_id)
    {
        $data = Input::all();
        $donor = new Donor;

        $rules = [
            'next' => 'date'
        ];
                
        $validator = Validator::make($data, $rules);
            
        $commitment = Commitment::find($commitment_id);
        $donor_entity_id = $commitment->donor_entity_id;
            
        if (!$validator->passes()) {
            return redirect('admin/donations_by_donor/'.$commitment->donor_id.'')->withErrors($validator)->withInput();
        }
            
        $commitment->frequency = $data['frequency'];
        $commitment->until = $data['until'];
        $commitment->method = $data['method'];
        $commitment->amount = $data['amount'];

        if (isset($data['arb_subscription_id'])) {
            $commitment->arb_subscription_id=$data['arb_subscription_id'];
        } else {
            $commitment->arb_subscription_id='';
        }

        //This will change the last value according to what the admin inputs for 'next'
        if (!empty($data['next'])) {
            $last= $donor->getLastFromNext($commitment, $data['next']);
            if ($last) {
                $commitment->last = $last;
            }
        }

        $commitment->save();
            
        if (isset($data['program_id'])) {
            $donor_entity = DonorEntity::find($donor_entity_id);
                
            if (count($donor_entity)) {
                $temp_program=Program::find($data['program_id']);
                if ($temp_program!=null) {
                    if ($temp_program->client_id==Session::get('client_id')) {
                        $donor_entity->program_id = $data['program_id'];
                        $donor_entity->save();
                        $e = new Entity;
                        $e->reloadSponsorshipsToCache($donor_entity);
                    }
                }
            }
        }
            
        // Log::info('Commitment id' . $commitment_id . ' updated.');
        return redirect('admin/donations_by_donor/'.$commitment->donor_id.'');
    }

    public function fixCommitment($donor_entity_id)
    {
        $donor_entity=DonorEntity::find($donor_entity_id);

        $commitment=new Commitment;

        if (count($donor_entity)) {
            $p=new Program;

            $type=$p->getProgramTypeFromEntity($donor_entity->entity_id);

            if ($type=='funding') {
                $commitment->funding=1;
            }

            $commitment->client_id=Session::get('client_id');
            $commitment->donor_entity_id=$donor_entity_id;
            $commitment->type=1;
            $commitment->frequency=1;
            $commitment->method=1;
            $commitment->designation=$donor_entity->entity_id;
            $commitment->donor_id=$donor_entity->donor_id;
            $commitment->save();
        }


        $dntns=new Donation;
        $donor= new Donor;

        $next='';
        
        return Redirect::back()->with('message', 'Your broken commitment has been fixed. However, it\'s empty. You must now <a class="btn btn-default btn-xs" data-toggle="modal" href="'. URL::to('admin/edit_commitment', [$commitment->id]) .'" data-target="#modal" title="Edit"><span class="glyphicon glyphicon-pencil"></span> Input the commitment Information</a>.')->with('alert', 'info');
    }
        
    public function removeCommitment($commitment_id)
    {

        $commitment = Commitment::find($commitment_id);
        $commitment->delete();
            
        return redirect('admin/donations_by_donor/'.$commitment->donor_id.'')
        ->with('message', 'Commitment successfully removed.')
        ->with('alert', 'success');
    }
}
