<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Commitment extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];


    public function determineFrequency($now, $last, $frequency)
    {
                $charge = false;
        switch ($frequency) {
            case 1: // Monthly
                if ($now >= $last->addMonth()) {
                    $charge = true;
                }
                break;
            case 2: // Quarterly
                if ($now >= $last->addMonths(3)) {
                    $charge = true;
                }
                break;
            case 3: // Semiannually
                if ($now >= $last->addMonths(6)) {
                    $charge = true;
                }
                break;
            case 4: // Annually
                if ($now >= $last->addMonths(12)) {
                    $charge = true;
                }
        }
                return $charge;
    }


    public function determineLast($last, $frequency, $forgive = null)
    {
        $last = new Carbon($last);

        $new_last= Carbon::now();

        //If the previous last is more than a year old, or the donor form is set to forgive missed payments, make the current last today.
        if ($new_last->diffInYears($last)>0||$forgive==1) {
            return $new_last;
        }

        switch ($frequency) {
            case 1: // Monthly
                $new_last= $last->addMonth();
                break;
            case 2: // Quarterly
                $new_last= $last->addMonths(3);
                break;
            case 3: // Semiannually
                $new_last= $last->addMonths(6);
                break;
            case 4: // Annually
                $new_last= $last->addMonths(12);
        }

        return $new_last;
    }

    public function determineReminderEmailSend($now, $last, $frequency)
    {
            
            $send = false;
            
        switch ($frequency) {
            case 1: // Monthly
                if ($now >= $last->addMonth()) {
                    $future = $last->addDays(2);

                    if ($now < $future) {
                        $send = true;
                    }
                }
                break;
                    
            case 2:
                if ($now >= $last->addMonths(3)) {
                    $future = $last->addDays(2);

                    if ($now < $future) {
                        $send = true;
                    }
                }
                break;
                    
            case 3:
                if ($now >= $last->addMonths(6)) {
                    $future = $last->addDays(2);

                    if ($now < $future) {
                        $send = true;
                    }
                }
                break;
                    
            case 4: // Annually
                if ($now >= $last->addMonths(12)) {
                    $future = $last->addDays(2);

                    if ($now < $future) {
                        $send = true;
                    }
                }
                break;
        }
            
            return $send;
    }
}
