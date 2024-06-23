<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Files extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create( strtolower( 'Files' ), function ( Blueprint $table ) {
            $table->id();

            $table->string( 'hash' );
            $table->unsignedBigInteger( 'user_id' )->index();
            $table->foreign( 'user_id' )->on( 'users' )->references( 'user_id' );
            $table->bigInteger( 'message_id' );

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
        Schema::dropIfExists( strtolower( 'Files' ) );
    }
}
