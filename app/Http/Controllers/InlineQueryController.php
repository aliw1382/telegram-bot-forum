<?php

namespace App\Http\Controllers;

use App\helper\Str;
use App\Helper\TelegramData;
use App\Models\Event;
use App\Models\ParticipantEvents;
use App\Models\Video;
use Illuminate\Support\Facades\URL;

class InlineQueryController extends TelegramData
{

    /**
     * @return void
     * @throws \Exception
     */
    public function index()
    {

        $telegram = telegram();

        $items = [];

        if ( date( 'Y-m-d H:i:s' ) < '2024-03-20 06:36:26' )
        {

            $date      = new \DateTime( '2024-03-20 06:36:26' );
            $date_now  = new \DateTime( 'now' );
            $date_diff = $date->diff( $date_now );

            $time = '';
            if ( ! empty( $date_diff->format( '%d' ) ) ) $time .= $date_diff->format( '%d' ) . ' روز ';
            if ( ! empty( $date_diff->format( '%h' ) ) ) $time .= $date_diff->format( '%h' ) . ' ساعت ';
            if ( ! empty( $date_diff->format( '%i' ) ) ) $time .= $date_diff->format( '%i' ) . ' دقیقه ';
            if ( ! empty( $date_diff->format( '%s' ) ) ) $time .= $date_diff->format( '%s' ) . ' ثانیه ';

            $message = '<b>🗓 چقدر به عید مونده؟</b>' . "\n \n";
            $message .= '<b>☑️ زمان تحویل سال 1403 به تاریخ شمسی: </b>' . "\n" . "<code>صبحِ روز چهارشنبه ۱ فروردینِ هجری خورشیدی</code>" . "\n \n";
            $message .= '⏰زمان باقی مانده: ' . "\n" . "<code>" . $time . "</code>" . "\n \n";
            $message .= '➖➖➖➖➖➖➖➖' . "\n" . '📣 @Montazeri_Computer';

            $items[] = [
                'type'                  => 'article',
                'id'                    => 1,
                'title'                 => 'چقدر به عید مونده؟',
                'input_message_content' => [
                    'message_text' => $message,
                    'parse_mode'   => 'html'
                ],
                'reply_markup'          => [
                    'inline_keyboard' => [
                        [
                            [ 'text' => '♻️ بررسی مجدد', 'callback_data' => 'update_time' ]
                        ],
                        [
                            [ 'text' => '↗️ اشتراک گذاری', 'switch_inline_query' => '' ]
                        ],
                    ]
                ],
                'thumb_url'             => 'https://pictoor.com/wp-content/uploads/2024/01/Pictoor.com-1403-Nw.jpg',
                'description'           => '🔸 برای نمایش ساعات باقی مانده از سال بروی این قسمت کلیک کنید.'

            ];

        }

        $events = Event::where( 'available_at', '>', date( 'Y-m-d 00:00:00' ) )->get();

        if ( count( $events ) > 0 )
        {

            foreach ( $events as $item )
            {

                switch ( $item->type )
                {

                    case 1:

                        $link = ( 'https://t.me/' . $telegram->getMe()[ 'result' ][ 'username' ] . '?start=' . $item->hash );

                        $message = '🎓 دوره:  ' . Str::b( $item->title ) . "\n \n";
                        $message .= '🧑‍🏫 مدرس: ' . Str::u( $item->teacher_name ) . "\n \n";
                        if ( ! empty( $item->topics ) ) $message .= $item->topics . "\n \n";
                        $message .= '👤 ظرفیت دوره: ' . Str::u( $item->count . ' نفر' ) . "\n \n";

                        if ( $item->available_at > date( 'Y-m-d' ) )
                        {
                            $message .= '🗓 زمان باقی مانده جهت ثبت نام: ' . "\n";
                            $message .= Str::b( Str::date( $item->available_at ) ) . "\n \n";
                        }
                        else
                        {
                            $message .= '❌ مهلت ثبت نام تمام شده است.' . "\n \n";
                        }

                        $message .= '👇جهت دریافت اطلاعات بیشتر در مورد دوره بر روی دکمه زیر کلیک کنید👇' . "\n";
                        $message .= $link . "\n \n";
                        $message .= '📣 @Montazeri_Computer';

                        $items[] = [

                            'type'          => 'photo',
                            'id'            => $item->id,
                            'title'         => $item->title,
                            'caption'       => $message,
                            'photo_file_id' => $item->file_id,
                            'reply_markup'  => [
                                'inline_keyboard' => [
                                    [
                                        $telegram->buildInlineKeyboardButton( '🤝 شرکت در دوره 🤝', $link )
                                    ],
                                    [
                                        [ 'text' => '↗️ اشتراک گذاری', 'switch_inline_query' => '' ]
                                    ]
                                ]
                            ],
                            'description'   => $item->description,
                            'parse_mode'    => 'html'

                        ];

                        break;

                    case 2:

                        $link = ( 'https://t.me/' . $telegram->getMe()[ 'result' ][ 'username' ] . '?start=' . $item->hash );

                        $count   = ParticipantEvents::where( 'event_id', $item->id )->count();
                        $message = '🏆 ' . Str::b( $item->title ) . "\n \n";
                        if ( ! empty( $item->topics ) ) $message .= $item->topics . "\n \n";
                        if ( ! empty( $item->teacher_name ) ) $message .= '🤝 حامیان مسابقات : ' . Str::bu( $item->teacher_name ) . "\n";
                        $message .= '💰 هزینه شرکت در مسابقه : ' . Str::b( number_format( $item->amount ) . ' تومان' ) . ' ' . ( in_array( $item->free_login_user, [ 1, 2 ] ) ? Str::bu( '( برای دانشجویان دانشکده منتظری رایگان )' ) : '' ) . "\n";
                        $message .= '👤 ظرفیت مسابقه : ' . Str::u( $item->count . ' ' . ( $item->data[ 'type_join' ] == 2 ? 'تیم' : 'نفر' ) ) . "\n";
                        $message .= '🗓 زمان باقی مانده جهت ثبت نام:' . "\n";
                        $message .= Str::b( Str::date( $item->available_at ) ) . "\n \n";
                        $message .= '👇جهت دریافت اطلاعات بیشتر در مورد دوره بر روی دکمه زیر کلیک کنید👇' . "\n";
                        $message .= $link . "\n \n";
                        $message .= '📣 @montazeri_computer';

                        $items[] = [

                            'type'          => 'photo',
                            'id'            => $item->id,
                            'title'         => $item->title,
                            'caption'       => $message,
                            'photo_file_id' => $item->file_id,
                            'reply_markup'  => [
                                'inline_keyboard' => [
                                    [
                                        $telegram->buildInlineKeyboardButton( '🎯 شرکت در مسابقه 🎮', $link )
                                    ],
                                    [
                                        [ 'text' => '↗️ اشتراک گذاری', 'switch_inline_query' => '' ]
                                    ]
                                ]
                            ],
                            'description'   => $item->description,
                            'parse_mode'    => 'html'

                        ];

                        break;

                }


            }

        }

        /*$items[] = [

            'type'          => 'photo',
            'id'            => 'game',
            'title'         => '🎮 بازی سازی',
            'caption'       => '🎗 واحد ارتباط با صنعت انجمن علمی کامپیوتر دانشکده منتظری مشهد:

💡 انجمن علمی کامپیوتر منتظری قصد دارد <b>با همکاری دیگر انجمن های کامپیوتر مشهد</b> و <b>یکی از بهترین شرکت های بازی سازی خراسان</b> یک برنامه به صورت مشترک در حوزه 🎮 بازی سازی برگزار کند و برای این موضوع نیازمندیم بدانیم چه تعداد از دوستان به حوزه بازی سازی علاقه‌مند هستند به همین منظور، لطفاً در نظر سنجی زیر شرکت کنید🙏',
            'photo_file_id' => 'AgACAgQAAxkBAAKYX2T7UKyaze9_8D02ZrPTs1z1qi5qAALKvTEbDOfhUwABY9q4ZJZWNwEAAwIAA3MAAzAE',
            'reply_markup'  => [
                'inline_keyboard' => [
                    [
                        $telegram->buildInlineKeyboardButton( '👈 ' . 'بازی ساز هستم', '', 'answer_vote_game-1' )
                    ],
                    [
                        $telegram->buildInlineKeyboardButton( '👈 ' . 'به بازی سازی علاقه دارم و دوست دارم آشنا شوم', '', 'answer_vote_game-2' )
                    ],
                    [
                        $telegram->buildInlineKeyboardButton( '👈 ' . 'علاقه ای به این حوزه ندارم', '', 'answer_vote_game-3' )
                    ],
                    [
                        [ 'text' => '↗️ اشتراک گذاری', 'switch_inline_query' => '' ]
                    ]
                ]
            ],
            'parse_mode'    => 'html'

        ];*/

        $telegram->answerInlineQuery( $this->inline_query->id, json_encode( $items ), [
            'cache_time' => 1,
        ] );

    }
}
