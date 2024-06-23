<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{

    protected $fillable = [ 'user_id', 'day' ];
    const PLANS = [
        [
            'name'   => '1 رزرو',
            'amount' => 5000,
            'day'    => 1
        ],
        [
            'name'   => '2 رزرو',
            'amount' => 9000,
            'day'    => 2
        ],
        [
            'name'   => '3 رزرو',
            'amount' => 14000,
            'day'    => 3
        ],
    ];

    protected $casts = [
        'payment' => 'array'
    ];

}
