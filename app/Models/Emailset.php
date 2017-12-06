<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;

class Emailset extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public function getEmailSets($hysform_id)
    {
        $programs = Program::where('donor_hysform_id', $hysform_id)->get();
        $hysform = Hysform::find($hysform_id);
        $emailset_ids= [];
        foreach ($programs as $p) {
            $emailset_ids[] = $p->emailset_id;
        }
        if (empty($emailset_ids)) {
            Session::flash('message', 'Error: Program configuration incomplete.<br/>You cannot add Donors to a Donor Form that is not attached to any programs.<br/>'.
            'You must attach this Donor Form to a program by going to <a href="'.URL::to("admin/manage_program").'">Setup Programs</a> and click Config on your desired program,<br/> then attach this form by selecting "'.$hysform->name.'" under "Attach Donor Form" and clicking save.');
            Session::flash('alert', 'danger');
            return false;
        }

        $emailset_ids = array_unique($emailset_ids);
        
        $emailsets = [];
        foreach ($emailset_ids as $eid) {
            if ($eid != 0) {
                $es = Emailset::find($eid);
                if ($es) {
                    $emailsets[$es->id] = ['id' => $es->id, 'name' => $es->name];
                }
            }
        }

        $emailset = reset($emailsets);

        if ($hysform->default_emailset_id) {
            if (isset($emailsets[$hysform->default_emailset_id])) {
                $emailset=$emailsets[$hysform->default_emailset_id];
            }
        }

        $templates= Emailtemplate::where('emailset_id', $emailset['id'])->get();
        $active_triggers= [];
        foreach ($templates as $template) {
            $active_triggers[]=$template->trigger;
        }



        return ['emailsets'=>$emailsets,'default_emailset'=>$emailset,'active_triggers'=>$active_triggers];
    }
}
