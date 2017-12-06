<?php

namespace App\Models\Imports;

use App\Models\Donation;
use App\Models\Donor;
use App\Models\Entity;
use App\Models\Program;
use Carbon\Carbon;
use Cartalyst\Sentry\Facades\Laravel\Sentry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class importPaymentsCsvToDatabase extends Model
{
    
    public function fire($job, $data)
    {
        
        try {
            ini_set("auto_detect_line_endings", true);
    
            $program_id = $data['program_id'];
            $client_id = $data['client_id'];
            $filename = $data['filename'];
            $user_id = $data['user_id'];
            
            unset($data['program_id']);
            unset($data['client_id']);
            unset($data['filename']);
            unset($data['user_email']);
            unset($data['_token']);
            
            $program = Program::find($program_id);
            
            if ($data['csv_entity_field'] != '_not_selected') {
                // select all entities for program and create array with just hys id and id field selected
                // use selected id field as array key and hys id as value
                $entity = new Entity;
                $entities = $entity->allEntities($program_id);
                
                foreach ($entities as $ent) { // create array with the client selected id as the key and hys internal id as the value
                    if (!empty($ent[ $data['entity_field'] ]) && !empty($ent['id'])) {
                        $e_ids[ $ent[ $data['entity_field'] ] ] = $ent['id'];
                    }
                }
            }
            
            // do the same for donors
            $donor = new Donor;
            $donors = $donor->allDonors($program->donor_hysform_id);

            foreach ($donors['profiles'] as $donor) { // create array with the client selected id as the key and hys internal id as the value
                if (!empty($donor[ $data['donor_field'] ]) && !empty($donor['id'])) {
                    $d_ids[ $donor[ $data['donor_field'] ] ] = $donor['id'];
                }
            }
            
            $file = file_get_contents('https://s3-us-west-1.amazonaws.com/hysfiles/'.$filename.'');
            
            $offset = ['offset' => 0];
            $parser = \KzykHys\CsvParser\CsvParser::fromString($file, $offset);
            $result = $parser->parse();
                                
            // create payments
            foreach ($result as $row) {
                $e = '';
                if (!empty($e_ids)) {
                    // payment is for this entity
                    $e_csv_id = $row[$data['csv_entity_field']]; // sets the selected id field
                    if (isset($e_ids[$e_csv_id])) {
                        $e = $e_ids[$e_csv_id]; // this is the hys entity id
                    }
                }
                
                // from this sponsor
                $d = '';
                $d_csv_id = $row[$data['csv_donor_field']];
                if (isset($d_ids[$d_csv_id])) {
                    $d = $d_ids[$d_csv_id]; // this is the hys donor id
                }
                
                // make sure we have a donor and entity if required
                if (!empty($d)) {
                    // payment date
                    $payment_date = '';
                    $dt = new Carbon;
                    if (isset($data['csv_date_field']) && $data['csv_date_field'] != '_not_selected') {
                        if ($this->isValidTimeStamp($row[$data['csv_date_field']])) {
                            $payment_date = $dt->createFromTimestamp($row[$data['csv_date_field']]);
                        } else {
                            $payment_date = $dt->parse($row[$data['csv_date_field']]);
                        }
                    }


                    $method = '';
                    if (isset($data['csv_method_field']) && $data['csv_method_field'] != '_not_selected') {
                        $method = strtolower(trim($row[$data['csv_method_field']]));
                        $allowed_methods = ['credit card' => 3, 'credit' => 3, 'card' => 3, 'check' => 2, 'cash' => 1, 'wire' => 4];
                        
                        if (!empty($method) && isset($allowed_methods[$method])) {
                            $method = $allowed_methods[$method];
                        }
                    }
                    
                    if (empty($method) && isset($data['method_default'])) {
                        $method = $data['method_default'];
                    }

                    $type = '';
                    if (isset($data['csv_type_field']) && $data['csv_type_field'] != '_not_selected') {
                        $type = strtolower(trim($row[$data['csv_type_field']]));
                        $allowed_types = ['one time donation' => 2, 'one time' => 2, 'one' => 2, 'sponsorship' => 1, 'recurring' => 3];
                        
                        if (!empty($type) && isset($allowed_types[$type])) {
                            $type = $allowed_types[$type];
                        }
                    }
                    
                    if (empty($type) && isset($data['type_default'])) {
                        $type = $data['type_default'];
                    }
                    
                    $result = '';
                    if ($data['csv_result_field'] != '_not_selected') {
                        $result = trim($row[$data['csv_result_field']]);
                    }
                    
                    $amount = '';
                    if ($data['csv_amount_field'] != '_not_selected') {
                        $amount = trim($row[$data['csv_amount_field']]);
                    }
                    
                    $donation = new Donation;
                    $donation->client_id = $client_id;
                    $donation->donor_id = $d;
                    $donation->type = $type;
                    $donation->amount = $amount;
                    $donation->designation = $e;
                    $donation->method = $method;
                    $donation->result = $result;
                    
                    if (!empty($payment_date)) {
                        $donation->created_at = $payment_date;
                    }
                    
                    $donation->save();
                                                            
                    $results['donations'][] = $donation->id;
                                        
                    unset($e);
                    unset($d);
                } // if (isset($e) && isset($d)) {
            }
            
            try {
                $user = Sentry::findUserById($user_id);
                $edata['body'] = 'Your import has been completed successfully.';
                
                Mail::queue('emails.adminEmailTemplate', $edata, function ($message) use ($user) {
                    $message->to($user->email, $user->first_name . ' ' . $user->last_name)->subject('HelpYouSponsor Import Status');
                });
            } catch (Cartalyst\Sentry\Users\UserNotFoundException $f) {
                Log::info('user_id ('. $user_id .') was not found.');
            }
            
            $results['client_id'] = $client_id;
            $results['user_id'] = $user_id;
            $results['program_id'] = $program_id;
            $results['filename'] = $filename;
            Log::info('Job completed ' . json_encode($results));

            $job->delete();
        } catch (Exception $f) {
            // notify of failure and delete job
            try {
                $user = Sentry::findUserById($user_id);
                $edata['body'] = 'There was a problem with your import and it has failed. The error we received is: ' . $f->getMessage();
                
                Mail::queue('emails.adminEmailTemplate', $edata, function ($message) use ($user) {
                    $message->to($user->email, $user->first_name . ' ' . $user->last_name)->subject('HelpYouSponsor Import Status');
                });
            } catch (Cartalyst\Sentry\Users\UserNotFoundException $f) {
                Log::info('user_id ('. $user_id .') was not found. Job failed: ' . $f->getMessage());
            }
            
            $results['client_id'] = $client_id;
            $results['user_id'] = $user_id;
            $results['program_id'] = $program_id;
            $results['filename'] = $filename;

            Log::info('Job failed. ' . $f->getMessage() . ' - ' . json_encode($results));
            
            $job->delete();
        }
    }

    public function isValidTimeStamp($timestamp)
    {
        return ((string) (int) $timestamp === $timestamp)
        && ($timestamp <= PHP_INT_MAX)
        && ($timestamp >= ~PHP_INT_MAX);
    }
}
