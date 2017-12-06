<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Session;

class Group extends Model
{
    protected $fillable = ['name'];
    
    use SoftDeletes;

    protected $dates = ['deleted_at'];


    public function areAllProgramsSet($client_id, $group_id)
    {

        $programs = Program::where('client_id', Session::get('client_id'))->where('lft', '>', 1)->orderBy('lft')->get();
        $group= Group::find($group_id);

        if ($group!=null) {
            $perms= json_decode($group->permissions, true);
            foreach ($programs as $program) {
                if (!isset($perms['program-'.$program->id])) {
                    return false;
                }
            }
            return true;
        } else {
            return false;
        }
    }

    //We don't seem to need this one.
    // public function areAllDonorsSet($client_id,$group_id)
    // {

    // }
}
