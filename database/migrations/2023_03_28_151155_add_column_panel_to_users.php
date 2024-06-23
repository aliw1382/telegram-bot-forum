<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnPanelToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'users', function ( Blueprint $table ) {

            $table->string( 'panel', 100 )->default( 'user' )->after('student_id');

        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table( 'users', function ( Blueprint $table ) {

            $table->dropColumn( 'panel' );

        } );
    }
}
