<?php

namespace App\Models\ImageQueue;

use App\Models\Upload;
use Aws\Laravel\AwsFacade;
use Illuminate\Database\Eloquent\Model;

class deleteUpload extends Model
{

    public function fire($job, $data)
    {
        $s3 = AWS::get('s3');
            
        $upload_id= $data['upload_id'];

        $upload=Upload::withTrashed()->find($upload_id);

        if ($upload!=null) {
            $img = explode('.', $upload->name);
                
            if (count($img)>1) {
                $thumb = $img[0].'_t_.'.$img[1];
            } else {
                $thumb='';
            }

                $s3->deleteObjects([
                    'Bucket' => 'hys',
                    'Objects' => [
                        ['Key' => $thumb],
                        ['Key' => $upload->name]
                        ]]);
                        $upload->forceDelete();
        }
            
        $job->delete();
    }
}
