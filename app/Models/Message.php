<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [ 'contact', 'title', 'name' ];

    /**
     * @param string $name
     * @param array $replace
     * @return string
     */
    public static function get( string $name, array $replace = [] ) : string
    {
        return self::replaceContent( $replace, ( self::where( 'name', $name )->first()->contact ?? 'چیزی یافت نشد' ) );
    }

    /**
     * @param array $replace
     * @param string $string
     * @return string
     */
    private static function replaceContent( array $replace, string $string ) : string
    {
        foreach ( $replace as $index => $item )
        {
            $string = str_replace( $index, $item, $string );
        }
        return $string;
    }

}
