<?php

namespace App\Models\MailQueue;

use App\Models\Emailsetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class sendEmailQueue extends Model
{
        
    public function fire($job, $data)
    {
                            

        if (isset($data['client_id'])) {
            $emailsetting = '';
            $emailsetting = Emailsetting::where('client_id', $data['client_id'])->first();
                
            if (count($emailsetting)) {
                if (!empty($emailsetting->host)&&!empty($emailsetting->username)&&!empty($emailsetting->password)) {
                    //Use Client Mailgun Server
                    Config::set('mail.host', $emailsetting->host);
                    Config::set('mail.from', ['address' => $emailsetting->from_address, 'name' => $emailsetting->from_name]);
                    Config::set('mail.username', $emailsetting->username);
                    Config::set('mail.password', $emailsetting->password);
                } else {
                    //Use HYS Mailgun server to send mail with client defined from address and name
                    Config::set('mail.host', 'smtp.mailgun.org');
                    Config::set('mail.from', ['address' => $emailsetting->from_address, 'name' => $emailsetting->from_name]);
                    Config::set('mail.username', 'postmaster@helpyousponsor.org');
                    Config::set('mail.password', '3jwex0yq2i73');
                }
            }
        } else {
            //Use HYS Mailgun server to send mail with HYS as the sender
            Config::set('mail.host', 'smtp.mailgun.org');
            Config::set('mail.from', ['address' => 'no-reply@helpyousponsor.org', 'name' => 'HelpYouSponsor']);
            Config::set('mail.username', 'postmaster@helpyousponsor.org');
            Config::set('mail.password', '3jwex0yq2i73');
        }
                
                    
        try {
            Mail::send($data['template'], $data['data'], function ($message) use ($data) {
                    
                if ($data['data']['to'] == 'admin') {
                    $i=0;
                    foreach ($data['data']['to_email'] as $email) {
                        $i++;
                        if (!empty($data['emailset']->from) && !empty($data['emailsetting']->from_name)) {
                            $message->from($data['emailset']->from, $data['emailsetting']->from_name);
                        }
                            
                        $message->to($email, $data['data']['to_name'].$i);
                    }
    
                    $message->subject($data['data']['subject']);
                } else {
                    if (!empty($data['emailset']->from) && !empty($data['emailsetting']->from_name)) {
                        $message->from($data['emailset']->from, $data['emailsetting']->from_name);
                    }

                    $message->to($data['data']['to_email'], $data['data']['to_name'])->subject($data['data']['subject']);
                }
            });
                
            $job->delete();
        } catch (Exception $e) {
            Log::info('Mail sending error - ' . $e->getMessage() . ' ' . var_dump($data));
                
            $job->delete();
        }
    }
}
