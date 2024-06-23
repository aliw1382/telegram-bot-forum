<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property Event $event
 * @property User $user
 * @property Student $student
 * @method ParticipantEvents get()
 */
class ParticipantEvents extends Model
{

    use SoftDeletes;

    protected $fillable = [ 'user_id', 'event_id', 'data', 'payment_type', 'student_id' ];

    protected $casts = [
        'data' => 'array'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function event()
    {
        return $this->belongsTo( Event::class );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo( User::class, 'user_id', 'user_id' );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function student()
    {
        return $this->belongsTo( Student::class );
    }

    /**
     * @return User
     */
    public function stu()
    {
        return User::where( 'student_id', $this->student_id )->first();
    }

    /**
     * @param \App\helper\User $user
     * @param \App\helper\User $request_user
     * @param Event $event
     * @return void
     * @throws \Exception
     */
    public function participateUser( \App\helper\User $user, \App\helper\User $request_user, Event $event ) : void
    {

        $participant_event = $this;

        $message = '✔️ شما به عنوان هم تیمی ' . $participant_event->user->name . ' معرفی شدید🎉';
        $request_user->SendMessageHtml( $message )->registerEvent( $event, 'JoinTeam', [ 'event' => $participant_event->id ] );

        $user->SendMessageHtml( '😃 ' . $request_user->name . ' به تیم شما پیوست 🎉' );

        if ( ( $participant_event->data[ 'count' ] ?? 1 ) + 1 == $event->data[ 'count_team' ] )
        {
            $participant_event->data = array_merge( $participant_event->data, [

                'status' => 'ready'

            ] );
        }
        $participant_event->data = array_merge( $participant_event->data, [

            'count' => isset( $participant_event->data[ 'count' ] ) ? $participant_event->data[ 'count' ] + 1 : 2

        ] );
        $participant_event->save();

    }

}
