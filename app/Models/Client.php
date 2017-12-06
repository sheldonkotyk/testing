<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;

class Client extends Model
{

    public function users()
    {
        return $this->hasMany('App\Models\User');
    }
    
    public function entities()
    {
        return $this->hasMany('App\Models\Entity');
    }
    
    public function programs()
    {
        return $this->hasMany('App\Models\Program');
    }
    
    public function fields()
    {
        return $this->hasMany('App\Models\Field');
    }
    
    public function donorfields()
    {
        return $this->hasMany('App\Models\Donorfield');
    }
    

    public function countPrograms()
    {
        $client_id = Session::get('client_id');

        return count(Program::where('client_id', '=', $client_id)->get());
    }

    public function countActivePrograms($client_id)
    {

        $count=0;
        foreach (Program::where('client_id', '=', $client_id)->has('entity')->get() as $program) {
            $count++;
        }

        return $count;
    }

    public function countCommitments($client_id, $method = null)
    {
        if (isset($method)) {
            return count(Commitment::where('client_id', '=', $client_id)->where('method', '=', '3')->get());
        } else {
            return count(Commitment::where('client_id', '=', $client_id)->get());
        }
    }

    public function countTodaysCommitments($client_id)
    {

        return count(Commitment::where('client_id', '=', $client_id)->where('created_at', '>', Carbon::now()->subHour(24))->get());
    }

    public function countThisWeeksCommitments($client_id)
    {

        return count(Commitment::where('client_id', '=', $client_id)->where('created_at', '>', Carbon::now()->subWeek())->get());
    }

    public function countNewEmails()
    {
        $client_id = Session::get('client_id');
        $count=Donoremail::where('client_id', '=', $client_id)->where('status', '=', '1')->where('parent_id', '=', '0')->count();
        if ($count>0) {
            return $count;
        } else {
            return '';
        }
    }


    public function countThisMonthsCommitments($client_id)
    {

        return count(Commitment::where('client_id', '=', $client_id)->where('created_at', '>', Carbon::now()->subMonth())->get());
    }


    public function thisWeeksDonations($client_id)
    {

        $total=0;
        foreach (Donation::where('client_id', '=', $client_id)->where('created_at', '>', Carbon::now()->subWeek())->get() as $donation) {
            $total+=$donation->amount;
        }
        return number_format($total, 0, '.', '');
    }

    public function thisMonthsDonations($client_id)
    {
        
        $total = 0;
        $total= Donation::where('client_id', '=', $client_id)->where('created_at', '>', Carbon::now()->subMonth())->get()->sum('amount');

        return number_format($total, 0, '.', '');
    }


    public function countThisMonthsDonations($client_id)
    {
        
        $total = 0;
        $total= Donation::where('client_id', '=', $client_id)->where('created_at', '>', Carbon::now()->subMonth())->get()->count();

        return $total;
    }

    public function allDonationsEver($client_id)
    {

        $total=0;
        foreach (Donation::where('client_id', '=', $client_id)->get() as $donation) {
            $total+=$donation->amount;
        }
        return $formatted = number_format($total, 0, '.', '');
    }

    public function countProgramlessCommitments($client_id)
    {
        return count(Commitment::where('client_id', '=', $client_id)->where('type', '=', '2')->get());
    }




    public function countCommitmentsByProgram($client_id)
    {

        $programs_list= [];

        $entities= Entity::where('client_id', '=', $client_id)->lists('program_id', 'id');
        $programs = Program::where('client_id', '=', $client_id)->lists('name', 'id');
        $donor_entities= DonorEntity::where('client_id', '=', $client_id)->lists('entity_id', 'id');
        $commitments = Commitment::where('client_id', '=', $client_id)->where('type', '1')->get();

        foreach ($commitments as $commitment) {
            if (isset($donor_entities[$commitment->donor_entity_id])) {
                if (isset($entities[$donor_entities[$commitment->donor_entity_id]])) {
                    if (isset($programs[$entities[$donor_entities[$commitment->donor_entity_id]]])) {
                        $program_id= $entities[$donor_entities[$commitment->donor_entity_id]];
                        $program_name= $programs[$entities[$donor_entities[$commitment->donor_entity_id]]];
                        
                        if (isset($programs_list[$program_id][$program_name]['total'])) {
                            $programs_list[$program_id][$program_name]['total']++;
                            $programs_list[$program_id][$program_name]['amount']+=$commitment->amount;
                        } else {
                            $programs_list[$program_id][$program_name]['total']=1;
                            $programs_list[$program_id][$program_name]['amount']=$commitment->amount;
                        }
                    }
                }
            }
        }
        
        if ($this->countProgramlessCommitments($client_id)>0) {
            $programs_list['-1']['Designations']=$this->countProgramlessCommitments($client_id);
        }



        return($programs_list);
    }


