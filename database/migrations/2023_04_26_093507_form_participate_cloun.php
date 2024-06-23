<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FormParticipateCloun extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'forms', function ( Blueprint $table ) {

            $table->bigInteger( 'participate' )->after( 'send_to' )->nullable();

        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropColumns( 'forms', [ 'participate' ] );
    }
}
