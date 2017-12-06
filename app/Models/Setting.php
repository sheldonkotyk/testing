<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Setting extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    
    public function programs()
    {
        return $this->hasMany('App\Models\Program', 'setting_id');
    }

    public function getFieldsFromSettings($setting_id)
    {

            //$settings= Setting::find($setting_id)->get();
            $field = new Field;

            $programs= Program::where('setting_id', $setting_id)->get();

            $hysform_ids=[];
        foreach ($programs as $p) {
            $hysform_ids[$p->hysform_id]=$field->getFields($p->hysform_id);
        }
            return $hysform_ids;
    }
}
