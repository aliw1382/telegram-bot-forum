<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     */
    public function register()
    {
        $this->reportable( function ( Throwable $e ) {

            /*$message = "<i>ERROR LINE: {" . $e->getLine() . "}</i>" . "\n \n";
            $message .= "<u>ERROR ON FILE: {" . $e->getFile() . "}</u>" . "\n \n";
            $message .= "<b>CONTACT ERROR: [" . $e->getMessage() . "]</b>";
            telegram()->sendMessage( 120545527, $message );*/

            if ( $this->isHttpException( $e ) )
            {
                if ( $e->getStatusCode() == 404 )
                {
                    return response()->view( '404', [], 404 );
                }
            }

        } );
    }
}
