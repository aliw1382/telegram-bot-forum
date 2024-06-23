<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UsersParticipatePart extends Model
{

    use SoftDeletes;

    protected $table = 'users_participate_part';

    protected $fillable = [
        'uuid',
        'ip',
        'agent',
        'model',
        'model_id',
        'type'
    ];

}
