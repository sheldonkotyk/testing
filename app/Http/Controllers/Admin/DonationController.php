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
use Donation;
use Designation;
use Commitment;
use Setting;
use URL;
use Log;
use Emailset;
    
use App\Http\Controllers\Controller;

class DonationController extends Controller
{
        
    public function donationsOptions()
    {
        $hysforms = Hysform::whereClientId(Session::get('client_id'))->whereType('donor')->get();
            
        return view('admin.views.donationOptions')->with([
            'hysforms'=> $hysforms,
            'hysform'=> ['id' => '', 'name' => ''],
            'fields' => '',
            'all'=> '']);
    }
  
    public function formFields()
    {
        $data = Input::all();
        $field = new Field;
        $fields = $field->getFields($data['Donor_Group']);
            
        return view('admin.views.formFields')->with('fields', $fields);
    }
        
    // data posted from donationsOptions
    public function viewAllDonations($all = false)
    {
        $data = Input::all();

        if (empty($data['date_from'])) {
            $data['date_from'] = Carbon::now()->format('Y-m-d');
        }
            
        if (empty($data['date_to'])) {
            $data['date_to'] = Carbon::now()->format('Y-m-d');
        }
 
        if (isset($data['all'])) {
            $all = '1';
        }

        if ($all) {
            $data['Donor_Group'] = 'all';
        }

        $rules = [
            'Donor_Group'=> 'required',
            'date_from' => 'date_format:Y-m-d|required',
            'date_to' => 'date_format:Y-m-d|required'
        ];
            
        $validator = Validator:: make($data, $rules);
            
        $donationReport = [];
            
        if ($validator->passes()) {
            $d = new Donation;

            $donations = Donation::whereClientId(Session::get('client_id'))->whereBetween('created_at', [$data['date_from'], $data['date_to']])->get();
                
            $number_of_donations = $donations->count();
            $total_of_donations = number_format($donations->sum('amount'), 0);
            $donor = new Donor;
            $entity = new Entity;

            // build array for outputing to table
            foreach ($donations as $donation) {
                // get the sponsor info
                $profile = $donor->oneDonor($donation->donor_id);
                    
                // designation
                $designation = $d->getDesignation($donation->type, $donation->designation);


                // method
                $method = $d->getMethod($donation->method);
                    
                if ($all) {
                    $donor_name = $entity->getDonorName($donation->donor_id);
                        
                    $donationReport[] = [
                        'profile' => $profile,
                        'donor'=> $donor_name,
                        'designation' => $designation,
                        'method' => $method,
                        'amount' => number_format($donation->amount, 2, '.', ''),
                        'date' => $donation->created_at
                    ];
                }
                    
                // only include donations from the selected form
                if (isset($profile['hysform_id']) && $profile['hysform_id'] == $data['Donor_Group']) {
                    $donationReport[] = [
                        'profile' => $profile,
                        'designation' => $designation,
                        'method' => $method,
                        'amount' => number_format($donation->amount, 2, '.', ''),
                        'date' => $donation->created_at
                    ];
                }
            }

            $outFields = [];
                
            if (!$all) {
                $field = new Field;
                $fields = $field->getFields($data['Donor_Group']);

                if (isset($data['fields'])) {
                    if (!is_array($data['fields'])) {
                        $data['fields']=json_decode($data['fields']);
                    }
                        
                    foreach ($data['fields'] as $df) {
                        foreach ($fields as $field) {
                            if ($df == $field->field_key) {
                                $outFields[] = ['field_key' => $field->field_key, 'field_label' => $field->field_label];
                            }
                        }
                    }
                }
            }

            $client = new Client;

            $donation_graph_data = $client->getDonationGraphDates(Session::get('client_id'), $data['date_from'], $data['date_to'], 1);
                
            $hysform = ['id' => 'all', 'name' => 'All Donations'];
                
            if ($data['Donor_Group'] != 'all') {
                $hysform = Hysform::find($data['Donor_Group'])->toArray();
            }

                
            return view('admin.views.viewAllDonations', [
                'donations' => $donationReport,
                'number_of_donations'=> $number_of_donations,
                'total_of_donations' => $total_of_donations,
                'donation_graph_data' => $donation_graph_data,
                'fields' => $outFields,
                'date_from' => $data['date_from'],
                'date_to' => $data['date_to'],
                'all'   => $all,
                'hysform' => $hysform
            ]);
        }
            
        return redirect('admin/donations')
            ->withErrors($validator)
            ->withInput();
    }
        
        
    public function addDonation($donor_id)
    {
        $data = Input::all();

        $rules = [
            'designation' => 'required',
            'amount'    => 'required|numeric|min:1'
            ];

         $pre_validator = Validator::make($data, $rules);

        if ($pre_validator->fails()) {
            return redirect('admin/donations_by_donor/'.$donor_id.'')
                    ->withErrors($pre_validator)
                    ->with('message', 'There was a problem with your submission.')
                    ->with('alert', 'danger');
        }
                
        //Default charge and notify donor
        $notify = true;
        $charge = true;

        if (isset($data['dont_notify']) && $data['dont_notify'] == '1') {
            $notify = false;
        }

        if (isset($data['dont_charge']) && $data['dont_charge'] == '1') {
            $charge = false;
        }

        $arb_subscription_id = '';

        if (isset($data['arb_subscription_id'])) {
            $arb_subscription_id = $data['arb_subscription_id'];
        }

        $donation = new Donation;
        $alert = 'success'; // use this as a flag in case we run into problems
        $message = '';
        $designation = explode('-', $data['designation']);

        if ($designation[0] == 'entity') {
            $donationType = 1;
        }
            
        if ($designation[0] == 'desig') {
            $donationType = 2;
        }
        if ($designation[0] == 'funding') {
            $donationType = 1;
        }

        $d = $donation->getDesignation($donationType, $designation[1]);
            
        // create stripe customer
        if ($data['method'] == '3' && isset($data['number'])) {
            $donor = Donor::find($donor_id);
                
            // validate data
            $rules = [
                'firstName' => 'required',
                'lastName' => 'required',
                'number' => 'required|creditcard',
                'cvv' => 'required',
                'expiryMonth' => 'required',
                'expiryYear' => 'required'
            ];
                
            $validator = Validator::make($data, $rules);
            
            if ($validator->fails()) {
                return redirect('admin/donations_by_donor/'.$donor_id.'')
                ->withErrors($validator)
                ->with('message', 'There was a problem with your submission.')
                ->with('alert', 'danger');
            }
                
            try {
                $card = [
                'firstName' => $data['firstName'],
                'lastName' => $data['lastName'],
                'email' => $donor->email,
                'number' => $data['number'],
                'cvv' => $data['cvv'],
                'expiryMonth' => $data['expiryMonth'],
                'expiryYear' => $data['expiryYear']
                ];
                    
            //$flname = $data['firstName'].' '.$data['lastName'];
            //$params = array('description' => $flname);
                $params['donor_id']=$donor_id;

                $response = $donation->createCustomer($card, $params);
                    
                if (!$response->success) {
            // payment failed: display message to customer
                    $message = $response->result;
                    $alert = 'danger';
                }
            } catch (\Exception $e) {
                $message = $e->getMessage();
                $alert = 'danger';
            }
        }
            
        if ($charge) {
            // create charge
            if ($data['method'] == '3') {
                if ($donation->isDonorCardActive($donor_id) && $data['amount'] > 1) {
                    try {
                        $params = [
                            'amount' => $data['amount'],
                            'currency' => 'usd',
                            'donor_id'  => $donor_id,
                            'description' => $d['name']
                        ];
                        $response = $donation->createCharge($params);
                            
                        if ($response->success) {
                            $message = 'Donation Successful';
                            $alert = 'success';
                            $result = $response->result;
                        } else {
                            $message = $response->result;
                            $alert = 'danger';
                        }
                    } catch (\Exception $e) {
                        $message = $e->getMessage();
                        $alert = 'danger';
                    }
                }
            }
        } else {
            $message = 'Credit Card was not charged.';
            $result = 'Do Not Charge was selected.';
        }
            
        if ($alert == 'success') {
            $donation->client_id = Session::get('client_id');
            $donation->donor_id = $donor_id;
            $donation->type = $donationType;
            $donation->amount = $data['amount'];
            $donation->designation = $designation[1];
            $donation->method = $data['method'];
            if (isset($result)) {
                $result = ''.$data['result'].' (Transaction Reference = '.$result.')';
            } else {
                $result = $data['result'];
            }
            $donation->result = $result;
            if (!empty($data['created_at'])) {
                $donation->created_at = new Carbon($data['created_at']);
            }

            //If the frequency is unset or it's set to 0, then we set the one_time field for client to pay us later!
            if (!isset($data['frequency'])||(isset($data['frequency'])&&$data['frequency']==0)) {
                $donation->one_time=1;
            }
                
            if (empty($arb_subscription_id)) {
                $donation->save();
            }

            // set a recurring donation
            if (isset($data['frequency']) && $data['frequency'] != 0) {
                $commitment = new Commitment;
                $commitment->client_id = Session::get('client_id');
                $commitment->donor_id = $donor_id;
                $commitment->type = 2;
                $commitment->frequency = $data['frequency'];
                    
                //This is the subscription ID from ARB
                if (!empty($arb_subscription_id)) {
                    $commitment->arb_subscription_id=$arb_subscription_id;
                }

                $commitment->until = $data['until'];
                $commitment->amount = $data['amount'];
                $commitment->designation = $designation[1];
                $commitment->method = $data['method'];
                if (!empty($data['created_at'])) {
                    $commitment->last = new Carbon($data['created_at']);
                } else {
                    $commitment->last = Carbon::now();
                }
                $commitment->save();
            }
                
            $donor= Donor::find($donor_id);
            //Reload the Cache entry for this donor!
            $donor->reloadDonorsToCache($donor);
                
            // send email receipt if donation is positive number
            if ($data['amount'] > 0 && empty($arb_subscription_id)) {
                $email = new Emailtemplate;
                $details['donor'] = $email->getDonor($donor_id);
                $method = $donation->getMethod($data['method']);
                    
                if (isset($data['frequency'])) {
                    $frequency = $donation->getFrequency($data['frequency']);
                } else {
                    $frequency = 'One-time';
                }
                    
                $designations = $d['name'].' @ '.$data['amount'].' ('.$frequency.')';

                $details['donation'] = ['designations' => $designations, 'total_amount' => $data['amount'], 'method' => $method, 'date' => $donation->created_at->toFormattedDateString()];
                    
                $entity = new Entity;
                $donor = $entity->getDonorName($donor_id);
                $to = ['type' => 'donor', 'name' => $donor['name'], 'email' => $donor['email'], 'id' => $donor_id];
                    
                if ($notify) {
                    $emailSent = $email->sendEmail($d['emailset_id'], $details, 'pay_receipt', $to);
                } else {
                    $emailSent= false;
                }

                if ($emailSent == true) {
                    $message .= "<p>Donation receipt emailed to donor.</p>";
                } else {
                    $message .= "<p>Donation receipt not emailed to donor.</p>";
                }
            }
        }
                        
        return redirect('admin/donations_by_donor/'.$donor_id.'')
            ->with('message', $message)
            ->with('alert', $alert);
    }
        
