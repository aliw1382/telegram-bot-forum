<?php

namespace App\Http\Controllers;

use App\helper\TelegramData;
use App\Models\Menu;
use Illuminate\Http\Request;
use JetBrains\PhpStorm\Pure;

class ToolsController extends TelegramData
{

    /**
     * @return bool
     */
    protected function is_text() : bool
    {
        return ! empty( $this->text );
    }

    /**
     * @return bool
     */
    protected function is_number()
    {
        return $this->is_text() && is_numeric( $this->text );
    }

    /**
     * @return bool
     */
    protected function is_photo()
    {
        return isset( $this->photo0_id ) && empty( $this->text );
    }

    /**
     * @param int $parent
     * @param bool $back
     * @return string
     * @throws \Exception
     */
    protected function menus( int $parent = 0, bool $back = true ) : string
    {
        $tel = telegram();

        $keyboard = [];
        $menus    = Menu::on()->where( 'parent', $parent )->orderBy( 'row' )->orderBy( 'col' )->get();

        $i    = 0;
        $x    = 0;
        $temp = - 1;

        foreach ( $menus as $item )
        {

            if ( $temp == - 1 ) $temp = $item->row;
            if ( $item->row != $temp )
            {
                $i ++;
                $x    = 0;
                $temp = $item->row;
            }

            $keyboard[ $i ][ $x ++ ] = $tel->buildKeyboardButton( $item->name );

        }

        if ( $back && $parent > 1 && count( $keyboard ) > 0 ) $keyboard[][] = $tel->buildKeyboardButton( '▶️ برگشت به منو اصلی' );

        return $tel->buildKeyBoard( $keyboard );


    }

    /**
     * @return false|string
     */
    protected function adminMenu()
    {
        return KEY_ADMIN_START_MENU;
    }

    /**
     * @return \Illuminate\Contracts\Cache\Repository|mixed
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function userMenu()
    {

        $name = 'bot_menu';
        if ( ! cache()->has( $name ) )
        {
            cache()->put( $name, $this->menus( 1 ), now()->addMinutes( 60 ) );
        }
        return cache()->get( $name );

    }

    /**
     * @param int $menu_id
     * @return \Illuminate\Contracts\Cache\Repository|mixed
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function loadMenu( int $menu_id )
    {

        $name = 'bot_menu_' . $menu_id;
        if ( ! cache()->has( $name ) )
        {
            cache()->put( $name, $this->menus( $menu_id ), now()->addMinutes( 60 ) );
        }

        return cache()->get( $name );

    }

}
