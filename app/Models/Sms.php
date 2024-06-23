<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sms extends Model
{

    public $timestamps = false;

    protected $fillable = ['phone' , 'message','status','response'];

    protected $table = 'sms';

}
