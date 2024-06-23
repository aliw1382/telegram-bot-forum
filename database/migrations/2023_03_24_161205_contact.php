<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Contact extends Migration
{

    /**
     * @return void
     */
    public function up() : void
    {
        Schema::create( 'contacts', function ( Blueprint $table ) {

            $table->id();

            $table->bigInteger( 'user_id' );
            $table->string( 'phone', 20 );

            $table->timestamp( 'created_at' );

        } );
    }

    /**
     * @return void
     */
    public function down() : void
    {
        Schema::dropIfExists( 'contacts' );
    }

}
