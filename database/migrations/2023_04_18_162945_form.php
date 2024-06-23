<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Form extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create( strtolower( 'Forms' ), function ( Blueprint $table ) {
            $table->id();

            $table->string( 'hash', 50 );
            $table->unsignedBigInteger( 'user_id' );
            $table->foreign( 'user_id' )->on( 'users' )->references( 'user_id' );
            $table->bigInteger( 'message_id' );
            $table->string( 'name' );
            $table->longText( 'questions' );
            $table->integer( 'status' )->default( 1 );

            $table->timestamps();
            $table->softDeletes();
        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists( strtolower( 'Forms' ) );
    }
}
