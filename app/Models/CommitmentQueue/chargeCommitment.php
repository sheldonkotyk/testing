<?php

namespace App\Models\CommitmentQueue;

use App\Models\Commitment;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Emailtemplate;
use App\Models\Entity;
use App\Models\Hysform;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class chargeCommitment extends Model
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
        
    public function fire($job, $data)
    {

        $commitment= Commitment::find($data['id']);
        $donation= new Donation;
        $a_donor = new Donor;
        if ($commitment!=null) {
            $d = $a_donor->getCommitmentDesignation($commitment);
            

            $now = Carbon::now();

            Config::set('client_id', $commitment->client_id);
                
            $last = Carbon::createFromTimeStamp(strtotime($commitment->last));
            if ($commitment->method == 3) {
                $commitment_save=false;
                $donation_save= false;
                // check frequency
                    
                $charge = $commitment->determineFrequency($now, $last, $commitment->frequency);
                    
                //return var_dump(array($charge,$commitment));
                if ($charge == true) {
                    $donor = Donor::find($commitment->donor_id);
                    $response=false;
                        

                    if ($donor!=null&&$donation->isAnyDonorCardActive($commitment->donor_id, $commitment->client_id)) {
                        try {
                            $params = [
                                'amount' => $donor->getFrequencyTotal($commitment->amount, $commitment->frequency),
                                'currency' => 'usd',
                                'donor_id'  => $commitment->donor_id,
                                'description' => $d['name']
                            ];

                                
                            // don't actually run charge in test mode
                            if ($this->testmode == false&&$this->process_cc==true) {
                                $response = $donation->createCharge($params, $commitment->client_id);
                            }
                                
                            if ($response->success || ($this->testmode == true && $this->success == true)) {
                                if ($this->testmode == false) {
                                    $result = $response->result;
                                        
                                    if (isset($result)) {
                                        $donation->result = "( ".$donor->getFrequency($commitment->frequency)." ) ".' Transaction Reference = '.$result.'';
                                    }
                                        
                                    // update
                                    //Set this according to the previous last, rather than today's date...
                                    //This keeps the payment date from changing if paymemts have failed in the past.
                                    //If the forgive option is selected for the client, missed payments will be forgiven and the new last will be set to the current day.

                                    $donor_form= Hysform::find($donor->hysform_id);
                                    $forgive=null;
                                    if (isset($donor_form)) {
                                        $forgive= $donor_form->forgive_missed_payments;
                                    }

                                    $commitment->last = $commitment->determineLast($commitment->last, $commitment->frequency, $forgive);

                                    if ($this->update_db==true) {
                                        $commitment_save=$commitment->save();
                                    }
                                        
                                    // email receipt
                                    $email = new Emailtemplate;
                                    $details['donor'] = $email->getDonor($commitment->donor_id);
                                        
                                    $method = $donation->getMethod($commitment->method);
                                    $frequency = $donation->getFrequency($commitment->frequency);
                                        
                                        
                                    $currency='$';
                                    if ($frequency=='Monthly') {
                                        $name = $d['name'].' @ '.$currency.$commitment->amount. ' '. $frequency;
                                    } else {
                                        $name = $d['name'].' @ '.$currency.$commitment->amount.' per Month (Paid '.$frequency.')';
                                    }

                                    $details['donation'] = [
                                      'method' => $method,
                                      'designations' => $name,
                                      'date'    => Carbon::now()->toFormattedDateString(),
                                      'total_amount' => $donor->getFrequencyTotal($commitment->amount, $commitment->frequency)
                                      ];
                                    $entity = new Entity;
                                    $donor = $entity->getDonorName($commitment->donor_id);
                                    $to = ['type' => 'donor', 'name' => $donor['name'], 'email' => $donor['email'], 'id' => $commitment->donor_id];
                                        
                                    if ($this->send_email == true&&isset($d['emailset_id'])) {
                                        $emailSent = $email->sendEmail($d['emailset_id'], $details, 'pay_receipt', $to);
                                    }
                                }
                            } else { // if the charge fails
                                    
                                if ($this->testmode == false) {
                                    $result = $response->result;
                                    if (!empty($result)) {
                                        $donation->result = $result;
                                    }
                                        
                                    // email notification to donor
                                    $email = new Emailtemplate;
                                    $details['donor'] = $email->getDonor($commitment->donor_id);
                                        
                                    $method = $donation->getMethod($commitment->method);
                                    $frequency = $donation->getFrequency($commitment->frequency);
                                    $details['donation'] = ['designation_name' => $d['name'], 'amount' => $commitment->amount, 'method' => $method, 'frequency' => $frequency, 'error' => $result];
                                    $entity = new Entity;
                                    $donor = $entity->getDonorName($commitment->donor_id);
                                    $to = ['type' => 'donor', 'name' => $donor['name'], 'email' => $donor['email'], 'id' => $commitment->donor_id];
                                        
                                    if ($this->send_email == true&&isset($d['emailset_id'])) {
                                        $emailSent = $email->sendEmail($d['emailset_id'], $details, 'pay_fail', $to);
                                    }
                                    // email notification to admin
                                    $emailtemplate = Emailtemplate::where('emailset_id', $d['emailset_id'])->where('trigger', 'pay_fail_admin')->first();
                                    if (count($emailtemplate)) {
                                        $to = ['type' => 'admin', 'name' => 'Admin', 'email' => $emailtemplate->to, 'id' => $commitment->donor_id];
                                            
                                        if ($this->send_email == true&&isset($d['emailset_id'])) {
                                            $emailSent = $email->sendEmail($d['emailset_id'], $details, 'pay_fail_admin', $to);
                                        }
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            if ($this->testmode == false) {
                                $result = $e->getMessage();
                                    
                                if (!empty($result)) {
                                    $donation->result = $result;
                                }
                                    
                                // email notification to donor
                                $email = new Emailtemplate;
                                $details['donor'] = $email->getDonor($commitment->donor_id);
                                $method = $donation->getMethod($commitment->method);
                                $frequency = $donation->getFrequency($commitment->frequency);
                                $details['donation'] = ['designation_name' => $d['name'], 'amount' => $commitment->amount, 'method' => $method, 'frequency' => $frequency, 'error' => $result];
                                $entity = new Entity;
                                $donor = $entity->getDonorName($commitment->donor_id);
                                $to = ['type' => 'donor', 'name' => $donor['name'], 'email' => $donor['email'], 'id' => $commitment->donor_id];
                                    
                                if ($this->send_email == true&&isset($d['emailset_id'])) {
                                    $emailSent = $email->sendEmail($d['emailset_id'], $details, 'pay_fail', $to);
                                }
                                    
                                // email notification to admin
                                $emailtemplate = Emailtemplate::where('emailset_id', $d['emailset_id'])->where('trigger', 'pay_fail_admin')->first();
                                if (count($emailtemplate)) {
                                    $to = ['type' => 'admin', 'name' => 'Admin', 'email' => $emailtemplate->to, 'id' => $commitment->donor_id];
                                    if ($this->send_email == true&&isset($d['emailset_id'])) {
                                        $emailSent = $email->sendEmail($d['emailset_id'], $details, 'pay_fail_admin', $to);
                                    }
                                }
                            }
                        }
                            
                        //Only add donation to MySql if the charge successfully ran!
                        if ($this->testmode == false) {
                            //add to database
                            $donation->client_id = $commitment->client_id;
                            $donation->donor_id = $commitment->donor_id;
                            if ($commitment->type == 1) {
                                $donation->type = 1;
                            } elseif ($commitment->type == 2) {
                                $donation->type = 3;
                            }
                            $donor1= new Donor;

                            if ($response!=false&&$response->success) {
                                $donation->amount = $donor1->getFrequencyTotal($commitment->amount, $commitment->frequency);
                            } else {
                                $donation->amount=0;
                                $donation->result = $donation->result . '<br>Payment of ' . $donor1->getFrequencyTotal($commitment->amount, $commitment->frequency) . ' failed. ';
                            }

                            $donation->designation = $commitment->designation;
                            $donation->method = $commitment->method;

                            if ($this->update_db==true) {
                                $donation_save=$donation->save();
                            }

                            if ($commitment->funding=='1') {
                                $a_donor->setStatus($commitment->designation);
                            }
                        }
                    } else { // end if (isAnyDonorCardActive()) {
                            
                        if ($this->testmode == false) {
                            // if credit card method is chosen but no credit card is found send reminder email
                            $email = new Emailtemplate;
                            $details['donor'] = $email->getDonor($commitment->donor_id);

                            $details['donation'] = ['designation_name' => $d['name'], 'amount' => $commitment->amount,'method'=> $donation->getMethod($commitment->method)];
                            $entity = new Entity;
                            $donor = $entity->getDonorName($commitment->donor_id);
                            $to = ['type' => 'donor', 'name' => $donor['name'], 'email' => $donor['email'], 'id' => $commitment->donor_id];
                            if ($this->send_email == true&&isset($d['emailset_id'])) {
                                $emailSent = $email->sendEmail($d['emailset_id'], $details, 'pay_remind', $to);
                            }
                        } // end if testmode
                    }
                }
            } // end if ($commitment->method == 3) {
                

            // payment reminder emails - pay_remind
            if ($commitment->method != 3) {
                $charge = $commitment->determineFrequency($now, $last, $commitment->frequency);

                if ($charge == true) {
                    if ($this->testmode == false) {
                        // only send reminder for the first two days after it is due
                        $last = Carbon::createFromTimeStamp(strtotime($commitment->last));
                        $send = $commitment->determineReminderEmailSend($now, $last, $commitment->frequency);
                            
                        if (true == $send) {
                            $email = new Emailtemplate;
                            $details['donor'] = $email->getDonor($commitment->donor_id);

                            $details['donation'] = ['designation_name' => $d['name'], 'amount' => $commitment->amount,'method' => $donation->getMethod($commitment->method)];

                            $entity = new Entity;
                            $donor = $entity->getDonorName($commitment->donor_id);
                            $to = ['type' => 'donor', 'name' => $donor['name'], 'email' => $donor['email'], 'id' => $commitment->donor_id];
                                
                            if ($this->send_email == true&&isset($d['emailset_id'])) {
                                $emailSent = $email->sendEmail($d['emailset_id'], $details, 'pay_remind', $to);
                            }
                        } // if (true == $send) {
                    }
                }
            }
        } // if(commitment!=null)

        $job->delete();
    }
}
