<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class Donorfield extends Model
{

    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public function scopeallActiveDonorFields()
    {

            return  DB::table('donorfields AS t1')
            ->where('t1.client_id', Session::get('client_id'))
            ->where('t1.deleted_at', '=', null)
            ->leftJoin('hysforms AS t2', 't2.id', '=', 't1.hysform_id')
            ->where('t2.type', '=', 'donor')
            ->where('t2.deleted_at', '=', null)
            ->groupBy('t1.field_key')
            ->orderBy('t1.field_order');
    }
}
