<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class DonorEntity extends Model
{

    use SoftDeletes;

    protected $dates = ['deleted_at'];
    
    protected $table = 'donor_entity';

    // public function scopeallEntitiesByProgramId($program_id)
    // {

    // 	return  DB::table('donor_entity AS t1')
    //     ->where('t1.client_id', Session::get('client_id'))
    //     ->where('t1.deleted_at','=',null)
    //     ->leftJoin('hysforms AS t2', 't2.id', '=', 't1.hysform_id')
    //     ->where('t2.type','=','entity')
    //     ->where('t2.deleted_at','=',null)
    //     ->groupBy('t1.field_key')
    //     ->orderBy('t1.field_order');
        
    // }

    public function commitment()
    {
        return $this->hasOne('App\Models\Commitment');
    }

    public function scopeallEntitiesByProgram($program_id)
    {

        return  DB::table('donor_entity AS t1')
        ->where('t1.client_id', Session::get('client_id'))
        ->where('t1.deleted_at', '=', null)
        ->leftJoin('hysforms AS t2', 't2.id', '=', 't1.hysform_id')
        ->where('t2.type', '=', 'entity')
        ->where('t2.deleted_at', '=', null)
        ->groupBy('t1.field_key')
        ->orderBy('t1.field_order');
    }
}
