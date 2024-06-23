<?php

namespace App\Providers;

use App\Models\ParticipantEvents;
use App\Models\Student;
use App\Models\UsersForm;
use App\Observers\ParticipantEventsObserver;
use App\Observers\ParticipateFormObserver;
use App\Observers\StudentObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    protected $observers = [
        Student::class           => [ StudentObserver::class ],
        ParticipantEvents::class => [ ParticipantEventsObserver::class ],
        UsersForm::class         => [ ParticipateFormObserver::class ]
    ];


    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
