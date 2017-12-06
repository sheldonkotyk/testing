<?php

namespace App\Models\DashboardStatsQueue;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class reloadDashboardStats extends Model
{
        
    public function fire($job, $data)
    {

        $client_id      = $data['client_id'];
        $client         = new Client;

        $client->load_stats($client_id, '1');

        Cache::put('dashboard_reload-'.$client_id, 'done', '5');

        Log::info('dashboard stats successfully loaded for client: ' . $client_id);

        $job->delete();
    }
}
