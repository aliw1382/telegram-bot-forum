<?php

namespace App\Http\Controllers;

use App\Exports\EventExport;
use App\Exports\UsersFormExport;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;

class ExcelController extends Controller
{

    /**
     * @param string $hash
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function usersForm( string $hash )
    {
        return Excel::download( new UsersFormExport( $hash ), 'form-' . $hash . '-' . date( 'Y-m-d H-i-s' ) . '.xlsx' );
    }

    /**
     * @param string $hash
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function Event( string $hash )
    {
        return Excel::download( new EventExport( $hash ), 'event-' . $hash . '-' . date( 'Y-m-d H-i-s' ) . '.xlsx' );
    }

}
