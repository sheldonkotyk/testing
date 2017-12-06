<?php

namespace App\Models\SponsorshipsTableQueue;

use App\Models\Entity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class reloadSponsorshipsCache extends Model
{
        
    public function fire($job, $data)
    {

        $client_id=$data['client_id'];

        $url = $data['url'];

        $trashed = $data['trashed'];

        $e = new Entity;

        Cache::forget('sponsorshipsajax-'.$client_id.'-'.$trashed);

        $e->getSponsorshipsAjax($client_id, $trashed, $url);

        // Log::info('Sponsorships Table Cache successfully loaded for client: '.$client_id);

        $job->delete();
    }
}
