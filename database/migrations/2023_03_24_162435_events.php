<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Events extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() : void
    {
        Schema::create( strtolower( 'Events' ), function ( Blueprint $table ) {
            $table->id();

            $table->unsignedBigInteger( 'user_id' )->index();
            $table->foreign( 'user_id' )->references( 'user_id' )->on( 'users' );
            $table->string( 'hash', 50 );
            $table->string( 'title' );
            $table->text( 'topics' )->nullable();
            $table->string( 'file_id' );
            $table->string( 'teacher_name' )->nullable();
            $table->bigInteger( 'amount' )->default( 0 );
            $table->integer( 'type' )->default( 1 );
            $table->text( 'description' )->nullable();
            $table->integer( 'count' )->nullable();
            $table->integer( 'free_login_user' )->default( 0 );
            $table->longText( 'data' )->nullable();
            $table->timestamp( 'available_at' );

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
        Schema::dropIfExists( strtolower( 'Events' ) );
    }
}
