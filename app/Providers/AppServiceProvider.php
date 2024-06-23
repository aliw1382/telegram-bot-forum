<?php

namespace App\Providers;

use App\Models\ParticipantEvents;
use App\Models\Student;
use App\Models\UsersForm;
use App\Observers\ParticipantEventsObserver;
use App\Observers\ParticipateFormObserver;
use App\Observers\QrObserver;
use App\Observers\StudentObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        if ( env( 'APP_USE_HTTPS' ) ) \URL::forceScheme( 'https' );


        ParticipantEvents::observe( ParticipantEventsObserver::class );
        UsersForm::observe( ParticipateFormObserver::class );

    }
}
