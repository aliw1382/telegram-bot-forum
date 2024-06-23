<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    protected $fillable = [ 'count', 'amount', 'data', 'available_at', 'description', 'file_id', 'title', 'topics', 'type', 'user_id', 'free_login_user', 'teacher_name', 'hash' ];

    protected $casts = [
        'data' => 'array'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function participate()
    {
        return $this->hasMany( ParticipantEvents::class, 'event_id', 'id' );
    }

}
