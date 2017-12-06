<?php

namespace App\Models\DonorTableQueue;

use App\Models\Donor;
use Illuminate\Database\Eloquent\Model;

class reloadDonorCache extends Model
{
        
    public function fire($job, $data)
    {

        $hysform_id=$data['hysform_id'];

        $url = $data['url'];

        $count_donors = Donor::where('hysform_id', $hysform_id)->count();

        if ($count_donors>300) {
            if (isset($data['reload'])) {
                $reload = $data['reload'];
            } else {
                $reload=['donors','data'];
            }

            if (isset($data['trashed_options'])) {
                $trashed_options = $data['trashed_options'];
            } else {
                $trashed_options=['','1'];
            }

            $d = new Donor;

            //Empty out the caches related to the donors table
            $d->clearDonorCache($hysform_id, $reload, $trashed_options);
                
            //Refresh the Donors page
            if (in_array('donors', $reload)) {
                $d->getDonors($hysform_id, '');
            }

            //Refresh the Donors Ajax Data that populates the table
            if (in_array('data', $reload)) {
                foreach ($trashed_options as $trashed) {
                    $d->getDonorsAjax($hysform_id, $url, $trashed);
                }
            }

            // Log::info('Donors Table Cache successfully loaded for hysform: ' . $hysform_id);
        }

        $job->delete();
    }
}
