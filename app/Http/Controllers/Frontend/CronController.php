<?php  namespace App\Controllers\Frontend;
 
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
use Donation;
use Designation;
use Commitment;
use Omnipay\Common\GatewayFactory;
use Config;
use ClientPayment;
use Emailsetting;
use Artisan;
use Queue;
    
use App\Http\Controllers\Controller;

class CronController extends Controller
{
        
    // set to true for testing. No emails or database changes will be made.
    private $testmode = false;
        
    // use to test successful or failed transactions
    private $success = false;

    //used to enable or disable credit card processing for testing purposes
    private $process_cc = true;

    //used to enable or disable email sending for testing purposes
    private $send_email = true;

    //used to enable or disable all database changes for testing purposes
    private $update_db = true;

    //enable or disable backups on cron job
    private $do_backup = true;
    
    public function testConfig($client_id)
    {
        $emailsetting = Emailsetting::where('client_id', $client_id)->first();
        Config::set('emailsetting', $emailsetting);
        $data = ['body' => 'You did it, this will work!', 'to' => 'to', 'id' => 'id'];
        Mail::send('emails.donorEmailTemplate', $data, function ($message) {
            $message->to('chad@minuszeromedia.com', 'John Smith')->subject('WooHoo it works!');
        });
    }
    
    private function setkey()
    {
        $key = 'yPBxoaUF8ofFgpAB7CPttpkByvaHoG';
        return $key;
    }
        
    private function backupDatabase()
    {
        Artisan::call('db:backup', ['--upload-s3' => 'hys-mysql-bu']);
    }
    
        
        
    // strictly used for testing
    public function testDiffInDays()
    {
        $commitments = Commitment::all();
        $now = Carbon::now();
        echo $now;
        echo '<br><br><br>';


        foreach ($commitments as $commitment) {
            $last = Carbon::createFromTimeStamp(strtotime($commitment->last));
            $charge = false;
            switch ($commitment->frequency) {
                case 2: // weekly
                    if ($now->diffInDays($last) >= '7') {
                        $charge = true;
                    }
                    echo 'id = '.$commitment->id.' frequency = '.$commitment->frequency.'';
                    echo '<br>';
                    echo 'charge = '.$charge.'';
                    echo '<br>';
                    echo 'diff in days = '.$now->diffInDays($last).'';
                    echo '<br><br>';
                    break;
                case 3: // bi-monthly (1st and 15th)
                    if (Carbon::now()->day == '1' or Carbon::now()->day == '15') {
                        $charge = true;
                    }
                    echo 'id = '.$commitment->id.' frequency = '.$commitment->frequency.'';
                    echo '<br>';
                    echo 'charge = '.$charge.'';
                    echo '<br>';
                    echo '1st of month = '.$now->firstOfMonth().', 1st or 15th of month = '.Carbon::now()->day.'';
                    echo '<br><br>';
                    break;
                case 4: // monthly
                    if ($now >= $last->addMonth()) {
                        $charge = true;
                    }
                    echo 'id = '.$commitment->id.' frequency = '.$commitment->frequency.'';
                    echo '<br>';
                    echo 'charge = '.$charge.'';
                    echo '<br>';
                    echo 'last add month = '.$last.'';
                    echo '<br><br>';
                    break;
            }
        }
    }
        
    private function prepareEmail($commitment)
    {
    }
    
    // public function runDailyCharges() {
    // 	$data = Input::all();
    // 	$key = '';
    // 	if (isset($data['key'])) {
    // 		$key = $data['key'];
    // 	}
    // 	$all_results = array();
            
    // 	if ($key == $this->setkey()) {
            
    // 		$now = Carbon::now();
                
                
    // 		// remove any expired commitments
   //  	$expired = Commitment::where('until', '<', $now)->where('until', '!=', '0000-00-00')->get();
   //  	if (!empty($expired)) {
      //   	foreach ($expired as $e) {
            // 	$e->delete();
      //   	}
      //   }
                

