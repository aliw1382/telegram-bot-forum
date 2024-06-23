<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{

    protected $fillable = [ 'user_id', 'star', 'model', 'model_id', 'detail' ];

    protected $casts = [
        'detail' => 'array'
    ];

}
