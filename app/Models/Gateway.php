<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Omnipay\Common\GatewayFactory;

class Gateway extends Model
{

    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public function hasCC($client_id)
    {

        $gateway = Gateway::where('client_id', $client_id)->where('settings', '!=', '')->first();

        if (isset($gateway)) {
            return true;
        } else {
            return false;
        }
    }
}
