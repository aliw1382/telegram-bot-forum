<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Subscription extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create( strtolower( 'Subscriptions' ), function ( Blueprint $table ) {
            $table->id();

            $table->unsignedBigInteger( 'user_id' )->index();
            $table->foreign( 'user_id' )->on( 'users' )->references( 'user_id' );
            $table->bigInteger( 'day' )->default( 1 );
            $table->string( 'student_id' )->nullable();
            $table->string( 'password' )->nullable();

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
        Schema::dropIfExists( strtolower( 'Subscriptions' ) );
    }
}
