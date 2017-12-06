<?php

namespace App\Models\ImageQueue;

use App\Models\Donor;
use App\Models\Entity;
use App\Models\Upload;
use Aws\Laravel\AwsFacade;
use Illuminate\Database\Eloquent\Model;

class cullUpload extends Model
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
                if ($upload->entity_id!='') {
                    $entity=Entity::withTrashed()->find($upload->entity_id);
                    if ($entity==null) {
                        $upload->delete();
                    }
                }
                if ($upload->donor_id!='') {
                    $donor=Donor::withTrashed()->find($upload->donor_id);
                    if ($donor==null) {
                        $upload->delete();
                    }
                }
            }
        }

        $job->delete();
    }
}
