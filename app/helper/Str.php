<?php

namespace App\helper;

class Str
{

    /**
     * @param string $str
     * @return string
     */
    public static function b( string $str ) : string
    {
        return "<b>" . $str . "</b>";
    }

    /**
     * @param string $str
     * @param string $link
     * @return string
     */
    public static function a( string $str, string $link ) : string
    {
        return "<a href='{$link}'>{$str}</a>";
    }

    /**
     * @param string $str
     * @return string
     */
    public static function i( string $str ) : string
    {
        return "<i>" . $str . "</i>";
    }

    /**
     * @param string $str
     * @return string
     */
    public static function u( string $str ) : string
    {
        return "<u>" . $str . "</u>";
    }

    /**
     * @param string $str
     * @return string
     */
    public static function code( string $str ) : string
    {
        return "<code>" . $str . "</code>";
    }

    /**
     * @param string $str
     * @return string
     */
    public static function bu( string $str ) : string
    {
        return "<b><u>" . $str . "</u></b>";
    }

    /**
     * @param string $str
     * @return string
     */
    public static function bi( string $str ) : string
    {
        return "<b><i>" . $str . "</i></b>";
    }

    /**
     * @param string $str
     * @return string
     */
    public static function ui( string $str ) : string
    {
        return "<u><i>" . $str . "</i></u>";
    }

    /**
     * @param string $date
     * @return string
     * @throws \Exception
     */
    public static function date( string $date )
    {

        $date      = new \DateTime( $date );
        $date_now  = new \DateTime();
        $date_diff = $date->diff( $date_now );

        $time = '';
        if ( ! empty( $date_diff->format( '%d' ) ) ) $time .= $date_diff->format( '%d' ) . ' روز ';
        if ( ! empty( $date_diff->format( '%h' ) ) ) $time .= $date_diff->format( '%h' ) . ' ساعت ';
        if ( ! empty( $date_diff->format( '%i' ) ) ) $time .= $date_diff->format( '%i' ) . ' دقیقه ';
        if ( ! empty( $date_diff->format( '%s' ) ) ) $time .= $date_diff->format( '%s' ) . ' ثانیه ';

        return $time;

    }

    /**
     * @param string $str
     * @return string
     */
    public static function codeB( string $str )
    {
        return "<b><code>" . $str . "</code></b>";
    }

}
