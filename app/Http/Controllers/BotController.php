<?php


namespace App\Http\Controllers;

use App\Exceptions\ExceptionAccess;
use App\Exceptions\ExceptionError;
use App\Exceptions\ExceptionWarning;
use App\helper\TelegramData;
use App\Http\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use MannikJ\Laravel\Wallet\Exceptions\UnacceptedTransactionException;
use Throwable;

class BotController extends Controller
{

    /**
     * @throws \App\Exceptions\ExceptionWarning
     * @throws Exception
     */
    public function manager() : JsonResponse
    {

        $updates    = file_get_contents( 'php://input' );
        $update_arr = json_decode( $updates, true );
        if ( ! isset( $update_arr[ 'update_id' ] ) ) die( 'NO ACCESS' );
        $update = json_decode( $updates );

        $update_keys = array_keys( $update_arr );
        if ( isset( $update_keys[ 1 ] ) )
        {

            $type = implode( '', array_map( 'ucfirst', explode( '_', $update_keys[ 1 ] ) ) );

            $data = new TelegramData( $update );
            if ( isset( $data->text ) && $data->text == '/id' )
            {
                tel()->sendMessage( $data->chat_id, $data->chat_id );
                exit();
            }

            if ( class_exists( 'App\Http\Controllers\\' . ucfirst( $type ) . 'Controller' ) )
            {


                $type_message = $data->type;

                if ( isset( $type_message ) && method_exists( ( 'App\Http\Controllers\\' . ucfirst( $type ) . 'Controller' ), $type_message ) )
                {

                    try
                    {

                        $class = new ( 'App\Http\Controllers\\' . ucfirst( $type ) . 'Controller' )( $update );
                        call_user_func( [ $class, $type_message ], $update );

                    }
                    catch ( UnacceptedTransactionException $e )
                    {

                        $message = '⚠️ خطا، ' . 'موجودی شما کافی نمی باشد.';
                        tel()->sendMessage( ( $data->chat_id ?? $data->chatid ), $message );


                    }
                    catch ( ExceptionWarning $exception )
                    {

                        $message = '⚠️ خطا، ' . $exception->getMessage();
                        tel()->sendMessage( ( $data->chat_id ?? $data->chatid ), $message );


                    }
                    catch ( ExceptionError $exception )
                    {

                        $message = '❌خطا، ' . $exception->getMessage();
                        tel()->sendMessage( ( $data->chat_id ?? $data->chatid ), $message );


                    }
                    catch ( ExceptionAccess $exception )
                    {

                        $message = '⛔️خطا، ' . $exception->getMessage();
                        tel()->sendMessage( ( $data->chat_id ?? $data->chatid ), $message );

                    }
                    catch ( Exception | Throwable $e )
                    {

                        $message = "<i>ERROR LINE: {" . $e->getLine() . "}</i>" . "\n \n";
                        $message .= "<i>ERROR User: {" . ( $data->chat_id ?? $data->chatid ) . "}</i>" . "\n \n";
                        $message .= "<u>ERROR ON FILE: {" . $e->getFile() . "}</u>" . "\n \n";
                        $message .= "<b>CONTACT ERROR: [" . $e->getMessage() . "]</b>";
                        tel()->sendMessage( env( 'ADMIN_LOG' ), $message );

                        if ( TelegramData::getUpdate()->type != 'channel' )
                        {
                            $message = '🔴 متاسفانه خطایی رخ داد،' . "\n \n" . '⚠️ گزارش خطا برای پشتیبانی ارسال شد. لطفا مجددا تلاش کنید🙏';
                            tel()->sendMessage( ( $data->chat_id ?? $data->chatid ), $message );
                        }

                    }
                    finally
                    {

                        return response()->json( [
                            'status' => 'error',
                        ] );

                    }

                }


            }

        }


        return response()->json( [
            'status' => 'success',
            'method' => $type_message ?? 'none'
        ] );

    }


    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function stats()
    {
        $votes = \App\Models\Vote::join( 'forms', function ( \Illuminate\Database\Query\JoinClause $join ) {

            $join->on( 'forms.id', 'votes.model_id' );

        } )->select( [
            'votes.model_id',
            'votes.star',
            'forms.*'
        ] )->groupBy( 'votes.model_id' )->get();

        $stats = collect();

        foreach ( $votes as $vote )
        {

            $v = \App\Models\Vote::where( 'model_id', $vote->model_id );
            $f = \App\Models\Form::withTrashed()->find( $vote->model_id );

            $stats->add( [
                $vote->name => [
                    'avg'         => round( $v->avg( 'star' ), 1 ),
                    'count'       => $v->count(),
                    'participate' => $f->users->count(),
                    'date'        => \Morilog\Jalali\Jalalian::forge( strtotime( $f->updated_at ) )->format( 'Y/m/d H:i:s' )
                ]
            ] );

        }

        return view( 'stats', [
            'stats' => $stats
        ] );
    }

}
