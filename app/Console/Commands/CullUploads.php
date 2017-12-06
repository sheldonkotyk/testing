<?php

namespace App\Console\Commands;

use App\Models\Upload;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CullUploads extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'app:cull-uploads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'remove deleted upload files and records (removes files from s3 as well). Also deletes files with no owners.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {

        //This command will utterly delete all deleted files.
        //This command also moves orphaned images to be deleted, so the next time you run this function, these files will be utterly deleted.
        //If run monthly, this command will keep your s3 bucket from filling up with unused files!

        //Remove all deleted files!
        foreach (Upload::onlyTrashed()->get() as $upload) {
            $data=['upload_id'=>$upload->id];
            Queue::push('deleteUpload', $data);
        }

        // Remove files with missing entities and missing donors!
        foreach (Upload::withTrashed()->get() as $upload) {
            $data=['upload_id'=>$upload->id];
            Queue::push('cullUpload', $data);
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
