<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Session;

class Hysform extends Model
{

    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public function countDonors($hysform_id)
    {
        $client_id=Session::get('client_id');

        return count(Donor::whereClientId($client_id)->whereHysformId($hysform_id)->get());
    }

    public function getFormType($hysform_id)
    {

        $hysform=Hysform::find($hysform_id);

        $type_name='';

        if ($hysform->type=='entity') {
            $type_name='Recipient Profile';
        }

        if ($hysform->type=='donor') {
            $type_name='Donor Profile';
        }
        
        if ($hysform->type=='submit') {
            $type_name='Progress Report';
        }

        return $type_name;
    }
}