    public function fundingByProgram($client_id)
    {

        $programs_list = [];
        $entities = Entity::withTrashed()->where('client_id', $client_id);
        foreach (Donation::where('client_id', '=', $client_id)->where('type', '1')->get() as $donation) {
            $entity = $entities->find($donation->designation);
            
            if (isset($entity->program_id)) {
                $program = Program::find($entity->program_id);
                
                $type = '';
                if (!empty($program)) {
                    $type = $program->getProgramType($program->id);
    
                    if ($type == 'funding') {
                        if (isset($programs_list[$program->id][$program->name]['total'])) {
                            $programs_list[$program->id][$program->name]['total']++;
                            $programs_list[$program->id][$program->name]['amount']+=$donation->amount;
                        } else {
                            $programs_list[$program->id][$program->name]['total']=1;
                            $programs_list[$program->id][$program->name]['amount']=$donation->amount;
                        }
                    }
                }
            }
        }
        
        return($programs_list);
    }


    public function isCurrent($comm)
    {
        if ($comm->frequency==1) {
            $from=Carbon::now()->subMonth();
        }
        if ($comm->frequency==2) {
            $from=Carbon::now()->subMonths(3);
        }
        if ($comm->frequency==3) {
            $from=Carbon::now()->subMonths(6);
        }
        if ($comm->frequency==4) {
            $from=Carbon::now()->subMonths(12);
        }
        
        if ($comm->last > $from) {
            return true;
        } else {
            return false;
        }
    }

    public function getCommitmentGraphWithDonations($client_id, $from, $to)
    {


        $now=Carbon::now();
        $graph_data=[];
        $temp_data=[];
        $commitments= Commitment::where('client_id', '=', $client_id)
        ->where('created_at', '>=', $from)
        ->where('created_at', '<=', $to)->get();


        foreach ($commitments as $comm) {
            $date = $comm->last;

            if (!isset($temp_data[$date]['total'])) {
                $temp_data[$date]['total']=1;
            } else {
                $temp_data[$date]['total']++;
            }
            
            if ($this->isCurrent($comm)) {
                if (!isset($temp_data[$date]['current_total'])) {
                    $temp_data[$date]['current_total']=1;
                } else {
                    $temp_data[$date]['current_total']++;
                }
            }
        }

        foreach ($temp_data as $date => $num) {
            if (empty($num['current_total'])) {
                $num['current_total']=0;
            }

            $graph_data[]=[
                'dates'         => $date,
                'totals'        => $num['total'],
                'current_totals'    => $num['current_total']];
        }

        return $graph_data;
    }

    public function getCommitmentGraphDates($client_id, $from, $to, $withZeros = false)
    {

        $now=Carbon::now();
        $graph_data=[];
        $temp_data=[];
        $commitments= Commitment::where('client_id', '=', $client_id)
        ->where('created_at', '>=', $from)
        ->where('created_at', '<=', $to)->get();

        if ($withZeros) {
            $temp_data=$this->dateRange($from, $to);
        }


        foreach ($commitments as $comm) {
            $date = $comm->created_at->format('Y-m-d');

            if (!isset($temp_data[$date])) {
                $temp_data[$date]=1;
            } else {
                $temp_data[$date]++;
            }
        }

        $grand_total=0;
        foreach ($temp_data as $date => $num) {
            $grand_total+= $num;
            $graph_data[]=['dates' => $date, 'totals' => number_format($num, 0, '.', '')];
        }

        if ($grand_total) {
            return $graph_data;
        } else {
            return [];
        }
    }


