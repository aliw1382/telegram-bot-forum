<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UsersForms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create( strtolower( 'Users_Forms' ), function ( Blueprint $table ) {
            $table->id();

            $table->unsignedBigInteger( 'user_id' );
            $table->foreign( 'user_id' )->on( 'users' )->references( 'user_id' );
            $table->unsignedBigInteger( 'form_id' );
            $table->foreign( 'form_id' )->on( 'forms' )->references( 'id' );
            $table->longText( 'value' );

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
        Schema::dropIfExists( strtolower( 'Users_Forms' ) );
    }
}