    public function editDonation($donation_id)
    {
        $donation = Donation::find($donation_id);
        return view('admin.views.editDonation')->with('donation', $donation);
    }
        
    public function postEditDonation($donation_id)
    {
        $data = Input::all();
        $donation = Donation::find($donation_id);
        $donation->created_at = new Carbon($data['created_at']);
        $donation->amount = $data['amount'];
        $donation->result = $data['result'];
        $donation->save();

        $donor= Donor::find($donation->donor_id);
        if ($donation->type=='1') {
            $donor->setStatus($donation->designation);
        }
        //Reload the Cache entry for this donor!
        $donor->reloadDonorsToCache($donor);
            
        return redirect('admin/donations_by_donor/'.$donation->donor_id.'');
    }
        
    public function removeDonation($donation_id)
    {
        $donation = Donation::find($donation_id);
        $donor_id = $donation->donor_id;
        $type = $donation->type;
        $donation->delete();



        $donor= Donor::find($donor_id);
        if ($type=='1') {
            $donor->setStatus($donation->designation);
        }
        //Reload the Cache entry for this donor!
        $donor->reloadDonorsToCache($donor);
            
        return redirect('admin/donations_by_donor/'.$donor_id.'')
            ->with('message', 'Donation successfully deleted.')
            ->with('alert', 'success');
    }
        
