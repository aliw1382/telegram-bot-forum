<?php

namespace App\Observers;

use App\Models\Student;
use Illuminate\Support\Facades\Hash;
use Sadegh19b\LaravelPersianValidation\PersianValidators;

class StudentObserver
{
    /**
     * Handle the Student "created" event.
     *
     * @param \App\Models\Student $student
     * @return void
     */
    public function created( Student $student )
    {
        //
    }

    /**
     * Handle the Student "updated" event.
     *
     * @param \App\Models\Student $student
     * @return void
     */
    public function updated( Student $student )
    {

        $persian_validation = new PersianValidators();

        if ( $persian_validation->validateIranianNationalCode( '', $student->national_code, '' ) )
        {

            $student->national_code = Hash::make( $student->national_code );
            $student->save();

        }


    }

    /**
     * Handle the Student "deleted" event.
     *
     * @param \App\Models\Student $student
     * @return void
     */
    public function deleted( Student $student )
    {
        //
    }

    /**
     * Handle the Student "restored" event.
     *
     * @param \App\Models\Student $student
     * @return void
     */
    public function restored( Student $student )
    {
        //
    }

    /**
     * Handle the Student "force deleted" event.
     *
     * @param \App\Models\Student $student
     * @return void
     */
    public function forceDeleted( Student $student )
    {
        //
    }
}
