<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UsersParticipatePart extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create( 'users_participate_part', function ( Blueprint $table ) {
            $table->id();

            $table->string( 'uuid', 30 )->unique();
            $table->string( 'type', 30 );
            $table->ipAddress( 'ip' );
            $table->string( 'agent' );

            $table->string( 'model' )->nullable();
            $table->bigInteger( 'model_id' )->nullable();

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
        Schema::dropIfExists( strtolower( 'Users_Participate_Part' ) );
    }
}