    public function donationsByDonor($donor_id)
    {

        $donor = Donor::where('client_id', Session::get('client_id'))->withTrashed()->find($donor_id);
        if (count($donor)==0) {
            return "Error: Donor Not Found.";
        }

        $dntns = new Donation;
        $donations = Donation::whereDonorId($donor_id)->orderBy('created_at', 'desc')->get();
            
        $out = $dntns->getDonationsTable($donations);

        $d = new Donor;
        $commitments = $d->getCommitments($donor_id);
        $sponsorships = $d->getSponsorships($donor_id); // array(id (entity), name, program_id, created, donor_entity_id, commit, currency_symbol, until)

        $donor = Donor::withTrashed()->find($donor_id);

        $emailset = new Emailset;
        $emailsets = $emailset->getEmailSets($donor->hysform_id);

        $template_errors=[];
        if (!empty($emailsets['default_emailset'])) {
            $t = new Emailtemplate;
            $e_s= Emailset::where('id', $emailsets['default_emailset']['id'])->get();
            $template_errors = $t->templateErrors($e_s);
        }
            
        $desigs = Designation::whereClientId(Session::get('client_id'))->where('hysforms', $donor->hysform_id)->get();
        $designations = [];
        foreach ($desigs as $d) {
                $designations[] = $d;
        }

        $funding_entities= $donor->getFundingEntities(Session::get('client_id'));

        // return var_dump($funding_entities);
/*
        $commitments = Commitment::whereDonorId($donor_id) 
            ->where('type', '2') 
            ->orWhere(function($query) 
        { 
            $query->where('until', '>', Carbon::now()) 
            ->where('until', '0000-00-00'); 
        })->get();    
*/

        $e = new Entity;
        $name = $e->getDonorName($donor_id);

        return view('admin.views.donationsByDonor', [
            'donations' => $out,
            'sponsorships' => $sponsorships,
            'funding_entities' => $funding_entities,
            'donor' => $donor,
            'hysform'=> Hysform::find($donor->hysform_id),
            'designations' => $designations,
            'commitments' => $commitments,
            'useCC' => $dntns->checkUseCC(),
            'donorCardActive' => $dntns->isDonorCardActive($donor->id),
            'anyDonorCardActive' => $dntns->isAnyDonorCardActive($donor->id),
            'name' => $name,
            'dntns' => $dntns,
            'emailsets'=>$emailsets,
            'years' => $years= $donor->getYears($donor),
            'template_errors' => $template_errors
        ]);
    }
                