      //   // run charges
      //   $commitments = Commitment::all();
      //   $program = new Program;
      //   $a_donor = new Donor;
               
      //   foreach ($commitments as $commitment) {
      //   	$donation= new Donation;
      //   	Config::set('client_id', $commitment->client_id);
                    
      //   	$last = Carbon::createFromTimeStamp(strtotime($commitment->last));
      //   	if ($commitment->method == 3) {
      //   		$commitment_save=false;
      //   		$donation_save= false;
         //    	// check frequency
                        
         //    	$charge = $this->determineFrequency($now, $last, $commitment->frequency);
                        
         //    	$redis = RedisL4::connection();
         //    	//return var_dump(array($charge,$commitment));
            // 	if ($charge == true) {
            // 	    $donor = Donor::find($commitment->donor_id);
            // 	    $response=false;
            // 	    if($donation->isAnyDonorCardActive($commitment->donor_id,$commitment->client_id)) {
                            

            //     		try {
            // 		    	$d = $a_donor->getCommitmentDesignation($commitment, $redis);

            // 		    	$params = array(
            // 		    		'amount' => $donor->getFrequencyTotal($commitment->amount,$commitment->frequency),
            // 		    		'currency' => 'usd',
            // 		    		'donor_id'	=> $commitment->donor_id,
            // 		    		'description' => $d['name']
            // 		    	);
                                    
            // 		    	// don't actually run charge in test mode
            // 		    	if ($this->testmode == false&&$this->process_cc==true) {
            // 		    		$response = $donation->createCharge($params, $commitment->client_id);
            // 		    	}
                                    
            // 		    	if ($response->success || ($this->testmode == true && $this->success == true)) {
                                        
            // 			    	if ($this->testmode == false) {
            // 				    	$result = $response->result;
                                            
            // 						if (isset($result)) {
            // 							$donation->result = "( ".$donor->getFrequency($commitment->frequency)." ) ".' Transaction Reference = '.$result.'';
            // 						}
                                            
            // 						// update
            // 						$commitment->last = Carbon::now();
            // 						if($this->update_db==true)
            // 						{
            // 							$commitment_save=$commitment->save();
            // 						}

                                            
            // 						// email receipt
            // 				    	$email = new Emailtemplate;
            // 				    	$details['donor'] = $email->getDonor($commitment->donor_id);
                                            
            // 				    	//Taken away to keep the pay_remind from spitting out broken short-codes.
            // 				    	// if($commitment->type=='1')
            // 				    		// $details['entity'] = $email->getEntity($commitment->designation);
                                            
            // 						$method = $donation->getMethod($commitment->method);
            // 						$frequency = $donation->getFrequency($commitment->frequency);
                                            
            // 						$d = $a_donor->getCommitmentDesignation($commitment, $redis);
                                            
            // 				    	$currency='$';
            // 				    	if($frequency=='Monthly')
            // 							$name = $d['name'].' @ '.$currency.$commitment->amount.' '.$frequency.')';
            // 						else
            // 							$name = $d['name'].' @ '.$currency.$commitment->amount.' per Month (Paid '.$frequency.')';
    
            // 				    	$details['donation'] = array(
            //                        'method' => $method,
            // 				    	  'designations' => $name,
            // 				    	  'date'	=> Carbon::now()->toFormattedDateString(),
            // 				    	  'total_amount' => $donor->getFrequencyTotal($commitment->amount,$commitment->frequency)
            // 				    	  );
            // 				    	$entity = new Entity;
            // 				    	$donor = $entity->getDonorName($commitment->donor_id);
            // 				    	$to = array('type' => 'donor', 'name' => $donor['name'], 'email' => $donor['email'], 'id' => $commitment->donor_id);
                                            
            // 				    	if($this->send_email == true&&isset($d['emailset_id']))
            // 				    	{
            // 				    		$emailSent = $email->sendEmail($d['emailset_id'], $details, 'pay_receipt', $to);
            // 				    	}
            // 			    	 }
                
            // 		    	} else { // if the charge fails
                                        
