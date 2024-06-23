<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Form extends Model
{

    use SoftDeletes;

    protected $fillable = [ 'user_id', 'message_id', 'name', 'hash', 'questions', 'status', 'send_to' ];

    protected $casts = [
        'questions' => 'array'
    ];

    public const STATUS_PUBLIC = 1;

    public const STATUS_DELETED = 0;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users() : \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany( UsersForm::class, 'form_id', 'id' );
    }

}
