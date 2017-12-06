<?php

namespace App\Models\ImageQueue;

use App\Models\Upload;
use Aws\Laravel\AwsFacade;
use Illuminate\Database\Eloquent\Model;
use Intervention\Image\Facades\Image;

class makeThumbnail extends Model
{

    public function fire($job, $data)
    {
        $upload_id      = $data['upload_id'];
        $tempFileFolder= $data['tempFileFolder'];

        $u= Upload::find($upload_id);

        if (!empty($u)) {
            if ($u->type == 'image') {
                $img = explode('.', $u->name);
                $thumb_filename = $img[0].'_t_.'.$img[1];
                    
                $data = file_get_contents('https://s3-us-west-1.amazonaws.com/hys/'.$u->name.'');
                $img = Image::make($data)->fit(300, 300, null, 'top')->save($tempFileFolder.$thumb_filename, 90);
                $s3 = AWS::get('s3');
                $result=$s3->putObject([
                    'Bucket'     => 'hys',
                    'Key'        => $thumb_filename,
                    'SourceFile' => ''.$tempFileFolder.$thumb_filename.'',
                    'ACL'    => 'public-read-write',
                ]);

                unlink($tempFileFolder.$thumb_filename);

                $u->thumnail_exists='1';
            }
        }

        $job->delete();
    }
}