            // 		    		if ($this->testmode == false) {
            // 				    	$result = $response->result;
            // 						if (!empty($result)) {
            // 							$donation->result = $result;
            // 						}
                                            
            // 				    	// email notification to donor
            // 				    	$email = new Emailtemplate;
            // 				    	$details['donor'] = $email->getDonor($commitment->donor_id);
                                            
            // 				    	//Taken away to keep template from spitting out broken short codes.
            // 				    	// if($commitment->type=='1')
            // 				    		// $details['entity'] = $email->getEntity($commitment->designation);

            // 						$method = $donation->getMethod($commitment->method);
            // 						$frequency = $donation->getFrequency($commitment->frequency);
            // 						$redis = RedisL4::connection();
            // 						$d = $a_donor->getCommitmentDesignation($commitment, $redis);
            // 				    	$details['donation'] = array('designation_name' => $d['name'], 'amount' => $commitment->amount, 'method' => $method, 'frequency' => $frequency, 'error' => $result);
            // 				    	$entity = new Entity;
            // 				    	$donor = $entity->getDonorName($commitment->donor_id);
            // 				    	$to = array('type' => 'donor', 'name' => $donor['name'], 'email' => $donor['email'], 'id' => $commitment->donor_id);
                                            
            // 				    	if($this->send_email == true&&isset($d['emailset_id']))
            // 				    	{
            // 				    		$emailSent = $email->sendEmail($d['emailset_id'], $details, 'pay_fail', $to);
            // 				    	}
            // 				    	// email notification to admin
            // 				    	$emailtemplate = Emailtemplate::where('emailset_id', $d['emailset_id'])->where('trigger', 'pay_fail_admin')->first();
            // 				    	if(count($emailtemplate))
            // 				    	{
            // 					    	$to = array('type' => 'admin', 'name' => 'Admin', 'email' => $emailtemplate->to, 'id' => $commitment->donor_id);
                                                
            // 					    	if($this->send_email == true&&isset($d['emailset_id']))
            // 					    	{
            // 								$emailSent = $email->sendEmail($d['emailset_id'], $details, 'pay_fail_admin', $to);
            // 							}
            // 						}
            // 					}
            // 		    	}
                                    
            // 			} catch (\Exception $e) {
                                
            // 				if ($this->testmode == false) {
            // 					$result = $e->getMessage();
                                        
            // 					if (!empty($result)) {
            // 						$donation->result = $result;
            // 					}
                                        
            // 			    	// email notification to donor
            // 			    	$email = new Emailtemplate;
            // 			    	$details['donor'] = $email->getDonor($commitment->donor_id);
            // 					$method = $donation->getMethod($commitment->method);
            // 					$frequency = $donation->getFrequency($commitment->frequency);
            // 					$redis = RedisL4::connection();
            // 					$d = $a_donor->getCommitmentDesignation($commitment, $redis);
            // 			    	$details['donation'] = array('designation_name' => $d['name'], 'amount' => $commitment->amount, 'method' => $method, 'frequency' => $frequency, 'error' => $result);
            // 			    	$entity = new Entity;
            // 			    	$donor = $entity->getDonorName($commitment->donor_id);
            // 			    	$to = array('type' => 'donor', 'name' => $donor['name'], 'email' => $donor['email'], 'id' => $commitment->donor_id);
                                        
            // 			    	if($this->send_email == true&&isset($d['emailset_id']))
            // 				    {
            // 			    		$emailSent = $email->sendEmail($d['emailset_id'], $details, 'pay_fail', $to);
            // 			    	}
                                        
            // 			    	// email notification to admin
            // 			    	$emailtemplate = Emailtemplate::where('emailset_id', $d['emailset_id'])->where('trigger', 'pay_fail_admin')->first();
            // 			    	if(count($emailtemplate))
            // 			    	{
            // 				    	$to = array('type' => 'admin', 'name' => 'Admin', 'email' => $emailtemplate->to, 'id' => $commitment->donor_id);
            // 				    	if($this->send_email == true&&isset($d['emailset_id']))
            // 					    {
            // 				    		$emailSent = $email->sendEmail($d['emailset_id'], $details, 'pay_fail_admin', $to);
            // 				    	}
            // 			    	}
            // 			    }
            // 			}
                                