    public function getCommitmentGraph($client_id, $days)
    {
    
            $from=Carbon::now()->subDays($days);
            $to=Carbon::now();
    
            return $this->getCommitmentGraphDates($client_id, $from, $to);
    }


    public function getEntityProgressGraph($client_id, $program_id)
    {
    
        $entities= Entity::where('program_id', $program_id)->get();
    
        $donor = new Donor;
        $redis=  RedisL4::connection();
    
        foreach ($entities as $e) {
            $name=$donor->getEntityName($e->id);
            $amount_details=$donor->getAmountDetailsForTable($e);
    
            $graph_data[]=['name' => $name['name'], 'percent' => $amount_details['Percent Complete']] ;
        }
    
        if (count($entities)) {
            return $graph_data;
        } else {
            return [];
        }
    }

    public function getDonationGraphDates($client_id, $from, $to, $withZeros = false)
    {

        $now=Carbon::now();
        $graph_data=[];
        $temp_data=[];
        $donations= Donation::whereClientId($client_id)->whereBetween('created_at', [$from, $to])
        ->get();

        if ($withZeros) {
            $temp_data=$this->dateRange($from, $to);
        }

        foreach ($donations as $don) {
            $date = $don->created_at->format('Y-m-d');

            if (!isset($temp_data[$date])) {
                $temp_data[$date]=$don->amount;
            } else {
                $temp_data[$date]+=$don->amount;
            }
        }

        $grand_total=0;
        foreach ($temp_data as $date => $num) {
            $grand_total+= $num;

            $graph_data[]=['dates' => $date, 'totals' => number_format($num, 0, '.', '')];
        }
        if ($grand_total) {
            return $graph_data;
        } else {
            return [];
        }
    }


    public function getDonationGraph($client_id, $days = 7)
    {

        $from=Carbon::now()->subDays($days);
        $to=Carbon::now();

        return $this->getDonationGraphDates($client_id, $from, $to);
    }

    public function getRemainingSponsorships($program_id)
    {
    
        $program= Program::find($program_id);
        $ps=Setting::find($program->setting_id);
        
        if (empty($ps)) { //If program has no settings, send a -1
            return '-1';
        }
        
        $settings=json_decode($ps->program_settings);

        $type=$settings->program_type;
        $total=0;


        if ($type=='number') {
            $nums_array=null;
            if (is_numeric($settings->number_spon)) {
                $number_spon=$settings->number_spon;
            } else {
                $nums_array=explode(',', $settings->number_spon);
            }

            $message ='Error :<br/>';
            foreach (Entity::whereProgramId($program_id)->get() as $entity) {
                if (is_array($nums_array)) {
                    if (in_array($entity->sp_num, $nums_array)) {
                        $number_spon=$entity->sp_num;
                    } else {
                        $message.='<a href="'.URL::to('admin/edit_entity/'.$entity->id).'">The entity #'.$entity->id.'</a> has it\'s "Number of Sponsors Required" set to "'.$entity->sp_num.'"<br/>';
                        $number_spon= $nums_array[0];
                    }
                }
                $this_remainder=$number_spon - DonorEntity::whereEntityId($entity->id)->count();

                //Don't count extra sponsorships (per entity) in this math
                if ($this_remainder>0) {
                    $total+= $this_remainder;
                }
            }
        } else {
            return '-1';
        }
        if ($message!='Error :<br/>') {
            $message.='Available options according to your settings are: "'.$settings->number_spon.'"<br/>';
            $message.='You can fix this problem by changing the "Number of Sponsors Required" on the entity, or by <a href="'.URL::to('admin/edit_settings/'.$ps->id).'">changing the settings</a>.<br/>';
            $message.='This issue will prevent the "Remaining By Program" stats table from displaying accurately.';
            Session::set('error', $message);
            Session::set('error-alert', 'warning');

            Session::flash('message', 'Warning: There are <a href="'.URL::to('admin/error').'">errors</a> that prevent your statistics from displaying accurately.');
            Session::flash('alert', 'warning');
        }
        return $total;
    }

    public function getAllRemainders($client_id)
    {

            $programs_list= [];

        foreach (Program::whereClientId($client_id)->get() as $program) {
            $t=$this->getRemainingSponsorships($program->id);
            if ($t>=0) {
                $programs_list[$program->id][$program->name]=$t;
            }
        }

            return($programs_list);
    }

