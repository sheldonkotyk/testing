<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class Field extends Model
{
    
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    
    public function scopeallActiveEntityFields()
    {

        return  DB::table('fields AS t1')
        ->where('t1.client_id', Session::get('client_id'))
        ->where('t1.deleted_at', '=', null)
        ->leftJoin('hysforms AS t2', 't2.id', '=', 't1.hysform_id')
        ->where('t2.type', '=', 'entity')
        ->where('t2.deleted_at', '=', null)
        ->groupBy('t1.field_key')
        ->orderBy('t1.field_order');
    }
    
    /**
    * Return fields for hysform_id
    **/
    public function getFields($hysform_id)
    {
        $hysform = Hysform::find($hysform_id);
        
        if ($hysform->type == 'entity') {
            $fields = Field::whereHysformId($hysform_id)->orderBy('field_order')->get();
        }
        
        if ($hysform->type == 'donor') {
            $fields = Donorfield::whereHysformId($hysform_id)->orderBy('field_order')->get();
        }
        
        if ($hysform->type == 'submit') {
            $fields = Field::whereHysformId($hysform_id)->orderBy('field_order')->get();
        }
        
        return $fields;
    }
}
