<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use MannikJ\Laravel\Wallet\Traits\HasWallet;


class User extends Model
{
    use HasWallet;

    public $timestamps = false;

    protected $table = 'users';

    /**
     * @var string[]
     */
    protected $casts = [
        'data' => 'array'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'status',
        'data',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function uni() : \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo( Student::class, 'student_id' );
    }

}
