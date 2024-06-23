<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Users extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create( strtolower( 'Users' ), function ( Blueprint $table ) {
            $table->id();

            $table->unsignedBigInteger( 'user_id' )->unique()->index();
            $table->string( 'name' )->nullable();
            $table->string( 'status' )->nullable();
            $table->integer( 'step' )->nullable();
            $table->text( 'data' )->nullable();
            $table->unsignedBigInteger( 'student_id' )->nullable();
            $table->foreign( 'student_id' )->references( 'id' )->on( 'students' );
            $table->string( 'reserve_status' )->default( 'off' );
            $table->timestamp( 'created_at' );

        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists( strtolower( 'Users' ) );
    }
}
