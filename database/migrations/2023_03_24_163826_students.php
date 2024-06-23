<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Students extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create( strtolower( 'Students' ), function ( Blueprint $table ) {
            $table->id();

            $table->string( 'students_id', 30 )->index();
            $table->string( 'first_name', 100 );
            $table->string( 'last_name', 100 );
            $table->string( 'national_code' );
            $table->unsignedBigInteger( 'uni_id' );
            $table->foreign( 'uni_id' )->references( 'id' )->on( 'universities' );
            $table->timestamp( 'login_at' )->nullable();
            $table->softDeletes();

        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists( strtolower( 'Students' ) );
    }
}
