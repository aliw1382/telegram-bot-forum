<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SectionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create( strtolower( 'Sections' ), function ( Blueprint $table ) {
            $table->id();

            $table->string( 'name' );
            $table->integer( 'code' )->nullable();

            $table->timestamps();
        } );

        Schema::table( 'students', function ( Blueprint $table ) {

            $table->integer( 'section_id' )->after( 'uni_id' )->default( 1 );
            $table->foreign( 'section_id' )->on( 'sections' )->references( 'id' );

        } );

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists( strtolower( 'Sections' ) );
        Schema::dropColumns( 'students', 'section_id' );
    }
}