            // 			//Only add donation to MySql if the charge successfully ran!
            // 			if ($this->testmode == false) {
            // 				//add to database
            // 				$donation->client_id = $commitment->client_id;
            // 				$donation->donor_id = $commitment->donor_id;
            // 				if ($commitment->type == 1) {
            // 					$donation->type = 1;
            // 				} elseif ($commitment->type == 2) {
            // 					$donation->type = 3;
            // 				}
            // 				$donor1= new Donor;

            // 				if($response!=false&&$response->success)
            // 				{
            // 					$donation->amount = $donor1->getFrequencyTotal($commitment->amount,$commitment->frequency);
            // 				}
            // 				else
            // 				{
            // 					$donation->amount=0;
            // 					$donation->result = $donation->result . '<br>Payment of ' . $donor1->getFrequencyTotal($commitment->amount,$commitment->frequency) . ' failed. ';
            // 				}

            // 				$donation->designation = $commitment->designation;
            // 				$donation->method = $commitment->method;

            // 				if($this->update_db==true)
            // 				{
            // 					$donation_save=$donation->save();
            // 				}

            // 				if($commitment->funding=='1')
            // 				{
            // 					$a_donor->setStatus($commitment->designation);
            // 				}
            // 			}

            // 			// $all_results[$commitment->id]= array(
            // 			// 'commitment_id'=>$commitment->id,
            // 			// 'commitment save() return value'=>$commitment_save,
            //          // 'donation save() return value'=>$donation_save,
            // 			// 'donation transaction result'=> $donation->result,
            // 			// 'amount'=>$donation->amount,
            // 			// 'method'=>$donation->method,
            // 			// 'created_at'=> $donation->created_at->toFormattedDateString(),
            // 			// 'donation_id'=> $donation->id);
                                
            // 	    } else { // end if (isAnyDonorCardActive()) {
                                
            // 	    	if ($this->testmode == false) {
            // 		    	// if credit card method is chosen but no credit card is found send reminder email
            // 				$email = new Emailtemplate;
            // 		    	$details['donor'] = $email->getDonor($commitment->donor_id);
    
            // 				//Taken away to keep the pay_remind from spitting out broken short-codes.
            // 		    	//Added to give Admins access to Entity fields
            // 		    	//if($commitment->type=='1')
            // 			    //		$details['entity']= $email->getEntity($commitment->designation_id);
        
            // 				$redis = RedisL4::connection();
            // 				$d = new Donation;
            // 				$d = $a_donor->getCommitmentDesignation($commitment, $redis);

                                    
                                    
            // 		    	$details['donation'] = array('designation_name' => $d['name'], 'amount' => $commitment->amount,'method'=> $donation->getMethod($commitment->method));
            // 		    	$entity = new Entity;
            // 		    	$donor = $entity->getDonorName($commitment->donor_id);
            // 		    	$to = array('type' => 'donor', 'name' => $donor['name'], 'email' => $donor['email'], 'id' => $commitment->donor_id);
            // 		    	if($this->send_email == true&&isset($d['emailset_id']))
            // 				{
            // 		    		$emailSent = $email->sendEmail($d['emailset_id'], $details, 'pay_remind', $to);
            // 		    	}
            // 			} // end if testmode
            // 	    }
            // 	}

                        
            // } // end if ($commitment->method == 3) {
                    
            // // payment reminder emails - pay_remind
            // if ($commitment->method != 3) {
                        
         //    	$charge = $this->determineFrequency($now, $last, $commitment->frequency);

            //  if ($charge == true) {
            // 		if ($this->testmode == false) {
                                
            // 			// only send reminder for the first two days after it is due
            // 			$last = Carbon::createFromTimeStamp(strtotime($commitment->last));
            // 			$send = $this->determineReminderEmailSend($now, $last, $commitment->frequency);
                                
