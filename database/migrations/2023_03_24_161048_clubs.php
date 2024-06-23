<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Clubs extends Migration
{

    public function up() : void
    {
        Schema::create( 'clubs', function ( Blueprint $table ) {
            $table->id();

            $table->integer( 'user_id' );

            $table->timestamps();
        } );
    }

    public function down() : void
    {
        Schema::dropIfExists( 'clubs' );
    }

}