    public function addCommitmentDonation($commitment_id)
    {
        $commitment = Commitment::find($commitment_id);
        $donation = new Donation;
        
        // get currency symbol
        if ($commitment->type == 1) {
            $entity = Entity::withTrashed()->find($commitment->designation);
            $program = Program::find($entity->program_id);
        } else {
            $donor = Donor::find($commitment->donor_id);
            $program = Program::where('donor_hysform_id', $donor->hysform_id)->first();
        }
            
        $setting = Setting::find($program->setting_id);
        $program_settings = json_decode($setting->program_settings);
        $currencty_symbol = $program_settings->currency_symbol;
            
        $method = $donation->getMethod($commitment->method);
        $frequency = $donation->getFrequency($commitment->frequency);

        $d = $donation->getDesignation($commitment->type, $commitment->designation);
        $details = ['designation_name' => $d['name'], 'amount' => $commitment->amount, 'method' => $method, 'frequency' => $frequency, 'currency_symbol' => $currencty_symbol, 'frequency_total'=>$donation->getFrequencyTotal($commitment->amount, $commitment->frequency)];
            
        return view('admin.views.addCommitmentDonation', [
            'commitment' => $commitment,
            'details' => $details,
            'dntns' => $donation,
            'useCC' => $donation->checkUseCC(),
            'donorCardActive' => $donation->isDonorCardActive($commitment->donor_id)
        ]);
    }
        
