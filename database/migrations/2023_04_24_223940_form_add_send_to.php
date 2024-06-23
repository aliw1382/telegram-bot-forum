<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FormAddSendTo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'forms', function ( Blueprint $table ) {

            $table->string( 'send_to', 50 )->after('status')->default( env( 'CHANNEL_LOG' ) );

        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropColumns( 'forms', [ 'send_to' ] );
    }
}
