<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Sadegh19b\LaravelPersianValidation\PersianValidators;

/**
 * @property Section $section
 * @property User $user
 */
class Student extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $fillable = [

        'students_id',
        'first_name',
        'last_name',
        'national_code',
        'uni_id',
        'section_id'

    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function uni() : \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo( University::class, 'uni_id' );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function section() : \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo( Section::class, 'section_id' );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne( User::class, 'student_id', 'id' );
    }

}
