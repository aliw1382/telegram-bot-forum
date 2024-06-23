<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{

    protected $table = 'payment';

    protected $fillable = [ 'user_id', 'transaction_id', 'amount', 'ref_id', 'detail', 'driver' ];

    protected $casts = [
        'detail' => 'array'
    ];


}