    public function postAddCommitmentDonation($commitment_id)
    {
        $data = Input::all();
        $commitment = Commitment::find($commitment_id);
        $donor = Donor::find($commitment->donor_id);
        $donation = new Donation;
        $message = '';
        $alert= 'success';
            
        $d = $donation->getDesignation($commitment->type, $commitment->designation);
            
        // run the transaction
        if ($data['method'] == 3) {
            // check if using stripe
            if ($donation->isDonorCardActive($commitment->donor_id)) {
                try {
                    $params = [
                        'amount' => $data['amount'],
                        'currency' => 'usd',
                        'donor_id'  => $commitment->donor_id,
                        'description' => $d['name']
                    ];
                    $response = $donation->createCharge($params);
                        
                    if ($response->success) {
                        $message = 'Donation Successful';
                        $alert = 'success';
                        $result = $response->result;
                    } else {
                        $message = $response->result;
                        $alert = 'danger';
                        return redirect('admin/commitment_donation/'.$commitment_id.'')
                        ->with('message', $message)
                        ->with('alert', $alert);
                    }
                } catch (\Exception $e) {
                    $message = $e->getMessage();
                    $alert = 'danger';
                    return redirect('admin/commitment_donation/'.$commitment_id.'')
                        ->with('message', $message)
                        ->with('alert', $alert);
                }
            } else {
                // warn that there is not a cc attached to this donor
                $message = "There is not a credit card associated with this donor's account. Please go back and add a credit card or choose a different payment method.";
                $alert = "danger";
                return redirect('admin/commitment_donation/'.$commitment_id.'')
                ->with('message', $message)
                ->with('alert', $alert);
            }
        } // end - if ($data['method'] == 3) {

        // add payment to donations
        $donation->client_id = Session::get('client_id');
        $donation->donor_id = $donor->id;
            
        if ($commitment->type == 1) {
            $donation->type = 1;
        }
            
        if ($commitment->type == 2) {
            $donation->type = 3;
        }
            
        $donation->amount = $data['amount'];
        $donation->designation = $commitment->designation;
        $donation->method = $data['method'];
        $donation->result = $data['result'];
            
        if (isset($result)) {
            $donation->result .= ' (Transaction Reference = '.$result.')';
        }
            
        if (isset($data['created_at'])&&!empty($data['created_at'])) {
            $donation->created_at = new Carbon($data['created_at']);
        }
        $donation->save();
            
        // update last date on commitment
        $last = $this->determineLast($commitment->last, $commitment->frequency);
            
        $commitment->last = $last;

        if ($commitment->funding=='1'&&$commitment->type=='1') {
                $d1=new Donor;
                $d1->setStatus($commitment->designation);
        }
        $commitment->save();


            
        // send receipt email
        $email = new Emailtemplate;
        $details['donor'] = $email->getDonor($donor->id);
        $method = $donation->getMethod($data['method']);
        $frequency = $donation->getFrequency($commitment->frequency);
        $designations = $d['name'].' @ '.$data['amount'].' ('.$frequency.')';

        $details['donation'] = ['designations' => $designations, 'total_amount' => $data['amount'], 'method' => $method, 'date' => $donation->created_at->toFormattedDateString()];
        //var_dump($donation->created_at);
        $entity = new Entity;
        $donor_info = $entity->getDonorName($donor->id);
        $to = ['type' => 'donor', 'name' => $donor_info['name'], 'email' => $donor_info['email'], 'id' => $donor_info['id']];
            
        $emailSent = $email->sendEmail($d['emailset_id'], $details, 'pay_receipt', $to);
        if ($emailSent == true) {
            $message .= "Donation receipt emailed to donor.";
        } else {
            $message .= "Donation receipt not emailed to donor.";
        }
            
        $donor= Donor::find($commitment->donor_id);
        //Reload the Cache entry for this donor!
        $donor->reloadDonorsToCache($donor);

        return redirect('admin/donations_by_donor/'.$donor->id.'')
            ->with('message', Session::get('message').'<br/>'.$message)
            ->with('alert', $alert);
    }
        
    public function determineLast($last, $frequency)
    {
        $last = Carbon::createFromTimeStamp(strtotime($last));
            
        switch ($frequency) {
            case 1:
                return $last->addMonth();
                    
            break;
                    
            case 2:
                return $last->addMonths(3);

            break;
                    
            case 3:
                return $last->addMonths(6);

            break;
                    
            case 4:
                return $last->addMonths(12);

            break;
        }
    }
        
    public function addCC($donor_id)
    {
        $e= new Entity;
        $name= $e->getDonorName($donor_id);
        $donor=Donor::find($donor_id);

        return view('admin.views.addCC', [
            'donor_id' => $donor_id,
            'donor'     => $donor,
            'name'  => $name
        ]);
    }
        
