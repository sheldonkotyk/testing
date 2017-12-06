<?php namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\LoadDashboards',
        'App\Console\Commands\LoadSponsorshipsTable',
        'App\Console\Commands\LoadEntitiesTable',
        'App\Console\Commands\Inspire',
        'App\Console\Commands\ClearBeanstalkdQueueCommand',
        'App\Console\Commands\LoadDonorsTable',
        'App\Console\Commands\CullUploads',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('inspire')
                 ->hourly();
    }
}
