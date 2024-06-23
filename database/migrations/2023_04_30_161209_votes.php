<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Votes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create( strtolower( 'Votes' ), function ( Blueprint $table ) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->on('users')->references('user_id');

            $table->integer('star')->nullable();

            $table->string('model');
            $table->bigInteger('model_id');

            $table->longText('detail')->nullable();

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
        Schema::dropIfExists( strtolower( 'Votes' ) );
    }
}
