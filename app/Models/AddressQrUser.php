<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class AddressQrUser extends Model
{

    protected $fillable = [
        'uuid',
        'user_id',
        'model',
        'model_id'
    ];

}