            // 			if (true == $send) {
                                    
            // 				$email = new Emailtemplate;
            // 		    	$details['donor'] = $email->getDonor($commitment->donor_id);
        
            // 				//Taken away to keep the pay_remind from spitting out broken short-codes.
            // 		    	//Added to give Admins access to Entity fields
            // 		    	// if($commitment->type=='1')
            // 				    		// $details['entity']= $email->getEntity($commitment->designation_id);
        
            // 				$redis = RedisL4::connection();
            // 				$d = new Donation;
            // 				$d = $a_donor->getCommitmentDesignation($commitment, $redis);
                                    
            // 		    	$details['donation'] = array('designation_name' => $d['name'], 'amount' => $commitment->amount,'method' => $donation->getMethod($commitment->method));
        
            // 		    	$entity = new Entity;
            // 		    	$donor = $entity->getDonorName($commitment->donor_id);
            // 		    	$to = array('type' => 'donor', 'name' => $donor['name'], 'email' => $donor['email'], 'id' => $commitment->donor_id);
                                    
            // 		    	if($this->send_email == true&&isset($d['emailset_id']))
            // 				{
            // 		    		$emailSent = $email->sendEmail($d['emailset_id'], $details, 'pay_remind', $to);
            // 		    	}
                                                                        
            // 			} // if (true == $send) {
            // 		}
                            
            // 	}
            // }
                    
      //   } // end foreach
                

      //   //Reset email settings to HYS
      //   Config::set('emailsetting', '');
                

      //   // Charge clients on the first of every month
      //   if (Carbon::now()->day == '1') {
                    
      //   	$clients = Client::all();
      //   	foreach ($clients as $client) {
      //   		$alert = '';
            // 	$amount = '';
            // 	$count = '';
            // 	$total_donations = '';

      //   		if (!empty($client->stripe_cust_id)) {
            //     	$count = Commitment::whereClientId($client->id)->count();

            // 		//Get one_time donations and count them
            //     	$donation_count = Donation::whereClientId($client->id)->where('one_time','1')->count();

            //     	if($donation_count > 0)
            //     	{
                                
            //     		//If we have one time donations add them to the total count
            //     		$total_donations = $count + $donation_count;
                                
            //     	} else {
                                
            // 	    	$total_donations = $count;
                                
            //     	}

            //     	$gateway_factory = new GatewayFactory;
            // 		$gateway = $gateway_factory->create('Stripe');
            // 		$gateway->setApiKey(config('stripe_secret_key'));
                            
            // 		$amount = $total_donations * .25;
                            
            // 		if ($amount >= 1) {
                            
            // 			if ($this->testmode == false && $this->process_cc == true) {
            // 	    		try {
            // 			    	$params = array(
            // 			    		'amount' => $amount,
            // 			    		'currency' => 'usd',
            // 			    		'cardReference' => $client->stripe_cust_id,
            // 			    		'description' => 'HelpYouSponsor'
            // 			    	);

            // 					$response = $gateway->purchase($params)->send();
                                        
            // 			    	if ($response->isSuccessful()) {
            // 				    	$message = 'Donation Successful';
            // 				    	$alert = 'success';
            // 				    	$result = $response->getTransactionReference();
                                            
            // 				    	//If there are one_time donations
            // 				    	if($donation_count > 0)
            // 				    	{
            // 				    		//Update the donation table to reflect that these donations have been paid for
            // 				    		Donation::whereClientId($client->id)->where('one_time','1')->update(array('one_time'=>'2'));
            // 				    	}

            // 			    	} else {
            // 				    	$message = $response->getMessage();
            // 				    	$alert = 'danger';
            // 			    	}
            // 				} catch (\Exception $e) {
            // 					$message = $e->getMessage();
            // 					$alert = 'danger';
            // 				}
            // 			} // end if testmode
            // 		}
            // 	}
                        
            // 	if ($this->testmode == false) {
            // 		// log payment in database
            // 		if ($amount >= 1) {
            // 			$cp = new ClientPayment;
            // 			$cp->client_id = $client->id;
            // 			$cp->amount = $amount;
            // 			$cp->result = $message;

