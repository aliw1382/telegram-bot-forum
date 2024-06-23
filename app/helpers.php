<?php

use App\Exceptions\ExceptionError;
use App\helper\TelegramBot;
use App\helper\User;

/**
 * @return TelegramBot
 * @throws Exception
 */
function tel() : TelegramBot
{
    global $telegramClass;

    if ( $telegramClass instanceof TelegramBot ) return $telegramClass;
    return $telegramClass = new TelegramBot( env( 'TOKEN_API' ) );

}

if ( ! function_exists( 'telegram' ) )
{

    /**
     * @return TelegramBot
     * @throws Exception
     */
    function telegram() : TelegramBot
    {
        return new TelegramBot( env( 'TOKEN_API' ) );
    }

}

/**
 * @param $str
 * @return string
 */
function string_encode( $str ) : string
{
    $encode = new Base2n( 5 );
    return $encode->encode( $str );
}

/**
 * @param $str_encode
 * @return string|null
 */
function string_decode( $str_encode ) : ?string
{
    $encode = new Base2n( 5 );
    return $encode->decode( $str_encode );
}

/**
 * @param int $user_id
 * @return User
 */
function User( int $user_id = 0 ) : User
{
    if ( $user_id == 0 )
    {
        $data    = \App\helper\TelegramData::getUpdate();
        $user_id = $data->from_id ?? $data->fromid;
    }
    return new User( $user_id );
}

/**
 * @param $str
 * @param string $mod
 * @param string $mf
 * @return array|string|string[]
 */
function tr_num( $str, string $mod = 'en', string $mf = '٫' )
{
    $num_a = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.' );
    $key_a = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', $mf );
    return ( $mod == 'fa' ) ? str_replace( $num_a, $key_a, $str ) : str_replace( $key_a, $num_a, $str );
}