    public function postAddCC($donor_id)
    {
        $data = Input::all();
            
        try {
            $card = [
                'firstName' => $data['firstName'],
                'lastName' => $data['lastName'],
                'number' => $data['number'],
                'cvv' => $data['cvv'],
                'expiryMonth' => $data['expiryMonth'],
                'expiryYear' => $data['expiryYear']
            ];
            $params['donor_id']=$donor_id;
            $donation = new Donation;

            $response = $donation->createCustomer($card, $params);
                
            if ($response->success) {
                $message = 'Credit card added successfully.';
                $alert = 'success';
                $result = $response->result;

                $donor= Donor::find($donor_id);
            //Reload the Cache entry for this donor!
                $donor->reloadDonorsToCache($donor);
                    
                return redirect('admin/donations_by_donor/'.$donor_id.'')
                ->with('message', $message)
                ->with('alert', $alert);
            } else {
                $message = $response->result;
                $alert = 'danger';
                    
                return redirect('admin/add_cc/'.$donor_id.'')
                ->with('message', $message)
                ->with('alert', $alert)
                ->withInput();
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $alert = 'danger';
            return redirect('admin/add_cc/'.$donor_id.'')
                ->with('message', $message)
                ->with('alert', $alert)
                ->withInput();
        }
    }
        
    public function updateCC($donor_id)
    {
        $data = Input::all();
        $params['donor_id']=$donor_id;

        try {
            $donation = new Donation;
            $response = $donation->updateCard($data, $params);
                
            if ($response->success) {
                $message = 'Credit Card Successfully Updated.';
                $alert = 'success';
            } else {
                $message = $response->result;
                $alert = 'danger';
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $alert = 'danger';
        }
            
        return redirect('admin/donations_by_donor/'.$donor_id.'')
            ->with('message', $message)
            ->with('alert', $alert);
    }
        
    public function deleteCC($donor_id)
    {
        try {
            $donation = new Donation;
            $params['donor_id']=$donor_id;
            $response = $donation->deleteCard($params);
                
            if ($response->success) {
                $message = 'Credit Card Successfully Deleted.';
                $alert = 'success';
                $donor= Donor::find($donor_id);
                //Reload the Cache entry for this donor!
                $donor->reloadDonorsToCache($donor);
            } else {
                $message = $response->result;
                $alert = 'danger';
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $alert = 'danger';
        }
            
        return redirect('admin/donations_by_donor/'.$donor_id.'')
            ->with('message', $message)
            ->with('alert', $alert);
    }

    public function authorizeSilentPost()
    {
        $data=Input::all();

        if (!isset($data['x_subscription_id'])) {
            Log::warning('No ARB Subscription ID found for this transaction, so we won\'t record it. Data: ', $data);
            return;
        }

        $commitment=Commitment::where('arb_subscription_id', $data['x_subscription_id'])->where('method', '5')->first();

        if (!count($commitment)) {
            Log::warning('Commitment Matching with Subscription ID= '.$data['x_subscription_id'].' Not Found', ["data"=>$data]);
            return 'No Commitment found.';
        }

        $donation=new Donation;

        $donation->client_id=$commitment->client_id;

        $donation->donor_id=$commitment->donor_id;

        $donation->type=$commitment->type;


        //For ARB transactions, a commitment must be made in HYS, therefore, one-time is not used to charge the client, but commitment.
        $donation->one_time='0';

        $donation->amount= $data['x_amount'];

        $donation->method= '5';

        $donation->designation = $commitment->designation;

            
        // foreach($data as $k => $d)
        // {
        // 	if(!empty($d)&&($k=='x_cust_id'||$k=='x_trans_id'))
        // 	{
        // 		$result[$k]=$k.': '.$d;
        // 	}
        // }

        $result = [];
        if (isset($data['x_cust_id'])) {
            $result[]='Authorize.Net Customer ID: '.$data['x_cust_id'];
        }

        if (isset($data['x_trans_id'])) {
            $result[]='Authorize.Net Transaction ID: '.$data['x_trans_id'];
        }


        if (isset($result)) {
            $donation->result= implode('<br>', $result);
        }


        $commitment->last=Carbon::now();

        $donation->save();

        $donor= Donor::find($donation->donor_id);
        //Reload the Cache entry for this donor!
        $donor->reloadDonorsToCache($donor);
    }

    public function authorizeSilentPostTest()
    {
        return view('admin.views.authorizeSilentPostTest');
    }
}