            // 			if($this->update_db==true)
            // 			{
            // 				$cp->save();
            // 			}
            // 		}
                            
            // 		if ($alert == 'success') {
            // 			//Send email Receipt to Client

            // 			// notify client
            // 			$data['organization'] = $client->organization;
            // 			$data['amount'] = $amount;
            //          $data['commitments'] = $count;
            // 			$data['onetime']= $donation_count;
            //          $data['date']=Carbon::now()->toFormattedDateString();
            // 			try
            // 			{
            // 				if (!empty($client->email)) {
            // 					if($this->send_email == true)
            // 					{
            // 						$data['email'] = $client->email;
            // 						Mail::queue('emails.clientPaymentReceipt', $data, function($message) use ($client)
            // 						{
            // 					    	$message->to($client->email, $client->organization)->subject('HelpYouSponsor Client Receipt');
            // 						});
            // 					}

            // 				}
            // 			} catch (\Exception $e) {
            // 				$data['error'] = $e->getMessage();
            // 			}

                                
            // 		} else if ($alert == 'danger') {
            // 			// notify client
            // 			$data['organization'] = $client->organization;
            // 			$data['client_id'] = $client->id;
            // 			$data['msg'] = $message;
            // 			$data['amount'] = $amount;
            //          $data['commitments'] = $count;
            //          $data['error'] = '';
                                
            // 			try
            // 			{
            // 				if (!empty($client->email)) {
            // 					if($this->send_email == true)
            // 					{
            // 						$data['email'] = $client->email;
            // 						Mail::queue('emails.payNotifyClient', $data, function($message) use ($client)
            // 						{
            // 					    	$message->to($client->email, $client->organization)->subject('HelpYouSponsor Monthly Payment');
            // 						});
            // 					}

            // 				}
            // 			} catch (\Exception $e) {
            // 				$error = $e->getMessage();
            // 				Log::error('An error occured when trying to send the client "' . $client->organization . '" (' . $client->email . ') their receipt for $' . $data['amount'] . ':' . $error);
            // 			}

            // 			// notify hys
                                
            // 			if($this->send_email == true)
            //          {
            // 				Mail::queue('emails.payNotifyHYS', $data, function($message)
            // 				{
            // 			    	$message->to('team@helpyousponsor.com', 'HelpYouSponsor Accounting')->subject('Client Payment Problem');
            // 				});
            // 			}
        
            //      }
            // 	} // end if testmode
                                
      //   	} // end foreach ($clients as $client) {
      //   }
                
      //   // create mysql backup

      //   if($this->do_backup == true)
      //   {
      //   	$this->backupDatabase();
      //   }

      //   //return var_dump($all_results);
      //   return 'Completed Successfully';
    // 	} // end if key
            
    // 	return 'Key did not match, access denied.';
    // }

    public function runDailyCharges()
    {
        $data = Input::all();
        $key = '';
        if (isset($data['key'])) {
            $key = $data['key'];
        }
            
        if ($key == $this->setkey()) {
            $now = Carbon::now();
                
            //Remove any expired commitments
            $expired = Commitment::where('until', '<', $now)->where('until', '!=', '0000-00-00')->get();
            if (!empty($expired)) {
                foreach ($expired as $e) {
                    $e->delete();
                }
            }

            // Send Donor charges to the Queue
            foreach (Commitment::all() as $commitment) {
                Queue::push('chargeCommitment', ['id'  =>  $commitment->id]);
            }

            //Reset email settings to HYS

            Config::set('emailsetting', '');

            $i=0;
            // Charge clients on the first of every month
            if ($now->day == '1') {
                foreach (Client::all() as $client) {
                    Queue::push('chargeClient', ['id'  =>  $client->id]);
                }
            }

                
            // create mysql backup

            if ($this->do_backup == true) {
                $this->backupDatabase();
            }

            return 'Completed Successfully';
        } // end if key
            
        return 'Key did not match, access denied.';
    }
}
