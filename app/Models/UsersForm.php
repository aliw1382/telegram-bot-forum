<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UsersForm extends Model
{

    use SoftDeletes;

    protected $fillable = [ 'user_id', 'form_id', 'value' ];

    protected $casts = [
        'value' => 'array'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function form() : \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo( Form::class );
    }

}