    public function load_stats($client_id, $reload = false, $url = false)
    {

        $key = 'dashboard-'.$client_id;

        if (!empty($reload)) {
            Cache::forget($key.$reload);
        }

        $data = Cache::remember($key.$reload, 10080, function () use ($client_id) {
            $client = new Client;
            
            $stats = [
                'Total Donations Made In Last 30 Days' => '',
                'Total Donations Ever' => '',
                '# of Donations In Last 30 Days' => ''
            ];
            
            //Made Fast
            $stats['Number of Active Programs']= $client->countActivePrograms($client_id);

            //Already Fast
            $stats['Total Number of Commitments']= $client->countCommitments($client_id);

            //Already Fast
            $stats['Total Credit Card Commitments']= $client->countCommitments($client_id, '3');

            //Already Fast
            $stats['Total Commitments Today'] = $client->countTodaysCommitments($client_id);

            //Already Fast
            $stats['New Commitments In Last 7 Days'] = $client->countThisWeeksCommitments($client_id);

            //Already Fast
            $stats['New Commitments In Last 30 Days'] = $client->countThisMonthsCommitments($client_id);


            $stats['Total Donations Made In Last 30 Days'] = $client->thisMonthsDonations($client_id);

            $stats['Total Donations Ever'] = $client->allDonationsEver($client_id);
                
            $stats['# of Donations In Last 30 Days'] = $client->countThisMonthsDonations($client_id);



            $commitments_by_program= $client->countCommitmentsByProgram($client_id);

            $remainders_by_program= $client->getAllRemainders($client_id);

            // $funding_by_program= $client->fundingByProgram($client_id);

            // $commitments_by_program= array();

            // $remainders_by_program= array();

            $funding_by_program= [];


            //Shows 30 days of commitments
            // $commitment_graph_data=$client->getCommitmentGraph(30);
            $commitment_graph_data=[];

            //Shows 30 days of donations
            // $donation_graph_data=$client->getDonationGraph(30);
            $donation_graph_data=[];

            return  [
                'date' => Carbon::now('America/Vancouver')->toDayDateTimeString() . " (PST)",
                'stats' => $stats,
                'commitments_by_program' => $commitments_by_program,
                'remainders_by_program' => $remainders_by_program,
                'commitment_graph_data'    => $commitment_graph_data,
                'donation_graph_data'   => $donation_graph_data,
                'funding_by_program' => $funding_by_program,
                'org'               => Client::find($client_id)
            ];
        });

        if ($reload) {
            Cache::put($key, $data, 10080);
            Cache::forget($key.$reload);
        }

        return $data;
    }
    

