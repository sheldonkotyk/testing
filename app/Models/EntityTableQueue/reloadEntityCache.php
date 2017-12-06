<?php

namespace App\Models\EntityTableQueue;

use App\Models\Entity;
use App\Models\Program;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class reloadEntityCache extends Model
{
        
    public function fire($job, $data)
    {

        $message= '';
        $program_id=$data['program_id'];

        $count_entities = Entity::where('program_id', $program_id)->count();

        //Preload every program into the cache with more than 300 entities. Programs with less than this load quickly enough to not need preload.
        if ($count_entities> 300) {
            if (isset($data['url'])) {
                $url=$data['url'];
            }
                
            if (isset($data['reload'])) {
                $reload = $data['reload'];
            } else {
                $reload=['entities','data','frontend'];
            }


            if (isset($data['trashed_options'])) {
                $trashed_options = $data['trashed_options'];
            } else {
                $trashed_options=['','1','available','sponsored','unsponsored'];
            }

            $e = new Entity;

            try {
                //Empty out the caches related to the entities table
                $e->clearEntityCache($program_id, $reload, $trashed_options);
                $program = Program::find($program_id);

                //Refresh the Entities page
                if (in_array('entities', $reload)) {
                    foreach ($trashed_options as $trashed) {
                        $e->getEntities($program_id, $program->client_id, $trashed);
                    }
                }

                //Refresh the Entities Ajax Data that populates the table
                if (in_array('data', $reload)) {
                    foreach ($trashed_options as $trashed) {
                        $e->getEntitiesAjax($program_id, $url, $trashed);
                    }
                }
            } catch (Exception $e) {
                Log::error('There was an error loading the Entities table cache for program: ' . $program_id . ' Error: ' . $e->getMessage());
            }
        }


        $job->delete();
    }
}
