<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Program;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class LoadSponsorshipsTable extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'app:load-all-sponsorships';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue preloading of Sponsorships table for all clients';


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $url = $this->argument('url');
        $trashed = ['','1'];


        foreach (Client::all() as $client) {
            foreach ($trashed as $t) {
                $data = [
                'client_id'=>$client->id,
                'url' => $url,
                'trashed' => $t];

                Queue::push('reloadSponsorshipsCache', $data);
            }

            //Clear out all program counts for sponsorship pages
            Cache::tags('sponsorships-'.$client->id)->flush();
            
            $programs = Program::where('client_id', $client->id)->get();

            foreach ($programs as $program) {
                foreach ($trashed as $t) {
                    $data = [
                    'program_id'=>$program->id,
                    'trashed' => $t];

                    Queue::push('reloadSponsorshipsByProgram', $data);
                }
            }
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        //Accept the url as the first argument. This allows us to create correct links.
        return [
            ['url', InputArgument::REQUIRED, 'The URL for making correct links.'],
        ];
    }
}
