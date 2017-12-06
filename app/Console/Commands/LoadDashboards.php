<?php

namespace App\Console\Commands;

use App\Models\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class LoadDashboards extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'app:load-all-dashboards';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send all the client dashboards to the Queue for reloading.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {

        foreach (Client::all() as $client) {
            $data = ['client_id'=>$client->id];

            Cache::put('dashboard_reload-'.$client->id, 'Reloading Stats... Please Wait.', 5);

            Queue::push('reloadDashboardStats', $data);
        }
    }

    // /**
    //  * Get the console command arguments.
    //  *
    //  * @return array
    //  */
    // protected function getArguments()
    // {
    // 	return array(
    // 		array('url', InputArgument::REQUIRED, 'The URL for making correct links.'),
    // 	);
    // }

    // /**
    //  * Get the console command options.
    //  *
    //  * @return array
    //  */
    // protected function getOptions()
    // {
    // 	return array(
    // 		array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
    // 	);
    // }
}
