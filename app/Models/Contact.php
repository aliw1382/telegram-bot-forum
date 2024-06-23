<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{

    protected $fillable = [ 'user_id', 'phone' ];

    public $timestamps = false;

    /**
     * @param string $phone
     * @return bool
     */
    public static function exstits( string $phone ) : bool
    {
        return (bool) Contact::where('phone', $phone)->exists();
    }

}
