<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Admins extends Migration
{

    /**
     * @return void
     */
    public function up() : void
    {
        Schema::create( 'admins', function ( Blueprint $table ) {
            $table->id();

            $table->integer( 'user_id' );
            $table->string( 'status', 20 );
            $table->string( 'role', 50 );

            $table->timestamps();
        } );
    }

    /**
     * @return void
     */
    public function down() : void
    {
        Schema::dropIfExists( 'admins' );
    }

}
