<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Menus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create( strtolower( 'Menus' ), function ( Blueprint $table ) {
            $table->id();

            $table->bigInteger( 'parent' )->default( 0 )->index();
            $table->integer( 'row' );
            $table->integer( 'col' );
            $table->string( 'name' );
            $table->unsignedBigInteger( 'user_id' )->nullable();
            $table->foreign( 'user_id' )->references( 'user_id' )->on( 'users' );
            $table->bigInteger( 'message_id' )->nullable();
            $table->text( 'message' )->nullable();
            $table->integer( 'type' )->default( 1 );

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
        Schema::dropIfExists( strtolower( 'Menus' ) );
    }
}
