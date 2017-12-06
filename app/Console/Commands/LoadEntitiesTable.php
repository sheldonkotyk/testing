<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Program;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class LoadEntitiesTable extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'app:load-all-entity-tables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue preloading of all entity tables for each client.';

    
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $url =$this->argument('url');
        
        foreach (Client::all() as $client) {
            $programs = Program::where('client_id', $client->id)->get();
            foreach ($programs as $program) {
                    $data = [
                        'client_id'=>$client->id,
                        'url'=>$url,
                        'program_id'=>$program->id];

                    Queue::push('reloadEntityCache', $data);
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
