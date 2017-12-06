<?php

namespace App\Models\CommitmentQueue;

use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\Commitment;
use App\Models\Donation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class chargeClient extends Model
{


    // set to true for testing. No emails or database changes will be made.
    private $testmode = false;
        
    //used to enable or disable credit card processing for testing purposes
    private $process_cc = true;

    //used to enable or disable email sending for testing purposes
    private $send_email = true;

    //used to enable or disable all database changes for testing purposes
    private $update_db = true;

        
    public function fire($job, $data)
    {

        $client= Client::find($data['id']);

        $alert = '';
        $amount = '';
        $count = '';
        $total_donations = '';

        if (!empty($client->stripe_cust_id)) {
            $count = Commitment::whereClientId($client->id)->count();

            //Get one_time donations and count them
            $donation_count = Donation::whereClientId($client->id)->where('one_time', '1')->count();

            if ($donation_count > 0) {
                //If we have one time donations add them to the total count
                $total_donations = $count + $donation_count;
            } else {
                $total_donations = $count;
            }

            $gateway_factory = new Omnipay\Common\GatewayFactory;
            $gateway = $gateway_factory->create('Stripe');
            $gateway->setApiKey(config('stripe_secret_key'));
                                
            if ($total_donations > 10) {
                $amount = $total_donations * .25;
                    
                if ($total_donations < 80 && $client->id > 199) {
                    $amount = 20.00;
                }
                
                if ($this->testmode == false && $this->process_cc == true) {
                    try {
                        $params = [
                            'amount' => $amount,
                            'currency' => 'usd',
                            'cardReference' => $client->stripe_cust_id,
                            'description' => 'HelpYouSponsor'
                        ];

                        $response = $gateway->purchase($params)->send();
                            
                        if ($response->isSuccessful()) {
                            $message = 'Donation Successful';
                            $alert = 'success';
                            $result = $response->getTransactionReference();
                                
                        //If there are one_time donations
                            if ($donation_count > 0) {
                                //Update the donation table to reflect that these donations have been paid for
                                Donation::whereClientId($client->id)->where('one_time', '1')->update(['one_time'=>'2']);
                            }
                        } else {
                            $message = $response->getMessage();
                            $alert = 'danger';
                        }
                    } catch (\Exception $e) {
                        $message = $e->getMessage();
                        $alert = 'danger';
                    }
                } // end if testmode
            } else {
                if ($donation_count > 0) {
                    //Update the donation table so these donations aren't charged in the future
                    Donation::whereClientId($client->id)->where('one_time', '1')->update(['one_time'=>'2']);
                }
            }
        }
            
        if ($this->testmode == false) {
            // log payment in database
            if ($amount >= 1) {
                $cp = new ClientPayment;
                $cp->client_id = $client->id;
                $cp->amount = $amount;
                $cp->result = $message;

                if ($this->update_db==true) {
                    $cp->save();
                }
            }
                
            if ($alert == 'success') {
                //Send email Receipt to Client

                // notify client
                $data['organization'] = $client->organization;
                $data['amount'] = $amount;
                $data['commitments'] = $count;
                $data['onetime']= $donation_count;
                $data['date']=Carbon::now()->toFormattedDateString();
                try {
                    if (!empty($client->email)) {
                        if ($this->send_email == true) {
                            $data['email'] = $client->email;
                            Mail::queue('emails.clientPaymentReceipt', $data, function ($message) use ($client) {
                                $message->to($client->email, $client->organization)->subject('HelpYouSponsor Client Receipt');
                            });
                        }
                    }
                } catch (\Exception $e) {
                    $data['error'] = $e->getMessage();
                }
            } else if ($alert == 'danger') {
                // notify client
                $data['organization'] = $client->organization;
                $data['client_id'] = $client->id;
                $data['msg'] = $message;
                $data['amount'] = $amount;
                $data['commitments'] = $count;
                $data['error'] = '';
                    
                try {
                    if (!empty($client->email)) {
                        if ($this->send_email == true) {
                            $data['email'] = $client->email;
                            Mail::queue('emails.payNotifyClient', $data, function ($message) use ($client) {
                                $message->to($client->email, $client->organization)->subject('HelpYouSponsor Monthly Payment');
                            });
                        }
                    }
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                    Log::error('An error occured when trying to send the client "' . $client->organization . '" (' . $client->email . ') their receipt for $' . $data['amount'] . ':' . $error);
                }

                // notify hys
                    
                if ($this->send_email == true) {
                    Mail::queue('emails.payNotifyHYS', $data, function ($message) {
                        $message->to('team@helpyousponsor.com', 'HelpYouSponsor Accounting')->subject('Client Payment Problem');
                    });
                }
            }
        } // end if testmode
            

        // Log::info('Process finished for charging Client # '.$client->id );
    
        $job->delete();
    }
}
