<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ParticipantEvents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create( strtolower( 'Participant_Events' ), function ( Blueprint $table ) {
            $table->id();

            $table->unsignedBigInteger( 'user_id' )->index();
            $table->foreign( 'user_id' )->references( 'user_id' )->on( 'users' );
            $table->unsignedBigInteger( 'event_id' )->index();
            $table->foreign( 'event_id' )->references( 'id' )->on( 'events' );
            $table->unsignedBigInteger( 'student_id' )->nullable();
            $table->foreign( 'student_id' )->references( 'id' )->on( 'students' );
            $table->string( 'payment_type', 100 );
            $table->text( 'data' )->nullable();

            $table->timestamps();
        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists( strtolower( 'Participant_Events' ) );
    }
}
