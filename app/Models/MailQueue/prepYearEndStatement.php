<?php

namespace App\Models\MailQueue;

use App\Models\Donation;
use App\Models\Donor;
use App\Models\Emailtemplate;
use App\Models\Entity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class prepYearEndStatement extends Model
{
        
    public function fire($job, $data)
    {

        $donor=     Donor::Find($data['donor_id']);
        $emailset_id= $data['emailset_id'];
        $emailtemplate = new Emailtemplate;

        if (isset($data['year'])) {
            $year = $data['year'];
        } else {
            $year = Carbon::now()->year;
        }

        $donations = Donation::where('donor_id', $donor->id)->whereBetween('created_at', [Carbon::createFromDate($year, 1, 1),Carbon::createFromDate($year, 12, 31)])->orderBy('created_at', 'desc')->get();

        $d = new Donation;

        $donations_table = $d->getDonationsTableHTML($donations);
        $total = $donations->sum('amount');

        $e = new Entity;
        $name = $e->getDonorName($donor->id);
        $to = ['type' => 'donor', 'name' => $name['name'], 'email' => $donor->email, 'id' => $donor->id];
        $details['login_info'] = ['date'=>Carbon::now()->toFormattedDateString(),'year'=>$year,'year_end_total'=>$total,'donations_table'=>$donations_table];
        $details['donor'] = $emailtemplate->getDonor($donor->id);
            
        //Email the donor and give them their auto generated temporary password
        $sent = $emailtemplate->sendEmail($emailset_id, $details, 'year_end_statement', $to);

        $job->delete();
    }
}
