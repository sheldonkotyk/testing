<?php

namespace App\Models\Imports;

use App\Models\Commitment;
use App\Models\Donor;
use App\Models\DonorEntity;
use App\Models\Entity;
use App\Models\Program;
use Carbon\Carbon;
use Cartalyst\Sentry\Facades\Laravel\Sentry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class importRelationshipsCsvToDatabase extends Model
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
            
            // select all entities for program and create array with just hys id and id field selected
            // use selected id field as array key and hys id as value
            $entity = new Entity;
            $entities = $entity->allEntities($program_id);
            
            foreach ($entities as $ent) { // create array with the client selected id as the key and hys internal id as the value
                if (!empty($ent[ $data['entity_field'] ]) && !empty($ent['id'])) {
                    $e_ids[ $ent[ $data['entity_field'] ] ] = $ent['id'];
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
                                
            // create commitments and relationship
            foreach ($result as $row) {
                // this entity
                $e_csv_id = $row[$data['csv_entity_field']]; // sets the selected id field
                if (isset($e_ids[$e_csv_id])) {
                    $e = $e_ids[$e_csv_id]; // this is the hys entity id
                }
                
                // is sponsored by this donor
                $d_csv_id = $row[$data['csv_donor_field']];
                
                if (isset($d_ids[$d_csv_id])) {
                    $d = $d_ids[$d_csv_id]; // this is the hys donor id
                }
                
                if (!empty($e) && !empty($d)) {
                    // get the sponsorship amount
                    $amount = '';
                    if (isset($data['csv_commitment_field']) && $data['csv_commitment_field'] != '_not_selected') {
                        $amount = $row[$data['csv_commitment_field']]; // use the amount if selected in the CSV
                    } else {
                        if (!empty($entities[$e]['sp_amount'])) {
                            $amount = $entities[$e]['sp_amount']; // else use the amount in the entity profile
                        }
                    }

                    $last = '';
                    $created_at = '';
                    if (isset($data['csv_date_field']) && $data['csv_date_field'] != '_not_selected') {
                        $dt = new Carbon;
                        
                        try {
                            $created_at = $dt->createFromTimeStamp($row[$data['csv_date_field']])->toDateTimeString();
                            $day = $dt->createFromTimeStamp($row[$data['csv_date_field']])->day;
                        } catch (Exception $f) {
                            try {
                                $created_at = $dt->parse($row[$data['csv_date_field']])->toDateTimeString();
                                $day = $dt->parse($row[$data['csv_date_field']])->day;
                            } catch (Exception $f) {
                            }
                        }
                        
                        if ($day < $dt->day) { // if the day this month has past
                            $last = $dt->create($dt->year, $dt->month, $day);
                        } else {
                            $month = $dt->month - 1;
                            $last = $dt->create($dt->year, $month, $day);
                        }
                    }
                    
                    // save sponsorship
                    $de = new DonorEntity;
                    $de->donor_id = $d;
                    $de->entity_id = $e;
                    $de->client_id = $client_id;
                    $de->program_id = $program_id;
                    $de->save();
                    
                    $results['donorentity'][] = $de->id;
                    
                    if (!empty($amount)) { // create commitment if we have a sponsorship amount
                        
                        $method = 2;
                        foreach ($donors['profiles'] as $dp) {
                            if ($dp['id'] == $d) {
                                if (!empty($dp['stripe_cust_id']) || !empty($dp['authorize_profile'])) {
                                    $method = 3;
                                    break;
                                }
                            }
                        }
                        
                        
                        // create commitment
                        $com = new Commitment;
                        $com->client_id = $client_id;
                        $com->donor_id = $d;
                        $com->donor_entity_id = $de->id;
                        $com->type = '1';
                        $com->frequency = '1';
                        $com->amount = $amount;
                        $com->designation = $e;
                        $com->method = $method;
                        
                        if (!empty($last)) {
                            $com->last = $last;
                        } else {
                            $com->last = Carbon::now();
                        }
                        
                        if (!empty($created_at)) {
                            $com->created_at = $created_at;
                        }
                        
                        $com->save();
                        
                        $results['commitment'][] = $com->id;
                    }
                    
                    unset($e);
                    unset($d);
                    unset($de);
                } // if (isset($e) && isset($d)) {
            }

            $e = new Entity;

            $e->reloadSponsorshipsToCache(DonorEntity::whereIn('id', $results['donorentity'])->get());
            
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
}
