<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Payments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create( strtolower( 'Payment' ), function ( Blueprint $table ) {
            $table->id();

            $table->unsignedBigInteger( 'user_id' )->index();
            $table->foreign( 'user_id' )->references( 'user_id' )->on( 'users' );
            $table->string( 'transaction_id' )->index();
            $table->bigInteger( 'amount' );
            $table->string( 'ref_id' )->nullable();
            $table->text( 'detail' );
            $table->string( 'driver', 50 );

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
        Schema::dropIfExists( strtolower( 'Payment' ) );
    }
}
