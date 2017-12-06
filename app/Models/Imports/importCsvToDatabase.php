<?php

namespace App\Models\Imports;

use App\Models\Donor;
use App\Models\Emailtemplate;
use App\Models\Entity;
use App\Models\Hysform;
use App\Models\Program;
use Carbon\Carbon;
use Cartalyst\Sentry\Facades\Laravel\Sentry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class importCsvToDatabase extends Model
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
            
            $file = file_get_contents('https://s3-us-west-1.amazonaws.com/hysfiles/'.$filename.'');
            
            $offset = ['offset' => 0];
            if (isset($data['offset'])) {
                $offset = ['offset' => $data['offset']];
                unset($data['offset']);
            }
            
            $parser = \KzykHys\CsvParser\CsvParser::fromString($file, $offset);
            $result = $parser->parse();
            
            $program = Program::find($program_id);
            if ($data['import_type'] == 'recipients' || $data['import_type'] == 'donors') {
                if (isset($data['hysCustomid'])) {
                    if ($data['import_type'] == 'recipients') {
                        $prefix = $program->prefix;
                        $counter = $program->counter;
                    } else if ($data['import_type'] == 'donors') {
                        $donor_hysform = Hysform::find($program->donor_hysform_id);
                        $prefix = $donor_hysform->prefix;
                        $counter = $donor_hysform->counter;
                    }
                }
            }
            
            // array of fields in the order that they are in the CSV
            $fieldKeys = $data['fields'];
            
            if ($data['import_type'] == 'donors') {
                $profile = [];
                
                foreach ($result as $row) {
                    $donor = new Donor;
                    $donor->client_id = $client_id;
                    $donor->hysform_id = $program->donor_hysform_id;
                    
                    if (!in_array('password', $fieldKeys)) {
                        $password = $this->generate_password();
                        $donor->password = Hash::make($password);
                    }
                    // $key is a number [0] and it corresponds to the same key in the $fieldKeys
                    foreach ($row as $key => $field) {
                        if (isset($fieldKeys[$key])) {
                            if ($fieldKeys[$key] == 'password') {
                                $password = $field;
                                $donor->password = Hash::make($password);
                            } else if ($fieldKeys[$key] == 'username') {
                                $donor->username = $field;
                            } else if ($fieldKeys[$key] == 'email') {
                                $donor->email = $field;
                            } else if ($fieldKeys[$key] == 'stripe_cust_id') {
                                $donor->stripe_cust_id = $field;
                            } else if ($fieldKeys[$key] == 'authorize_profile') {
                                $donor->authorize_profile = $field;
                            } else if ($fieldKeys[$key] != '_do_not_import') {
                                $profile[$fieldKeys[$key]] = $field;
                            }
                        }
                    } // end foreach ($row as $key => $field) {
                    
                    // create username if none entered
                    if (!isset($donor->username)) {
                        if (isset($donor->email)) {
                            $donor->username = $donor->email;
                        }
                    }
                                        
                    if (isset($data['hysCustomid'])) {
                        $counter = ++$counter;
                        $profile[$data['hysCustomid']] = $prefix.$counter;
                    }
                    
                    if (!empty($profile)) { // protect against empty rows being imported
                        
                        $donor->wait_time = Carbon::now();
                        $donor->who_added = json_encode(['type' => 'admin', 'method' => 'csv', 'id' => $user_id]);
                        
                        //Don't use Redis, but rather store in json array in donors table
                        $donor->json_fields = json_encode($profile);
                        $donor->save();
                        
                        // send email to notify donors of their login credentials
                        if (isset($data['notify_donor']) && $data['notify_donor'] == 1) {
                            if (!empty($donor->email) && !empty($donor->username)) {
                                $e = new Entity;
                                $emailtemplate = new Emailtemplate;
                                $name = $e->getDonorName($donor->id);
                                $to = ['type' => 'donor', 'name' => $name['name'], 'email' => $donor->email, 'id' => $donor->id];
                                $details['login_info'] = ['username' => $donor->username, 'password' => $password];
                                $details['donor'] = $emailtemplate->getDonor($donor->id);
                                $emailtemplate->sendEmail($data['notify_donor'], $details, 'notify_donor', $to);
                            }
                        }
        
                        $results['donors'][] = $donor->id;
                    } // end if (!empty($profile)) {
                } // end foreach ($result as $row) {
                
                if (isset($data['hysCustomid'])) {
                    $donor_hysform->counter = $counter;
                    $donor_hysform->save();
                }

                //Reload caches for all users non-trashed donor table
                $d = new Donor;
                $donors = Donor::whereIn('id', $results['donors'])->get();
                $d->reloadDonorsToCache($donors);
                $d->syncDonorsToMailchimp($donors);
            }
            
            if ($data['import_type'] == 'recipients') {
                $profile = [];
                
                foreach ($result as $row) {
                    // $key is a number [0] and it corresponds to the same key in the $fieldKeys
                    foreach ($row as $key => $field) {
                        // create the array for storing record in redis
                        if (isset($fieldKeys[$key]) && $fieldKeys[$key] != '_do_not_import') {
                            $profile[$fieldKeys[$key]] = $field;
                        }
                    }
                    if (isset($data['hysCustomid'])) {
                        $counter = ++$counter;
                        $profile[$data['hysCustomid']] = $prefix.$counter;
                    }
                    
                    if (!empty($profile)) { // protect against empty rows being imported
                        
                        // create the entity in mysql
                        $entity = new Entity;
                        $entity->client_id = $client_id;
                        $entity->program_id = $program_id;
                        $entity->status = 0;
                        $entity->sp_amount = $data['sp_amount'];
                        $entity->sp_num = $data['sp_num'];
                        $entity->wait_time = Carbon::now();

                        $entity->json_fields = json_encode($profile);
                        $entity->save();
                        
                        $results['entities'][] = $entity->id;
                    }
                }
                
                if (isset($data['hysCustomid'])) {
                    $program->counter = $counter;
                    $program->save();
                }

                $entity = new Entity;
                //Queue reloading the non-trashed tables for this user and all users
                $entity->reloadEntitiesToCache(Entity::whereIn('id', $results['entities'])->get());
            }
                        
            // send completed email
            try {
                $user = Sentry::findUserById($user_id);
                $edata['body'] = 'Your import has been completed successfully.';
                
                Mail::queue('emails.adminEmailTemplate', $edata, function ($message) use ($user) {
                    $message->to($user->email, $user->first_name . ' ' . $user->last_name)->subject('HelpYouSponsor Import Status');
                });
            } catch (Cartalyst\Sentry\Users\UserNotFoundException $e) {
                Log::info('user_id ('. $user_id .') was not found.');
            }
            
            $results['program_id'] = $program_id;
            $results['client_id'] = $client_id;
            $results['filename'] = $filename;
            $results['user_id'] = $user_id;
            
            Log::info('Job completed - ' . json_encode($results));
            $job->delete();
        } catch (Exception $e) {
            // notify of failure and delete job
            try {
                $user = Sentry::findUserById($user_id);
                $edata['body'] = 'There was a problem with your import and it has failed. The error we received is: ' . $e->getMessage();
                
                Mail::queue('emails.adminEmailTemplate', $edata, function ($message) use ($user) {
                    $message->to($user->email, $user->first_name . ' ' . $user->last_name)->subject('HelpYouSponsor Import Status');
                });
            } catch (Cartalyst\Sentry\Users\UserNotFoundException $f) {
                Log::info('user_id ('. $user_id .') was not found. Job failed: ' . $e->getMessage());
            }
            
            $results['program_id'] = $program_id;
            $results['client_id'] = $client_id;
            $results['filename'] = $filename;
            $results['user_id'] = $user_id;

            Log::info('Job failed. ' . $e->getMessage() . ' - ' . json_encode($results));
            
            $job->delete();
        }
    }
        
    public function generate_password()
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $password = substr(str_shuffle($chars), 0, 8);
        
        return $password;
    }
}
