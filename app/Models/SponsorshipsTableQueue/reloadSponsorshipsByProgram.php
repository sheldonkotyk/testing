<?php

namespace App\Models\SponsorshipsTableQueue;

use App\Models\Entity;
use App\Models\Program;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class reloadSponsorshipsByProgram extends Model
{
        
    public function fire($job, $data)
    {

        $program_id = $data['program_id'];
        $program= Program::find($program_id);

        $trashed = $data['trashed'];

        $e = new Entity;

        $key = 'showallsponsorships-'.$program_id.'-'.$trashed;

        Cache::forget($key);

        if (count($program)) {
            $e->getSponsorships($program->client_id, $program_id, $trashed);
        }

        // Log::info('Sponsorships Table Counts by Program Cache successfully loaded for program: '.$program_id);

        $job->delete();
    }
}