    public function isBoxActive($client_id = null)
    {
        if ($client_id == null) {
            $client_id=Session::get('client_id');
        }
    }

    
    /**
     * creates a default set of all program modules.
     *
     * @access public
     * @param mixed $client->id
     * @return void
     */
    public function programInABox($client_id)
    {
        
        $client = Client::find($client_id);
        
        Hysform::unguard();
        $entity_form = Hysform::create([
            'client_id' => $client_id,
            'type' => "entity",
            'name' => "Child Profiles"
        ]);
        
        $donor_form = Hysform::create([
            'client_id' => $client_id,
            'type' => "donor",
            'name' => "Sponsor Profiles"
        ]);
        Hysform::reguard();
        
        $ef_data = [
            [
                'client_id' => $client_id,
                'hysform_id' => $entity_form->id,
                'field_key' => 'en_child_name',
                'field_label' => 'Child Name',
                'field_data' => 'Enter Name',
                'field_type' => 'hysText',
                'required' => 1,
                'permissions' => 'public',
                'admingroup_id' => 0,
                'is_title' => 1,
                'field_order' => 1
            ],
    
            [
                'client_id' => $client_id,
                'hysform_id' => $entity_form->id,
                'field_key' => 'en_birthday',
                'field_label' => 'Birthday',
                'field_data' => 'YYYY/MM/DD',
                'field_type' => 'hysDate',
                'required' => 0,
                'permissions' => 'public',
                'admingroup_id' => 0,
                'is_title' => 0,
                'field_order' => 2
            ],
    
            
            [
                'client_id' => $client_id,
                'hysform_id' => $entity_form->id,
                'field_key' => 'en_gender',
                'field_label' => 'Gender',
                'field_data' => ',Male,Female',
                'field_type' => 'hysSelect',
                'required' => 1,
                'permissions' => 'public',
                'admingroup_id' => 0,
                'is_title' => 0,
                'field_order' => 3
            ]
        ];
        
        $df_data = new Donorfield;
        $df_data->client_id = $client_id;
        $df_data->hysform_id = $donor_form->id;
        $df_data->field_key = 'dn_donor_id';
        $df_data->field_label = 'Donor ID';
        $df_data->field_type = 'hysCustomid';
        $df_data->required = 1;
        $df_data->permissions = 'public';
        $df_data->admingroup_id = 0;
        $df_data->is_title = 0;
        $df_data->field_order = 0;
        $df_data->save();
            
        $df_data = new Donorfield;
        $df_data->client_id = $client_id;
        $df_data->hysform_id = $donor_form->id;
        $df_data->field_key = 'dn_donor_name';
        $df_data->field_label = 'Donor Name';
        $df_data->field_data = 'Enter Name';
        $df_data->field_type = 'hysText';
        $df_data->required = 1;
        $df_data->permissions = 'public';
        $df_data->admingroup_id = 0;
        $df_data->is_title = 1;
        $df_data->field_order = 1;
        $df_data->save();

        $df_data = new Donorfield;
        $df_data->client_id = $client_id;
        $df_data->hysform_id = $donor_form->id;
        $df_data->field_key = 'dn_address';
        $df_data->field_label = 'Address';
        $df_data->field_data = 'Enter your street address';
        $df_data->field_type = 'hysText';
        $df_data->required = 1;
        $df_data->permissions = 'public';
        $df_data->admingroup_id = 0;
        $df_data->is_title = 0;
        $df_data->field_order = 2;
        $df_data->save();

        $settings_fields = [
            'client_id' => $client_id,
            'name' => 'General Settings',
            'program_settings' => '{"program_type":"number","sp_num":"","labels":"","number_spon":"1","sponsorship_amount":"34","currency_symbol":"$","duration":"","stripe":"1","login_box":"1","checks":"1"}',
            'text_front' => '',
            'text_profile' => '',
            'text_checkout' => '',
            'text_account' => '',
            'info' => '<p>Sponsoring [en_child_name] helps provide basic needs like food, clothing, and an education.</p>',
            'allow_email' => 1,
            'show_payment' => 1
        ];
        
        Field::unguard();
        $entity_form_fields = Field::insert($ef_data);
        Field::reguard();
        
        Setting::unguard();
        $settings = Setting::create($settings_fields);
        Setting::reguard();
        
        
        $emailset = new Emailset;
        $emailset->client_id = $client_id;
        $emailset->name = 'Child Sponsorship Program Email Templates';
        $emailset->from = $client->email;
        $emailset->save();
        
        $e_templates = [
            
            [
                'client_id' => $client_id,
                'emailset_id' => $emailset->id,
                'trigger' => 'new_donor',
                'subject' => 'Welcome to our sponsorship program',
                'message' => '<p>Hello [dn_donor_name],</p><p>Welcome to our sponsorship program. We are thankful you decided to sponsor [en_child_name] who is a [gender] with a birthday on [birthday]. Here is all the information you need to know about the program and contacting your child.</p><p>Sincerely,</p><p>Program Director</p>'
            ],
            
            [
                'client_id' => $client_id,
                'emailset_id' => $emailset->id,
                'trigger' => 'profile_update',
                'subject' => '[en_child_name]\'s profile has been updated!',
                'message' => '<p>Hello [dn_donor_name],</p><p>We have just updated your sponsor child\'s information. You may log in to our website with your account to view the updated information about [en_child_name].</p><p>Thank you for your continued sponsorship. You are making a difference in the life of a child.</p><p>Sincerely,</p><p>Program Director</p>'
            ],
            
            [
                'client_id' => $client_id,
                'emailset_id' => $emailset->id,
                'trigger' => 'notify_donor',
                'subject' => 'Your sponsorship account has been setup!',
                'message' => '<h2>Donor Account for [dn_donor_name]</h2><p>You have been added to the "sponsorship program"</p><p>Here is your login information:</p><ul><li>Username:  [username]</li><li>Temporary Password: [password]</li></ul>'
            ],
            
            [
                'client_id' => $client_id,
                'emailset_id' => $emailset->id,
                'trigger' => 'pay_receipt',
                'subject' => 'Donation Receipt',
                'message' => '<p>Thank you for your donation. This email serves as your donation receipt.</p>
								<table style=""border-color:#666;width:80%;margin:20px auto;"" cellpadding=""10"">
								<tbody>
								<tr>
									<td>
										Donor Name
									</td>
									<td>
										[donor_name]
									</td>
								</tr>
								<tr>
									<td>
										Designation
									</td>
									<td>
										[designation_name]
									</td>
								</tr>
								<tr>
									<td>
										Amount
									</td>
									<td>
										$[amount]
									</td>
								</tr>
								<tr>
									<td>
										Method
									</td>
									<td>
										[method]
									</td>
								</tr>
								<tr>
									<td>
										Frequency
									</td>
									<td>
										[frequency]</td>
								</tr>
								</tbody>
								</table>'
            ],
            
            [
                'client_id' => $client_id,
                'emailset_id' => $emailset->id,
                'trigger' => 'pay_remind',
                'subject' => 'Donation Reminder',
                'message' => '<p>Hi [donor_name],</p><p>This is a friendly reminder of your commitment to:</p><p>[designation_name] for $[amount].</p><p>If you have already sent in your donation you can disregard this reminder.</p><p>We sincerely thank you for your continued support of our ministry. It is only through people like you that we are able to do what we do.</p><p>Sincerely,</p><p>Support Staff</p>'
            ],
            
            [
                'client_id' => $client_id,
                'emailset_id' => $emailset->id,
                'trigger' => 'pay_fail',
                'subject' => 'Sponsorship Payment Failed',
                'message' => '<p>Hello [dn_donor_name],</p><p>We regret to inform you that there was a problem with your recent sponsorship payment. Please log in to your account on our website in order to update your card number.</p><p>Thank you,</p><p>Program Director</p>'
            ],
            
            [
                'client_id' => $client_id,
                'emailset_id' => $emailset->id,
                'trigger' => 'new_donor_admin',
                'subject' => 'New Sponsorship',
                'message' => '<p>[dn_donor_name] has just signed up to sponsor [en_child_name].</p>'
            ],
            
            [
                'client_id' => $client_id,
                'emailset_id' => $emailset->id,
                'trigger' => 'sp_email',
                'subject' => 'New email from [dn_donor_name] for [en_child_name]',
                'message' => '<p>A sponsor has just sent an email to their sponsor child:</p><p>From: [dn_donor_name]</p><p>To: [en_child_name]</p>'
            ],
            
            [
                'client_id' => $client_id,
                'emailset_id' => $emailset->id,
                'trigger' => 'pay_fail_admin',
                'subject' => 'Sponsorhip payment failed',
                'message' => '<p>This is a notification that the following donor\'s payment failed: [dn_donor_name].</p>'
            ]
        ];
        
        Emailtemplate::unguard();
        $emailtemplates = Emailtemplate::insert($e_templates);
        Emailtemplate::reguard();
        
        $parent = Program::whereClientId($client_id)->first();
        $child = new Program([
            'client_id' => $client_id,
            'name' => 'Child Sponsorship',
            'hysform_id' => $entity_form->id,
            'donor_hysform_id' => $donor_form->id,
            'setting_id' => $settings->id,
            'emailset_id' => $emailset->id
        ]);
        $child->makeFirstChildOf($parent);
        
        return 'Default program setup complete';
    }

    /**
     * creating between two date
     * @param string since
     * @param string until
     * @param string step
     * @param string date format
     * @return array
     * @author Ali OYGUR <alioygur@gmail.com>
     */
    public function dateRange($first, $last, $step = '+1 day', $format = 'Y-m-d')
    {

        $dates = [];
        $current = strtotime($first);
        $last = strtotime($last);

        while ($current <= $last) {
            $dates[date($format, $current)] = (float)0;
            $current = strtotime($step, $current);
        }

        return $dates;
    }
}
