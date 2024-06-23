<?php

use App\Broadcasts\PaymentMobileBroadcast;
use App\Http\Controllers\BotController;
use App\Http\Controllers\PaymentController;
use App\Models\AddressQrUser;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get( '/', function () {
    return view( 'welcome' );
} )->name( 'home' );


Route::get( '/help', function () {
    return view( 'welcome' );
} )->name( 'help' );


Route::get( '/install', function () {
    return view( 'welcome' );
} )->name( 'install' );


Route::post( '/bot/index.php', [ BotController::class, 'manager' ] );

Route::get( '/payment', [ PaymentController::class, 'verify' ] )->name( 'payment' );

Route::get( '/stats', [ BotController::class, 'stats' ] );
