<?php

namespace App\Http\Controllers;


use App\Exceptions\ExceptionAccess;
use App\Exceptions\ExceptionError;
use App\Exceptions\ExceptionWarning;
use App\helper\Payment;
use App\helper\Str;
use App\helper\TelegramBot;
use App\helper\User;
use App\Models\Admin;
use App\Models\Club;
use App\Models\Event;
use App\Models\Files;
use App\Models\Form;
use App\Models\Menu;
use App\Models\Message;
use App\Models\ParticipantEvents;
use App\Models\Post;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\Ticket;
use App\Models\University;
use App\Models\UsersForm;
use App\Models\UsersParticipatePart;
use App\Models\Vote;
use Carbon\Language;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Morilog\Jalali\Jalalian;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Sadegh19b\LaravelPersianValidation\PersianValidators;

class MessageController extends ToolsController
{

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws ExceptionError
     * @throws ExceptionWarning
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws \Exception
     */
    public function private()
    {

        $user     = new User( $this->chat_id );
        $telegram = tel();

        if ( ! $user->isPanelAdmin() )
        {

            if ( in_array( $user->getUserId(), json_decode( Storage::get( 'public/bans.json' ) ) ) )
            {
                $user->SendMessageHtml( '✋ شما مسدود هستید ⛔️' );
                exit();
            }

            if ( count( $this->text_data ) != 2 )
            {

                if ( ! $user->isOnChannel() )
                {

                    try
                    {

                        $user->setKeyboard(
                            $telegram->buildInlineKeyBoard( [
                                [
                                    $telegram->buildInlineKeyboardButton( text: '⬅️ ورود به کانال ➡️', url: 'https://t.me/montazeri_computer' )
                                ]
                            ] )
                        )->SendMessageHtml( str_replace( [ '%name%', '%id%' ], [ $this->first_name, $user->getUserId() ], Message::get( 'join-channel' ) ) );
                        die();

                    }
                    catch ( \Exception | \Throwable $exception )
                    {

                    }

                }

                switch ( $this->text )
                {

                    case '/start':
                    case '▶️ برگشت به منو اصلی':

                        START_BOT:

                        $message = 'سلام، ' . ( $user->name ?? $this->first_name ) . ' عزیز' . ' ✋' . "\n";
                        $message .= 'به ربات ' . "<b>انجمن علمی کامپیوتر دانشکده فنی و حرفه ای منتظری</b>" . ' خوش اومدی🌷' . "\n \n";
                        $message .= '▪️شما با استفاده از من می تونید هر درخواستی که از اعضای انجمن علمی کامپیوتر داری اعلام کنی تا من به دستشون برسونم🙏' . "\n";
                        $message .= 'برای استفاده از ربات میتوانید از منو زیر استفاده کنید😉';
                        $user->setKeyboard( KEY_START_MENU )->SendMessageHtml( $message )->reset();
                        /*$telegram->sendPhoto(
                            $user->getUserId(),
                            'AgACAgQAAxkBAAIIGmP_juRx81lRKrrfGWq81e0tligLAALIvjEbVW_5U2n8xw0sEmtKAQADAgADcwADLgQ',
                            $message,
                            $this->userMenu()
                        );*/

                        break;

                    case '/login':
                    case '👮‍♀️ ورود به حساب کاربری 👮‍♀️':

                        if ( is_null( $user->student_id ) )
                        {

                            $message = '<b>⚠️ توجه در صورتی که بعد از تلاش شماره دانشجویی شما وجود نداشت میتوانید از طریق ارتباط به ما درخواست اضافه کردن شماره دانشجوییان را بدهید.</b>' . "\n \n";
                            $message .= '👤 شماره دانشجویی خود را لطفا وارد کنید.';
                            $user->setKeyboard( KEY_BACK_TO_MENU )->SendMessageHtml( $message )->setStatus( 'get_student_number' );

                        }
                        else
                        {

                            $message = '⁉️ حساب شما در حال متصل شده است. آیا میخواهید از حساب متصل شده خارج شوید؟';
                            $user->setKeyboard(
                                $telegram->buildInlineKeyBoard( [
                                    [
                                        $telegram->buildInlineKeyboardButton( '🚸 خروج از حساب', '', 'exit_connected_account' )
                                    ]
                                ] )
                            )->SendMessageHtml( $message );
                        }

                        break;

                    case '/contact_us':
                    case '💌 ارتباط با ما 📨':

                        $message = '📮 به بخش پشتیبانی انجمن علمی کامپیوتر خوش آمدید🫶' . "\n \n";
                        $message .= '🔖 لطفا موضوع مرتبط را انتخاب کنید:';
                        $user->setKeyboard(
                            $telegram->buildInlineKeyBoard( [
                                [
                                    $telegram->buildInlineKeyboardButton( '👤 ثبت شماره دانشجویی', '', 'new_ticket-6' ),
                                ],
                                [
                                    $telegram->buildInlineKeyboardButton( '📀 رویداد ها', '', 'new_ticket-0' ),
                                    $telegram->buildInlineKeyboardButton( '🎓 دوره های آموزشی', '', 'new_ticket-1' ),
                                ],
                                [
                                    $telegram->buildInlineKeyboardButton( '📝 نظرات و انتقادات', '', 'new_ticket-2' ),
                                    $telegram->buildInlineKeyboardButton( '🔅 سایر موارد', '', 'new_ticket-3' ),
                                ],
                                [
                                    $telegram->buildInlineKeyboardButton( '📋 درخواست عضویت در انجمن', '', 'new_ticket-4' ),
                                    $telegram->buildInlineKeyboardButton( '🤝 همکاری با انجمن', '', 'new_ticket-5' ),
                                ],
                                [
                                    $telegram->buildInlineKeyboardButton( '🏆 مسابقات', '', 'new_ticket-7' ),
                                    $telegram->buildInlineKeyboardButton( '📬 ارتباط با صنعت', '', 'new_ticket-8' ),
                                ],
                                [
                                    $telegram->buildInlineKeyboardButton( '👨‍💻 ارتباط با دبیر', '', 'new_ticket-9' ),
                                ],
                                [
                                    $telegram->buildInlineKeyboardButton( '❌ انصراف و بستن پنل ❌', '', 'close_plan' ),
                                ]
                            ] )
                        )->SendMessageHtml( $message )->clearStatus();


                        break;

                    case '/profile':
                    case '👤 پروفایل':

                        $message = '👤 پروفایل شما:' . "\n \n";
                        $message .= '💳 آیدی عددی شما:  ' . $user->code() . "\n";
                        $message .= '👨🏻‍💻 نام حساب تلگرام شما:  ' . "<b>" . $this->first_name . "</b>" . "\n";
                        if ( ! empty( $user->name ) ) $message .= '👤 نام و نام خانوادگی:  ' . "<b><u>" . $user->name . "</u></b>" . "\n \n";

                        $message .= '➖➖➖➖➖➖➖' . "\n";

                        if ( ! empty( $user->student_id ) )
                        {

                            $link    = $user->user();
                            $message .= '🔗 حساب متصل:' . "\n";
                            $message .= '👤 نام و نام خانوادگی: ' . "<b><u>" . $link->uni->first_name . ' ' . $link->uni->last_name . "</u></b>" . "\n";
                            $message .= '🎗 شماره دانشجویی: ' . "<b><code>" . $link->uni->students_id . "</code></b>" . "\n";
                            $message .= '🏢 دانشگاه: ' . "<b>" . $link->uni->uni->name . "</b>" . "\n";
                            $message .= '🎓 رشته تحصیلی: ' . "<b>" . $link->uni->section->name . "</b>" . "\n";

                        }
                        else
                        {

                            $message .= '❌ حساب شما متصل نمی باشد.' . "\n";

                        }

                        $message .= "\n" . '🌐 این پیام برای مشاهده اطلاعات حساب شما ' . $user->mention() . ' می باشد.';

                        $user->SendMessageHtml( $message )->clearStatus();

                        break;

                    case '🎙 پادکست ها':

                        $posts = Post::where( 'type', 'podcast' )->orderBy( 'id' )->get();
                        foreach ( $posts as $post )
                            $telegram->forwardMessage( $user->getUserId(), $post->chat_id, $post->message_id );
                        $user->clearStatus();

                        break;

                    case '/about_us':
                    case '📜 درباره ما':

                        $user->setKeyboard(
                            $telegram->buildInlineKeyBoard( [
                                [
                                    $telegram->buildInlineKeyboardButton( '👈 جهت درخواست عضویت کلیک کنید 👉', 'https://t.me/' . $telegram->getMe()[ 'result' ][ 'username' ] . '?start=ticket-5' )
                                ]
                            ] )
                        )->SendMessageHtml( Message::get( 'about' ) )->clearStatus();

                        break;

                    case '/events':
                    case '🔔 رویداد ها':

                        $events = Event::all();

                        if ( count( $events ) > 0 )
                        {

                            foreach ( $events as $item )
                            {

                                switch ( $item->type )
                                {

                                    case 1:

                                        $count   = ParticipantEvents::where( 'event_id', $item->id )->count();
                                        $message = '🎓 دوره:  ' . Str::b( $item->title ) . "\n \n";
                                        $message .= '🧑‍🏫 مدرس: ' . Str::u( $item->teacher_name ) . "\n \n";
                                        $message .= '💰 هزینه دوره: ' . Str::b( number_format( $item->amount ) . ' تومان' ) . ' ' . ( in_array( $item->free_login_user, [ 1, 2 ] ) ? Str::bu( '( برای دانشجویان دانشکده منتظری رایگان )' ) : '' ) . "\n \n";
                                        $message .= '👤 ظرفیت دوره: ' . Str::u( $item->count . ' نفر' ) . "\n";
                                        $message .= '👨🏻‍🎓 تعداد دانشجو: ' . Str::u( $count . ' نفر' ) . "\n \n";

                                        if ( date( 'Y-m-d', strtotime( $item->available_at ) ) > date( 'Y-m-d' ) )
                                        {
                                            $message .= '🗓 زمان باقی مانده جهت ثبت نام: ' . "\n";
                                            $message .= Str::b( Str::date( $item->available_at ) ) . "\n \n";
                                        }
                                        else
                                        {
                                            $message .= '❌ مهلت ثبت نام تمام شده است.' . "\n \n";
                                        }

                                        $message .= '👇جهت دریافت اطلاعات بیشتر در مورد دوره بر روی دکمه زیر کلیک کنید👇';

                                        $telegram->sendPhoto( $user->getUserId(), $item->file_id, $message, $telegram->buildInlineKeyBoard( [ [ $telegram->buildInlineKeyboardButton( '📥 شرکت در دوره 📥', '', 'event_participate-' . $item->id ) ] ] ) );

                                        break;

                                    case 2:

                                        $count   = match ( $item->data[ 'type_join' ] )
                                        {
                                            default => ParticipantEvents::where( 'event_id', $item->id )->count(),
                                            2       => ParticipantEvents::where( 'event_id', $item->id )->where( 'payment_type', '!=', 'JoinTeam' )->count(),
                                        };
                                        $message = '🏆 مسابقه : ' . Str::b( $item->title ) . "\n \n";
                                        if ( ! empty( $item->teacher_name ) ) $message .= '🤝 حامیان مسابقات : ' . Str::bu( $item->teacher_name ) . "\n";
                                        $message .= '💰 هزینه شرکت در مسابقه : ' . Str::b( number_format( $item->amount ) . ' تومان' ) . ' ' . ( in_array( $item->free_login_user, [ 1, 2 ] ) ? Str::bu( '( برای دانشجویان دانشکده منتظری رایگان )' ) : '' ) . "\n";
                                        $message .= '⭐️ تعداد شرکت کنندگان : ' . Str::u( $count . ' ' . ( $item->data[ 'type_join' ] == 2 ? 'تیم' : 'نفر' ) ) . "\n";
                                        $message .= '👤 ظرفیت مسابقه : ' . Str::u( $item->count . ' ' . ( $item->data[ 'type_join' ] == 2 ? 'تیم' : 'نفر' ) ) . "\n";
                                        $message .= '🗓 زمان باقی مانده جهت ثبت نام:' . "\n";

                                        if ( date( 'Y-m-d', strtotime( $item->available_at ) ) > date( 'Y-m-d' ) )
                                        {
                                            $message .= '🗓 زمان باقی مانده جهت ثبت نام: ' . "\n";
                                            $message .= Str::b( Str::date( $item->available_at ) ) . "\n \n";
                                        }
                                        else
                                        {
                                            $message .= '❌ مهلت ثبت نام تمام شده است.' . "\n \n";
                                        }

                                        $message .= '👇جهت دریافت اطلاعات بیشتر در مورد دوره بر روی دکمه زیر کلیک کنید👇' . "\n \n";
                                        $message .= '📣 @montazeri_computer';
                                        $telegram->sendPhoto( $user->getUserId(), $item->file_id, $message, $telegram->buildInlineKeyBoard( [ [ $telegram->buildInlineKeyboardButton( '🏆 شرکت در مسابقه 🎮', '', 'event_participate-' . $item->id ) ] ] ) );

                                        break;

                                }

                            }

                        }
                        else
                        {

                            $user->SendMessageHtml( '❗️ در حال حاضر هیچ رویداد فعالی نداریم 😉' )->clearStatus();

                        }

                        break;

                    case '🍔 سامانه رزرو غذا 🍟':
                    case '/food':


                        $message = '🍽 به بخش مدیریت سامانه رزرو غذا خوش آمدید🎉' . "\n \n";
                        $message .= '📍اجازه بده هرکدام از بخش هارو برات توضیح بدم😉' . "\n";
                        $message .= Str::u( '🔻 هدف از طراحی این بخش این است که دانشجویان بدون هیچ دردسری غذای سلف خود را رزرو کنند و دیگر مشکل فراموشی رزرو غذا حل شود ✅' ) . "\n \n";
                        $message .= Str::bu( '🔸خرید اشتراک:' ) . "\n";
                        $message .= '🔹 برای استفاده از سامانه رزرو اتوماتیک شما نیاز هست که اشتراک این بخش را داشته باشید و برای خرید اشتراک میتوانید از این قسمت اقدام کنید.' . "\n";
                        $message .= Str::bu( '🔸 اشتراک من:' ) . "\n";
                        $message .= '🔹در این قسمت میتوانید اشتراک باقی مانده خود را مشاهده کنید.' . "\n";
                        $message .= Str::bu( '🔸شرایط و قوانین:' ) . "\n";
                        $message .= '🔹 در این قسمت قوانین و شرایط استفاده از بخش رزرو اتوماتیک را توضیح داده شده است.' . "\n";
                        $message .= Str::bu( '🔸تنظیمات:' ) . "\n";
                        $message .= '🔹 همانطوری که میدونید برای رزرو غذا نیاز به شماره دانشجویی و رمز عبور دارد بعد از تهیه اشتراک میتوانید وارد این بخش شوید و اقدام به ثبت اطلاعات ورود خود کنید.' . "\n";
                        $user->setKeyboard(
                            $telegram->buildKeyBoard( [
                                [
                                    $telegram->buildKeyboardButton( '💳 خرید اشتراک' ),
                                    $telegram->buildKeyboardButton( '💠 اشتراک من' ),
                                ],
                                [
                                    $telegram->buildKeyboardButton( '📚 شرایط و قوانین' ),
                                    $telegram->buildKeyboardButton( '⚙️ تنظیمات' ),
                                ],
                                [
                                    $telegram->buildKeyboardButton( '▶️ برگشت به منو اصلی' ),
                                ]
                            ] )
                        )->SendMessageHtml( $message )->reset();


                        break;

                    case '💳 خرید اشتراک':

                        $user->SendMessageHtml( '⚠️ این بخش هنوز فعال نشده است.' );
                        die();

                        $message  = '🛍 لیست اشتراک های موجود برای رزرو اتوماتیک غذا 🍽' . "\n";
                        $message  .= Str::u( '⚠️ منظور از 1 رزرو یعنی ربات برای غذای 1 هفته شما را به صورت اتوماتیک رزرو می کند.' );
                        $keyboard = [];
                        foreach ( Subscription::PLANS as $key => $item )
                        {
                            $keyboard[][] = $telegram->buildInlineKeyboardButton( text: '🛍 ' . $item[ 'name' ] . ' ' . $item[ 'amount' ] . ' تومان', callback_data: 'plan_food-' . $key );
                        }
                        $user->setKeyboard( $telegram->buildInlineKeyBoard( $keyboard ) )->SendMessageHtml( $message );

                        break;

                    case '💠 اشتراک من':

                        $user->SendMessageHtml( '⚠️ این بخش هنوز فعال نشده است.' );
                        die();

                        $message = '🔖  وضعیت اشتراک شما به شرح زیر است:' . "\n \n";
                        $message .= '👈 شناسه آیدی شما: ' . Str::code( $user->getUserId() ) . "\n \n";
                        $message .= '👈 ' . tr_num( $user->subscription(), 'fa' ) . ' رزرو از اشتراک شما باقی مانده است.' . "\n \n";
                        $message .= '👇 برای تمدید اشتراک خود میتوانید از دکمه زیر استفاده کنید👇';
                        $user->setKeyboard(
                            $telegram->buildInlineKeyBoard( [
                                [
                                    $telegram->buildInlineKeyboardButton( text: '♻️ تمدید اشتراک', callback_data: 'plan_food' )
                                ]
                            ] )
                        )->SendMessageHtml( $message );

                        break;

                    case '📚 شرایط و قوانین':

                        $user->SendMessageHtml( Message::get( 'self' ) );

                        break;

                    case '⚙️ تنظیمات':

                        /*if ( $user->subscription() > 0 )
                        {*/

                        // Crypt
                        $message = '⚙️ به بخش تنظیمات خوش آمدید⛓' . "\n \n";
                        $message .= '👤 برای ثبت شماره دانشجویی و رمز عبور خود از طریق دکمه ثبت اطلاعات ورود به سمانه اقدام به ثبت یا ویرایش اطلاعات خود کنید.' . "\n\n";
                        $message .= '🔹 جهت فعال سازی یادآوری غذا میتونید بر روی دکمه "فعال سازی" کلیک کنید.' . "\n\n";
                        $message .= '🖲 وضعیت یادآوری: ' . match ( $user->reserve_status )
                            {
                                'off'        => '❌ غیرفعال',
                                'on', 'done' => 'فعال ✅'
                            };

                        $message .= "\n\n" . '⚜️ روز های یادآوری:' . "\n";
                        $message .= '🔰 یک شنبه تا چهارشنبه ساعت های 12 صبح و 12 شب' . "\n";
                        $message .= '⚠️ تنها درصورتی عضو کانال انجمن باشید به شما پیام یادآوری ارسال می شود❗️' . "\n \n";
                        $message .= '💢 همچنین میتوانید با اضافه کردن ربات در گروه و ارسال دستور /install این قابلیت را در اختیار تمام اعضای گروه فعال کنید.';

                        $user->setKeyboard(
                            $telegram->buildInlineKeyBoard( [
                                [
                                    $telegram->buildInlineKeyboardButton( text: '👤 ثبت اطلاعات ورود به سامانه', callback_data: 'setting_food' )
                                ],
                                [
                                    $telegram->buildInlineKeyboardButton(
                                        text: match ( $user->reserve_status )
                                        {
                                            'on', 'done' => '❌ غیرفعال سازی',
                                            'off'        => 'فعال سازی ✅'
                                        }, callback_data: 'setting_reserve_notification'
                                    )
                                ],
                            ] )
                        )->SendMessageHtml( $message );

                        /*}
                        else
                        {
                            throw new ExceptionWarning( 'برای استفاده از این بخش نیاز است اشتراک تهیه کنید.' );
                        }*/

                        break;

                    case 'ادمین':
                    case '/admin':

                        if ( $user->isAdmin() )
                        {

                            $message = '✅ تغییر پنل کاربری با موفقیت انجام شد.' . "\n";
                            $message .= '⚜️ شما هم اکنون در پنل <u>ادمین</u> هستید.';
                            $user->SendMessageHtml( $message )->togglePanel();
                            $this->admin();

                        }
                        else goto START_BOT;

                        break;

                    case '/panel':

                        $events = ParticipantEvents::join( 'events', function ( JoinClause $join ) use ( $user ) {

                            $join
                                ->on( 'participant_events.event_id', 'events.id' )
                                ->where( 'events.available_at', '>', date( 'Y-m-d' ) )
                                ->where( 'events.type', 2 )
                                ->where( 'participant_events.data', 'LIKE', '%"role":"owner"%' )
                                ->where( 'participant_events.user_id', $user->getUserId() )
                            ;

                        } )->select( 'participant_events.*' )->get();

                        if ( $events->count() > 0 )
                        {

                            $message  = '⚜️ لطفا از رویداد هایی که در آن شرکت کرده اید، یکی را انتخاب کنید:';
                            $keyboard = [];

                            foreach ( $events as $event )
                            {

                                $keyboard[][] = $telegram->buildInlineKeyboardButton( text: '🎗 ' . $event->event->title, callback_data: 'panel_event-' . $event->id );

                            }

                            $user->setKeyboard( $telegram->buildInlineKeyBoard( $keyboard ) )->SendMessageHtml( $message );

                        }
                        else
                        {

                            $user->SendMessageHtml( '❗️ در حال حاضر هیچ رویداد فعالی نداریم 😉' )->clearStatus();

                        }

                        break;

                    default:

                        $menu = Menu::on()->where( 'name', $this->text )->first();
                        if ( isset( $menu->id ) )
                        {

                            $telegram->copyMessage( $user->getUserId(), $menu->user_id, $menu->message_id, [
                                'reply_markup' => $this->loadMenu( $menu->id )
                            ] );

                        }
                        else
                        {

                            switch ( $user->status )
                            {

                                case 'get_student_number':


                                    if ( $this->is_number() )
                                    {

                                        $message = '🎫 کد ملی خود را وارد نمایید.';
                                        $user->SendMessageHtml( $message )->setStatus( 'get_national_code' )->setData( [
                                            'id' => $this->text
                                        ] );

                                    }
                                    else
                                    {
                                        throw new ExceptionWarning( 'شماره دانشجویی یافت نشد.' );
                                    }

                                    break;

                                case 'get_national_code':

                                    if ( $this->is_number() )
                                    {

                                        $validation = new PersianValidators();

                                        if ( $validation->validateIranianNationalCode( '', $this->text, '' ) )
                                        {

                                            $query = Student::on()->where( 'students_id', $user->data[ 'id' ] );

                                            if ( $query->exists() && Hash::check( $this->text, $query->first()->national_code ) )
                                            {

                                                $query = $query->first();
                                                if ( is_null( $query->login_at ) )
                                                {

                                                    $query->login_at = now()->toDateTimeString();

                                                    $message = '✅ تبریک میگویم شما با موفقیت به حساب کاربری خود وارد شدید.' . "\n \n";
                                                    $message .= '👤 نام و نام خانوادگی: ' . "<b><u>" . $query->first_name . ' ' . $query->last_name . "</u></b>" . "\n";
                                                    $message .= '🎗 شماره دانشجویی: ' . "<b><code>" . $query->students_id . "</code></b>" . "\n";
                                                    $message .= '🏢 دانشگاه: ' . "<b>" . $query->uni->name . "</b>" . "\n";
                                                    $message .= '🎓 رشته تحصیلی: ' . "<b>" . $query->section->name . "</b>" . "\n";
                                                    $message .= '📆 تاریخ ورود: ' . "\n" . jdate( $query->login_at )->toString();
                                                    $query->save();
                                                    $user->SendMessageHtml( $message )->clearStatus()->clearData()->update( [
                                                        'student_id' => $query->id,
                                                        'name'       => $query->first_name . ' ' . $query->last_name
                                                    ] );
                                                    $user->setKeyboard( $this->userMenu() )->SendMessageHtml( '⬇️ به منو اصلی برگشتید:' );


                                                }
                                                else
                                                {
                                                    throw new ExceptionWarning( 'با هر شماره دانشجویی تنها یک حساب میتوانید وارد شوید.' );
                                                }

                                            }
                                            else
                                            {
                                                throw new ExceptionWarning( 'شماره دانشجویی یا کد ملی یافت نشد.' );
                                            }

                                        }
                                        else
                                        {
                                            throw new ExceptionWarning( 'کد ملی نامعتبر است.' );
                                        }

                                    }
                                    else
                                    {
                                        throw new ExceptionWarning( 'کد ملی یافت نشد.' );
                                    }

                                    break;

                                case 'get_message_ticket':


                                    $this->text       = str_replace( [ '<', '>' ], '', $this->text );
                                    $this->first_name = str_replace( [ '<', '>' ], '', $this->first_name );

                                    $message = '🔖 موضوع تیکت : ' . Ticket::LIST_TICKETS[ $user->data[ 'id' ] ] . "\n";
                                    $message .= '[' . $user->getUserId() . '] <a href="tg://user?id=' . $user->getUserId() . '">from </a> ' . $this->first_name . "\n";

                                    if ( ! empty( $this->text ) )
                                    {

                                        $telegram->sendMessage( env( 'GP_SUPPORT' ), $message . $this->text );

                                    }
                                    else
                                    {

                                        $telegram->copyMessage( env( 'GP_SUPPORT' ), $this->chat_id, $this->message_id );
                                        $telegram->sendMessage( env( 'GP_SUPPORT' ), $message );

                                    }

                                    $message = '✅ پیامت دریافت شد. جهت دریافت پاسخ صبوری کنید.';
                                    $user->SendMessageHtml( $message )->reset();

                                    break;

                                case 'get_message_to_reply':

                                    $telegram->copyMessage( env( 'GP_SUPPORT' ), $user->getUserId(), $this->message_id, [
                                        'reply_to_message_id'         => $user->data[ 'id' ],
                                        'allow_sending_without_reply' => true
                                    ] );
                                    $message = '[' . $user->getUserId() . '] <a href="tg://user?id=' . $user->getUserId() . '">from </a> ' . $this->first_name . "\n";
                                    $telegram->sendMessage( env( 'GP_SUPPORT' ), $message );
                                    $user->SendMessageHtml( ' پاسخ شما دریافت شد ✅' )->clearStatus()->clearData();

                                    break;

                                // --- Event ---

                                case 'get_name_team':

                                    if ( $this->is_text() )
                                    {

                                        if ( mb_strlen( $this->text, 'UTF-8' ) <= 50 )
                                        {

                                            $event = ParticipantEvents::find( $user->data[ 'id' ] );

                                            if ( isset( $event->data[ 'status' ] ) && $event->data[ 'status' ] != 'process' && $event->data[ 'status' ] != 'accept' )
                                            {
                                                goto START_BOT;
                                            }

                                            $event->data = array_merge( $event->data, [ 'team' => $this->text, 'status' => 'invite_team' ] );
                                            $event->save();

                                            $message = 'نام تیم شما با موفقیت تغییر کرد ✅';
                                            $user->SendMessageHtml( $message )->reset();
                                            $this->text = '/panel';
                                            $this->private();

                                        }
                                        else
                                        {
                                            throw new ExceptionWarning( 'نام تیم شما نمیتواند بیشتر از 50 کاراکتر باشد.' );
                                        }

                                    }
                                    else
                                    {
                                        throw new ExceptionWarning( 'شما باید یک متن ارسال کنید.' );
                                    }

                                    break;

                                case 'invite_team':

                                    if ( isset( $this->message->user_shared ) )
                                    {

                                        $user_shared     = $this->message->user_shared;
                                        $request_user_id = $user_shared->user_id;
                                        $request_id      = $user_shared->request_id;

                                        $participant_event = ParticipantEvents::find( $request_id );

                                        if ( $participant_event->exists() )
                                        {

                                            $request_user = new User( $request_user_id );
                                            $message      = '';

                                            if ( $participant_event->user_id != $request_user->getUserId() )
                                            {

                                                $event = $participant_event->event;

                                                if ( $event->data[ 'count_team' ] > ( $participant_event->data[ 'count' ] ?? 1 ) )
                                                {

                                                    if ( isset( $participant_event->data[ 'status' ] ) && $participant_event->data[ 'status' ] == 'invite_team' )
                                                    {

                                                        if ( is_numeric( $request_user->student_id ) && $event->free_login_user == 1 )
                                                        {

                                                            if ( ! $request_user->isRegisteredEvent( $event ) )
                                                            {

                                                                $participant_event->participateUser( $user, $request_user, $event );

                                                            }
                                                            else
                                                            {

                                                                $message = '❌ این کاربر قبلا در این مسابقه ثبت نام کرده است✋';

                                                            }

                                                        }
                                                        elseif ( $event->free_login_user == 2 )
                                                        {

                                                            if ( is_numeric( $request_user->student_id ) )
                                                            {

                                                                if ( ! $request_user->isRegisteredEvent( $event ) )
                                                                {

                                                                    $participant_event->participateUser( $user, $request_user, $event );

                                                                }
                                                                else
                                                                {

                                                                    $message = '❌ این کاربر قبلا در این مسابقه ثبت نام کرده است✋';
                                                                }

                                                            }
                                                            else
                                                            {

                                                                $message = '✋ کاربری که معرفی کردی هنوز در ربات ثبت نام و وارد حساب خود نشده است.';

                                                            }

                                                        }
                                                        elseif ( ! $request_user->isRegisteredEvent( $event ) )
                                                        {

                                                            $participant_event->participateUser( $user, $request_user, $event );

                                                        }
                                                        else
                                                        {

                                                            $message = '❌ این کاربر قبلا در این مسابقه ثبت نام کرده است✋';

                                                        }

                                                    }
                                                    else
                                                    {

                                                        $message = '⚜️ تیم شما در حال بررسی است🤝';

                                                    }

                                                }
                                                else
                                                {

                                                    $message = '😓 متاسفم ظرفیت تیم شما تکمیل شده است ✋';

                                                }

                                            }
                                            else
                                            {

                                                $message = '😁 نمیشه که خودت هم تیمی خودت بشی 😉';

                                            }

                                            $user->SendMessageHtml( $message );

                                        }
                                        else
                                        {
                                            throw new ExceptionWarning( 'خطایی رخ داد، یا ثبت نام شما در این رویداد لغو شده است.' );
                                        }


                                    }
                                    else
                                    {
                                        throw new ExceptionWarning( 'شما باید با استفاده از دکمه تعریف شده دوست خود را به ربات دعوت کنید.' );
                                    }

                                    break;

                                // --- Form ---

                                case 'get_info_form':

                                    $form = Form::where( 'id', ( $user->data[ 'id' ] ?? 0 ) );

                                    if ( $form->exists() )
                                    {

                                        $form       = $form->first();
                                        $questions  = $form->questions;
                                        $validation = new PersianValidators();
                                        $q          = ( $user->data[ 'q' ] ?? [] );

                                        switch ( $questions[ $user->step ][ 'validate' ] )
                                        {

                                            case 'text':

                                                if ( $this->is_text() )
                                                {

                                                    if ( mb_strlen( $this->text, 'utf8' ) <= 500 )
                                                    {

                                                        $q[ $user->step ] = $this->text;

                                                    }
                                                    else
                                                    {
                                                        throw new ExceptionWarning( 'متن ارسالی باید کمتر از 500 کاراکتر باشد.' );
                                                    }

                                                }
                                                else
                                                {
                                                    throw new ExceptionWarning( 'شما باید یک محتوا متنی ارسال کنید.' );
                                                }

                                                break;

                                            case 'persian_text':

                                                if ( $validation->validatePersianAlpha( '', $this->text, '' ) )
                                                {

                                                    if ( mb_strlen( $this->text, 'utf8' ) <= 500 )
                                                    {

                                                        $q[ $user->step ] = $this->text;

                                                    }
                                                    else
                                                    {
                                                        throw new ExceptionWarning( 'متن ارسالی باید کمتر از 500 کاراکتر باشد.' );
                                                    }

                                                }
                                                else
                                                {
                                                    throw new ExceptionWarning( 'شما باید یک متن به فارسی ارسال کنید.' );
                                                }

                                                break;

                                            case 'number':

                                                if ( $this->is_number() )
                                                {

                                                    if ( mb_strlen( $this->text, 'utf8' ) <= 15 )
                                                    {

                                                        $q[ $user->step ] = $this->text;

                                                    }
                                                    else
                                                    {
                                                        throw new ExceptionWarning( 'متن ارسالی باید کمتر از 500 کاراکتر باشد.' );
                                                    }

                                                }
                                                else
                                                {
                                                    throw new ExceptionWarning( 'شما باید یک عدد ارسال کنید.' );
                                                }

                                                break;

                                            case 'phone':

                                                if ( isset( $this->contact->phone_number ) )
                                                    $this->text = $this->contact->phone_number;

                                                if ( $validation->validateIranianMobile( '', $this->text, '' ) )
                                                {

                                                    $q[ $user->step ] = str_replace( [ '+', '98' ], [ '', '0' ], $this->text );

                                                }
                                                else
                                                {
                                                    throw new ExceptionWarning( 'شما باید یک شماره ارسال کنید.' );
                                                }

                                                break;

                                            case 'national_code':

                                                if ( $validation->validateIranianNationalCode( '', $this->text, '' ) && strlen( $this->text ) == 10 )
                                                {

                                                    $q[ $user->step ] = $this->text;

                                                }
                                                else
                                                {
                                                    throw new ExceptionWarning( 'شما باید یک کدملی ارسال کنید.' );
                                                }

                                                break;


                                        }

                                        if ( isset( $form->questions[ $user->step + 1 ] ) )
                                        {

                                            $message = $form->questions[ $user->step + 1 ][ 'name' ];

                                            switch ( $form->questions[ $user->step + 1 ][ 'validate' ] )
                                            {

                                                case 'phone':

                                                    $user->setKeyboard(
                                                        $telegram->buildKeyBoard( [
                                                            [
                                                                $telegram->buildKeyboardButton( '📞 اشتراک گذاری شماره همراهم 📱', true )
                                                            ]
                                                        ],
                                                            'برای اشتراک گذاری شماره همراهتان میتوانید از منو زیر استفاده کنید'
                                                        ),
                                                    );

                                                    break;

                                                case 'payment':

                                                    $msg = $telegram->sendMessage( $user->getUserId(), '🔄 در حال صدور صورتحساب ' );

                                                    $payment = new Payment( $message, $user->getUserId() );
                                                    $payment->config()->detail( 'detail', [ 'type' => 'form', 'form' => [ 'id' => $form->id, 'data_form' => $q ] ] );
                                                    $payment->config()->detail( 'description', $form->name . ' - ' . $user->getUserId() );
                                                    $payment_url = $payment->toUrl();

                                                    for ( $i = 0; $i < 2; $i ++ )
                                                    {

                                                        for ( $j = 1; $j <= 4; $j ++ )
                                                        {

                                                            $buffer = str_repeat( '▪️', $j );
                                                            $telegram->editMessageText( $user->getUserId(), $msg[ 'result' ][ 'message_id' ], $buffer . ' در حال صدور صورتحساب ' . $buffer );
                                                            sleep( 1 );

                                                        }

                                                    }

                                                    $message = '🧾 فاکتور پرداخت برای شما ساخته شد✅' . "\n \n";
                                                    $message .= Str::b( '💠 مشخصات فاکتور:' ) . "\n";
                                                    $message .= '💰 مبلغ: ' . Str::b( number_format( $payment->getAmount() ) . ' تومان' ) . "\n";
                                                    $message .= '📦بابت: ' . Str::bu( $form->name ) . "\n \n";
                                                    $message .= '⚠️ لطفا توجه داشته باشید هنگام پرداخت از استفاده هرگونه ' . Str::bu( 'فیلترشکن خودداری' ) . ' کنید.' . "\n \n";
                                                    $message .= '⚠️ توجه درگاه پرداخت از سمت زرین پال تایید و قابل اعتماد است ✅' . "\n";
                                                    $message .= Str::b( $payment_url ) . "\n";
                                                    $message .= '📍 لینک یک بار مصرف و 2 دقیقه اعتبار دارد.' . "\n\n";
                                                    $message .= Str::b( '👇 برای پرداخت بر روی دکمه زیر کلیک کنید👇' );
                                                    $user->setKeyboard(
                                                        $telegram->buildInlineKeyBoard( [
                                                            [
                                                                $telegram->buildInlineKeyboardButton( '💳 پرداخت', $payment_url )
                                                            ]
                                                        ] )
                                                    )->SendMessageHtml( $message )->reset();
                                                    $telegram->deleteMessage( $user->getUserId(), $msg[ 'result' ][ 'message_id' ] );
                                                    die();

                                                    break;

                                                default:

                                                    $user->setKeyboard( $telegram->buildKeyBoardHide() );

                                                    break;

                                            }


                                            $user->SendMessageHtml( $message )->setStep( $user->step + 1 )->setData(
                                                array_merge( $user->data, [
                                                    'q' => $q
                                                ] )
                                            );

                                        }
                                        else
                                        {

                                            if ( $form->participate > 0 || is_null( $form->participate ) )
                                            {
                                                UsersForm::create( [
                                                    'user_id' => $user->getUserId(),
                                                    'form_id' => $form->id,
                                                    'value'   => $q
                                                ] );
                                                $message = '✅ اطلاعات ثبت نام شما در فرم " ' . $form->name . ' " با موفقیت انجام شد 🙏' . "\n \n";
                                                $message .= 'با تشکر از همکاری شما🤝';
                                                $user->SendMessageHtml( $message )->reset();
                                            }
                                            else
                                            {
                                                $message = '😮‍💨 متاسفم اما ظرفیت این فرم تکمیل شده است❌';
                                                $user->SendMessageHtml( $message );
                                            }

                                        }


                                    }
                                    else
                                    {
                                        throw new ExceptionWarning( 'خطایی در شناسایی فرم رخ داد لطفا مجدد امتحان کنید.' );
                                    }

                                    break;

                                // --- Food ---

                                case 'get_setting_food':

                                    if ( $this->is_text() )
                                    {

                                        if ( count( $this->text_data ) == 2 )
                                        {

                                            Subscription::where( 'user_id', $user->getUserId() )->update( [
                                                'student_id' => Crypt::encryptString( $this->text_data[ 0 ] ),
                                                'password'   => Crypt::encryptString( $this->text_data[ 1 ] )
                                            ] );
                                            $message = '✔️ اطلاعات شما با موفقیت ثبت شد ✅';
                                            $user->SendMessageHtml( $message );

                                        }
                                        else
                                        {
                                            throw new ExceptionWarning( 'فرمت ارسال شده اشتباه است.' );
                                        }

                                    }
                                    else
                                    {
                                        throw new ExceptionWarning( 'شما باید یک متن ارسال کنید.' );
                                    }

                                    break;

                                // ----------------

                                default:

                                    if ( isset( $this->reply_id ) && isset( $this->message->reply_to_message ) && isset( $this->message->reply_to_message->entities ) )
                                    {

                                        $msg = $this->message->reply_to_message->entities[ 0 ];

                                        if ( isset( $msg ) && isset( $msg->url ) )
                                        {

                                            if ( preg_match( '/\d+/', $msg->url, $msg ) )
                                            {

                                                $telegram->copyMessage( env( 'GP_SUPPORT' ), $user->getUserId(), $this->message_id, [
                                                    'reply_to_message_id'         => $msg[ 0 ],
                                                    'allow_sending_without_reply' => true
                                                ] );
                                                $user->SendMessageHtml( ' پاسخ شما دریافت شد ✅' );

                                            }

                                        }

                                    }
                                    else
                                    {
                                        $user->SendMessageHtml( '❌ متاسفم، چیزی که ارسال کردی را من آن را نمیشناسم .. لطفا از منو ربات استفاده کنید✅' );
                                    }

                                    break;


                            }

                        }

                        break;

                }

            }
            else
            {
                $this->subUser();
            }

        }
        else
        {
            $this->admin();
        }


    }

    /**
     * @return void
     * @throws ExceptionError
     * @throws ExceptionWarning
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    private function admin() : void
    {

        $user = new User( $this->chat_id );
        if ( method_exists( __CLASS__, $user->getRole() ) )
        {
            call_user_func( [ __CLASS__, $user->getRole() ] );
        }
        else
        {
            throw new ExceptionError( 'پنلی برای شما تنظیم نشده است.' );
        }

    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws ExceptionError
     * @throws ExceptionWarning
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     */
    public function administrator()
    {

        $user     = new User( $this->chat_id );
        $telegram = tel();

        switch ( $this->text )
        {

            case '▶️ برگشت به منو اصلی':
            case '/start':

                START_BOT_ADMIN:
                $message = '🌐 سلام ادمین گرامی به پنل مدیریت خود خوش آمدید.' . "\n \n";
                $message .= '♨️ برای استفاده از ربات و امکانات آن لطفا از پنل زیر استفاده کنید.';
                $user->setKeyboard( $this->adminMenu() )->SendMessageHtml( $message )->clearStatus();

                break;

            #مدیریت مدیران
            case '🤝 مدیریت مدیران':

                $message = '💢 بخش مدیریت مدیران ربات برای استفاده از این بخش از منو زیر استفاده کنید.';
                $user->setKeyboard(
                    $telegram->buildKeyBoard( [
                        [
                            $telegram->buildKeyboardButton( '➕ افزودن مدیر' ),
                            $telegram->buildKeyboardButton( '➖ حذف مدیر' ),
                        ],
                        [
                            $telegram->buildKeyboardButton( '📋 لیست مدیران' )
                        ],
                        [
                            $telegram->buildKeyboardButton( '▶️ برگشت به منو اصلی' )
                        ]
                    ] )
                )->SendMessage( $message );

                break;

            case '➕ افزودن مدیر':

                $message = '⚜️ برای <u>اضافه کردن مدیر جدید</u> آیدی عددی حساب فرد مورد نظر خود را وارد کنید.' . "\n \n";
                $message .= "<b>⚠️ برای دریافت آیدی عددی از دستور /id در حساب فرد مورد نظر استفاده کنید.</b>";
                $user->setKeyboard( KEY_BACK_TO_MENU )->SendMessageHtml( $message )->setStatus( 'get_id_for_add_admin' );

                break;

            case '➖ حذف مدیر':

                $message = '⚜️ برای <u>حذف کردن مدیر</u> آیدی عددی حساب فرد مورد نظر خود را وارد کنید.' . "\n \n";
                $message .= "<b>⚠️ برای دریافت آیدی عددی از دستور /id در حساب فرد مورد نظر استفاده کنید.</b>";
                $user->setKeyboard( KEY_BACK_TO_MENU )->SendMessageHtml( $message )->setStatus( 'get_id_for_remove_admin' );

                break;

            case '📋 لیست مدیران':

                $message = '📋 لیست مدیران ربات به شرح زیر است:' . "\n \n";
                foreach ( Admin::All() as $id => $item )
                {
                    $message .= "<b>" . ( $id + 1 ) . ".</b>" . " <code>{$item->user_id}</code> <a href='tg://user?id={$item->user_id}'>Profile</a> <b>" . $item->role . "</b>" . "\n";
                }
                $user->SendMessageHtml( $message );

                break;

            #ارسال پیام

            case '📮 ارسال پیام':

                $message = '📍 نوع ارسال پیام را انتخاب کنید:';
                $user->setKeyboard(
                    $telegram->buildKeyBoard( [
                        [
                            $telegram->buildKeyboardButton( '📩 پیام همگانی' ),
                            $telegram->buildKeyboardButton( '📫 فوروارد همگانی' ),
                        ],
                        [
                            $telegram->buildKeyboardButton( '▶️ برگشت به منو اصلی' ),
                        ]
                    ] )
                )->SendMessageHtml( $message );

                break;

            case '📩 پیام همگانی':

                $message = '♨️ لطفا پیامی که میخواهید برای تمامی اعضا ارسال شود را ارسال کنید.' . "\n \n";
                $message .= '🚫 توجه داشته باشید که هر پیامی که ارسال کنید به همان شکل ارسال می شود <b><u>لطفا در ارسال پیام دقت کنید.</u></b>';
                $user->setKeyboard( KEY_BACK_TO_MENU )->SendMessageHtml( $message )->setStatus( 'get_message_for_message_all' );

                break;

            case '📫 فوروارد همگانی':

                $message = '♨️ لطفا پیامی که میخواهید برای تمامی اعضا فوروارد شود را ارسال کنید.' . "\n \n";
                $message .= '🚫 توجه داشته باشید که هر پیامی که ارسال کنید به همان شکل فوروارد می شود <b><u>لطفا در ارسال پیام دقت کنید.</u></b>';
                $user->setKeyboard( KEY_BACK_TO_MENU )->SendMessageHtml( $message )->setStatus( 'get_message_for_forward_all' );

                break;

            #مدیریت منو

            case '🖍 مدیریت منو':

                $tel      = telegram();
                $menus    = Menu::on()->where( 'parent', 0 )->get();
                $keyboard = [];
                foreach ( $menus as $item )
                {
                    $keyboard[ $item->row ][ $item->col ] = $tel->buildInlineKeyboardButton( $item->name, '', 'menu-' . $item->id );
                }
                $user->setKeyboard( $tel->buildInlineKeyBoard( $keyboard ) )->SendMessageHtml( '🛡 به بخش مدیریت منو های ربات خوش آمدید.' )->clearStatus();

                break;

            #پیام های ربات

            case '📝 مدیریت پیام های ربات':

                $message = '📂 به بخش مدیریت پیام های ربات خوش آمدید ...' . "\n \n";
                $message .= '<b>▪️ برای تغییر هر کدام از متن دکمه ها بر روی دکمه مورد نظر کلیک نمایید.</b>';

                $keyboard = [];
                $i        = 0;
                $r        = 0;
                foreach ( Message::all() as $item )
                {
                    $keyboard[ $i ][ $r ] = $telegram->buildInlineKeyboardButton( $item->title, '', 'btn-' . $item->id . '-edit' );
                    if ( $r ++ == 1 )
                    {
                        $i ++;
                        $r = 0;
                    }
                }

                $user->setKeyboard( $telegram->buildInlineKeyBoard( $keyboard ) )->SendMessageHtml( $message );

                break;

            #آمار گیری ربات
            case '📊 آمار ربات 📊':

                $message = '📊 گزارش آمار ربات انجمن علمی کامپیوتر منتظری در تاریخ:' . "\n";
                $message .= "<code>" . jdate()->format( 'Y-m-d H:i:s' ) . "</code>" . "\n \n";
                $message .= '👤 تعداد کاربران: ' . "\n" . \App\Models\User::count() . ' نفر' . "\n \n";
                $message .= '🎓 تعداد افراد وارد شده در 24 ساعت اخیر: ' . "\n" . \App\Models\User::where( 'created_at', '>=', now()->format( 'Y-m-d' ) )->count() . ' نفر' . "\n \n";
                $message .= '👨🏻‍🎓 تعداد افرادی که وارد حسابشان شده اند: ' . "\n" . \App\Models\User::whereNotNull( 'student_id' )->count() . ' نفر' . "\n \n";
                $message .= '🍽 تعداد کاربران یادآور رزرو: ' . "\n" . \App\Models\User::where( function ( Builder $builder ) {

                        $builder->where( 'reserve_status', 'on' )->orWhere( 'reserve_status', 'done' );

                    } )->count() . ' نفر' . "\n \n";
                $user->SendMessageHtml( $message );

                break;

            # ثبت نام دانشجو جدید در لیست دانشجویان

            case '🎓 مدیریت دانشجویان':

                $message = '💢 به بخش مدیریت دانشجویان خوش امدید:';
                $user->setKeyboard(
                    $telegram->buildKeyBoard( [
                        [
                            $telegram->buildKeyboardButton( '➕ ثبت نام دانشجو جدید' ),
                            $telegram->buildKeyboardButton( '▪️اطلاعات دانشجو' ),
                        ],
                        [
                            $telegram->buildKeyboardButton( '🏫 اضافه کردن دانشگاه' ),
                            $telegram->buildKeyboardButton( '🏫 لیست دانشگاها' )
                        ],
                        [
                            $telegram->buildKeyboardButton( '🎓 اضافه رشته تحصیلی' ),
                            $telegram->buildKeyboardButton( '🎓 لیست رشته تحصیلی' )
                        ],
                        [
                            $telegram->buildKeyboardButton( '▶️ برگشت به منو اصلی' )
                        ]
                    ] )
                )->SendMessageHtml( $message );

                break;

            case '➕ ثبت نام دانشجو جدید':

                $message = '💢 لطفا شماره دانشجویی، دانشجو را وارد کنید:';
                $user->setKeyboard( KEY_BACK_TO_MENU )->SendMessageHtml( $message )->setStatus( 'register_new_user' )->setStep( 1 );

                break;

            case '▪️اطلاعات دانشجو':

                $message = '🏷 شماره دانشجویی مورد نظر را وارد کنید:';
                $user->setKeyboard( KEY_BACK_TO_MENU )->SendMessageHtml( $message )->setStatus( 'student_info' );

                break;

            # مدیریت دانشگاه ها

            case '🏫 اضافه کردن دانشگاه':

                $message = '🏷 نام دانشگاهی که میخواهید اضافه کنید را وارد کنید:';
                $user->SendMessageHtml( $message )->setStatus( 'new_universities' )->setStep( 1 );

                break;

            case '🏫 لیست دانشگاها':

                $message = '📋 لیست دانشگاه های ثبت شده در ربات:' . "\n \n";
                foreach ( University::all() as $id => $item )
                {
                    $message .= "<b>" . ( $id + 1 ) . ".</b>" . " <code>{$item->name}</code>" . "\n";
                }
                $user->SendMessageHtml( $message );

                break;

            #مدیریت رشته ها

            case '🎓 اضافه رشته تحصیلی':

                $message = '🏷 نام رشته که میخواهید اضافه کنید را وارد کنید:';
                $user->SendMessageHtml( $message )->setStatus( 'new_section' )->setStep( 1 );

                break;

            case '🎓 لیست رشته تحصیلی':

                $message = '📋 لیست رشته های ثبت شده در ربات:' . "\n \n";
                foreach ( Section::all() as $id => $item )
                {
                    $message .= "<b>" . ( $id + 1 ) . ".</b>" . " <code>{$item->name}</code>" . "\n";
                }
                $user->SendMessageHtml( $message );

                break;

            # رویداد ها

            case '🛎 مدیریت رویداد ها':

                $message = '🔻 به مدیریت رویداد ها خوش امدید.';
                $user->setKeyboard(
                    $telegram->buildKeyBoard( [
                        [
                            $telegram->buildKeyboardButton( '➕ رویداد جدید' ),
                            $telegram->buildKeyboardButton( '🔘 رویداد ها' ),
                        ],
                        [
                            $telegram->buildKeyboardButton( '▶️ برگشت به منو اصلی' )
                        ],
                    ] )
                )->SendMessageHtml( $message );

                break;

            case '➕ رویداد جدید':

                $message = '💠 لطفا انتخاب کنید رویداد شما از چه نوعی می باشد؟';
                $user->setKeyboard(
                    $telegram->buildInlineKeyBoard( [
                        [
                            $telegram->buildInlineKeyboardButton( '🎓 دوره آموزشی', '', 'new_event-1' ),
                        ],
                        [
                            $telegram->buildInlineKeyboardButton( '🏆 مسابقات', '', 'new_event-2' ),
                        ]
                    ] )
                )->SendMessageHtml( $message )->reset();

                break;

            case '🔘 رویداد ها':


                $events = Event::all();

                if ( count( $events ) > 0 )
                {

                    foreach ( $events as $item )
                    {

                        switch ( $item->type )
                        {

                            case 1:

                                $count   = ParticipantEvents::where( 'event_id', $item->id )->count();
                                $message = '🎓 دوره:  ' . Str::b( $item->title ) . "\n \n";
                                $message .= '🧑‍🏫 مدرس: ' . Str::u( $item->teacher_name ) . "\n \n";
                                $message .= '💰 هزینه دوره: ' . Str::b( number_format( $item->amount ) . ' تومان' ) . ' ' . ( in_array( $item->free_login_user, [ 1, 2 ] ) ? Str::bu( '( برای دانشجویان دانشکده منتظری رایگان )' ) : '' ) . "\n \n";
                                $message .= '👤 ظرفیت دوره: ' . Str::u( $item->count . ' نفر' ) . "\n";
                                $message .= '👨🏻‍🎓 تعداد دانشجو: ' . Str::u( $count . ' نفر' ) . "\n \n";

                                if ( $item->available_at > date( 'Y-m-d' ) )
                                {
                                    $message .= '🗓 زمان باقی مانده جهت ثبت نام: ' . "\n";
                                    $message .= Str::b( Str::date( $item->available_at ) ) . "\n \n";
                                }
                                else
                                {
                                    $message .= '❌ مهلت ثبت نام تمام شده است.' . "\n \n";
                                }
                                $message .= 'https://t.me/' . $telegram->getMe()[ 'result' ][ 'username' ] . '?start=' . $item->hash;

                                $telegram->sendPhoto(
                                    $user->getUserId(), $item->file_id, $message, $telegram->buildInlineKeyBoard( [
                                    [
                                        $telegram->buildInlineKeyboardButton( '✏️ ویرایش', '', 'edit_event-' . $item->id ),
                                        $telegram->buildInlineKeyboardButton( '🗑 حذف', '', 'delete_event-' . $item->id )
                                    ],
                                    [
                                        $telegram->buildInlineKeyboardButton( '📜 لیست شرکت کنندگان', '', 'list_participate_event-' . $item->id ),
                                        $telegram->buildInlineKeyboardButton( '👤 حضور و غیاب', '', 'roll_call_event-' . $item->id )
                                    ],
                                    [
                                        $telegram->buildInlineKeyboardButton( '🗑 لغو ثبت نام کاربر', '', 'remove_user_event-' . $item->id ),
                                    ],
                                    [
                                        $telegram->buildInlineKeyboardButton( '📮 ارسال پیام به شرکت کننده ها 📯', '', 'send_message_event-' . $item->id ),
                                    ]
                                ] )
                                );

                                break;

                            case 2:

                                $count   = match ( $item->data[ 'type_join' ] )
                                {
                                    default => ParticipantEvents::where( 'event_id', $item->id )->count(),
                                    2       => ParticipantEvents::where( 'event_id', $item->id )->where( 'payment_type', '!=', 'JoinTeam' )->count(),
                                };
                                $message = '🏆 مسابقه : ' . Str::b( $item->title ) . "\n \n";
                                if ( ! empty( $item->teacher_name ) ) $message .= '🤝 حامیان مسابقات : ' . Str::bu( $item->teacher_name ) . "\n";
                                $message .= '💰 هزینه شرکت در مسابقه : ' . Str::b( number_format( $item->amount ) . ' تومان' ) . ' ' . ( in_array( $item->free_login_user, [ 1, 2 ] ) ? Str::bu( '( برای دانشجویان دانشکده منتظری رایگان )' ) : '' ) . "\n";
                                $message .= '⭐️ تعداد شرکت کنندگان : ' . Str::u( $count . ' ' . ( $item->data[ 'type_join' ] == 2 ? 'تیم' : 'نفر' ) ) . "\n";
                                $message .= '👤 ظرفیت مسابقه : ' . Str::u( $item->count . ' ' . ( $item->data[ 'type_join' ] == 2 ? 'تیم' : 'نفر' ) ) . "\n";
                                $message .= '🗓 زمان باقی مانده جهت ثبت نام:' . "\n";

                                if ( date( 'Y-m-d', strtotime( $item->available_at ) ) > date( 'Y-m-d' ) )
                                {
                                    $message .= '🗓 زمان باقی مانده جهت ثبت نام: ' . "\n";
                                    $message .= Str::b( Str::date( $item->available_at ) ) . "\n \n";
                                }
                                else
                                {
                                    $message .= '❌ مهلت ثبت نام تمام شده است.' . "\n \n";
                                }

                                $message .= '👇جهت دریافت اطلاعات بیشتر در مورد دوره بر روی دکمه زیر کلیک کنید👇' . "\n \n";
                                $message .= 'https://t.me/' . $telegram->getMe()[ 'result' ][ 'username' ] . '?start=' . $item->hash . "\n \n";
                                $message .= '📣 @montazeri_computer';

                                $telegram->sendPhoto(
                                    $user->getUserId(), $item->file_id, $message, $telegram->buildInlineKeyBoard( [
                                    [
                                        $telegram->buildInlineKeyboardButton( '📁 خروجی اکسل', route( 'export.events', [ 'events' => $item->hash ] ) ),
                                    ],
                                    [
                                        $telegram->buildInlineKeyboardButton( '✏️ ویرایش', '', 'edit_event-' . $item->id ),
                                        $telegram->buildInlineKeyboardButton( '🗑 حذف', '', 'delete_event-' . $item->id )
                                    ],
                                    [
                                        $telegram->buildInlineKeyboardButton( '🗑 لغو ثبت نام کاربر', '', 'remove_user_event-' . $item->id ),
                                    ],
                                    [
                                        $telegram->buildInlineKeyboardButton( '📮 ارسال پیام به شرکت کننده ها 📯', '', 'send_message_event-' . $item->id ),
                                    ]
                                ] )
                                );

                                break;

                        }

                    }

                }
                else
                {

                    $user->SendMessageHtml( '🔶 هیچ رویداد فعالی وجود ندارد.' );

                }

                break;

            # فرم ها

            case '📃 مدیریت فرم ها ✉️':

                $message = '🔻 به مدیریت فرم ها خوش امدید.';
                $user->setKeyboard(
                    $telegram->buildKeyBoard( [
                        [
                            $telegram->buildKeyboardButton( '➕ فرم جدید' ),
                            $telegram->buildKeyboardButton( '📜 فرم ها' ),
                        ],
                        [
                            $telegram->buildKeyboardButton( '▶️ برگشت به منو اصلی' )
                        ],
                    ] )
                )->SendMessageHtml( $message );

                break;

            case '➕ فرم جدید':

                $message = '⚜️ پیش نمایش فرم را ارسال کنید.';
                $user->setKeyboard( KEY_BACK_TO_MENU )->SendMessageHtml( $message )->setStatus( 'new_form' )->setStep( 1 );

                break;

            case '📜 فرم ها':

                $forms = Form::all();

                if ( count( $forms ) > 0 )
                {


                    $link = ( 'https://t.me/' . $telegram->getMe()[ 'result' ][ 'username' ] . '?start=form-' );
                    foreach ( $forms as $item )
                    {

                        $avg = Vote::where( 'model', $item::class )
                                   ->where( 'model_id', $item->id )
                                   ->where( 'star', '>', '0' )
                                   ->avg( 'star' )
                        ;

                        $message = '📍 نام فرم: ' . $item->name . "\n";
                        $message .= '👤 تعداد شرکت کنندگان: ' . $item->users->count() . ' نفر' . "\n";
                        $message .= '📊 میانگین نظرسنجی: ' . round( $avg, 2 ) . ' ⭐️' . "\n \n";
                        $message .= $link . $item->hash;
                        $telegram->copyMessage( $user->getUserId(), $item->user_id, $item->message_id, [
                            'reply_markup' => $telegram->buildInlineKeyBoard( [
                                [
                                    $telegram->buildInlineKeyboardButton( text: '📁 خروجی اکسل', url: route( 'export.forms', [ 'forms' => $item->hash ] ) ),
                                ],
                                [
                                    $telegram->buildInlineKeyboardButton( text: '✏️ ویرایش', callback_data: 'edit_form-' . $item->id ),
                                    $telegram->buildInlineKeyboardButton( text: '🗑 حذف', callback_data: 'delete_form-' . $item->id )
                                ],
                                [
                                    $telegram->buildInlineKeyboardButton( text: '📋 لیست شرکت کنندگان', callback_data: 'list_participate_form-' . $item->id . '-1' ),
                                    $telegram->buildInlineKeyboardButton( text: '📡 ارسال پیام به شرکت کنندگان', callback_data: 'send_message_to_participate_form-' . $item->id ),
                                ],

                                [
                                    $telegram->buildInlineKeyboardButton( text: '📯 ارسال نظرسنجی', callback_data: 'send_vote-' . $item->id . '-form' ),
                                ],
                                [
                                    $telegram->buildInlineKeyboardButton(
                                        text: match ( intval( $item->status ) )
                                        {
                                            Form::STATUS_PUBLIC  => '✅ فعال',
                                            Form::STATUS_DELETED => '❌ مخفی شده'
                                        }, callback_data: 'change_status-' . $item->id
                                    ),
                                ]
                            ] ),
                            'caption'      => $message
                        ] );

                    }

                }
                else
                {

                    $user->SendMessageHtml( '🔶 هیچ فرم فعالی وجود ندارد.' );

                }

                break;

            # فایل ها

            case '📨 مدیریت فایل ها 📬':

                $message = '🏮 به بخش مدیریت فایل ها خوش آمدید:';
                $user->setKeyboard(
                    $telegram->buildKeyBoard( [
                        [
                            $telegram->buildKeyboardButton( '📓 لیست فایل ها' ),
                            $telegram->buildKeyboardButton( '📁 فایل جدید' ),
                        ],
                        [
                            $telegram->buildInlineKeyboardButton( '▶️ برگشت به منو اصلی' )
                        ]
                    ] )
                )->SendMessageHtml( $message );

                break;

            case '📓 لیست فایل ها':

                $message = '📁 لطفا نام پوشه مورد نظر را وارد کنید:';
                $user->setKeyboard( KEY_BACK_TO_MENU )->SendMessageHtml( $message )->setStatus( 'get_folder_name' );

                break;

            case '📁 فایل جدید':

                $message = '📦 فایل جدید را ارسال کنید:';
                $user->setKeyboard( KEY_BACK_TO_MENU )->SendMessageHtml( $message )->setStatus( 'get_new_file' );

                break;

            # پاک کردن کش برای منو ها
            case '/clear':

                cache()->clear();
                throw new ExceptionError( 'کش با موفقیت خالی شد!' );

                break;

            case '/stats':

                $message = \Illuminate\Support\Str::of( '📊 آمار نظرسنجی بازی سازی در ربات انجمن علمی کامپیوتر:' )->append( "\n" );
                $message = $message->append( "<code>" . Jalalian::now()->format( 'Y/m/d H:i:s' ) . "</code>" )->append( "\n \n" );
                $message = $message->append( '👈 ', 'بازی ساز هستم: ' )->append( \DB::table( 'game_vote' )->where( 'option_select', 1 )->count() )->append( ' نفر' )->append( "\n" );
                $message = $message->append( '👈 ', 'به بازی سازی علاقه دارم و دوست دارم آشنا شوم: ' )->append( \DB::table( 'game_vote' )->where( 'option_select', 2 )->count() )->append( ' نفر' )->append( "\n" );
                $message = $message->append( '👈 ', 'علاقه ای به این حوزه ندارم: ' )->append( \DB::table( 'game_vote' )->where( 'option_select', 3 )->count() )->append( ' نفر' )->append( "\n" );
                $message = $message->append( '👈 ', 'مجموع شرکت کنندگان: ' )->append( \DB::table( 'game_vote' )->count() )->append( ' نفر' );
                $user->SendMessageHtml( $message );

                break;

            # ساخت جداول

            case '/migrate':

                $command = \Artisan::call( 'migrate' );
                $user->SendMessageHtml( ( $command == 0 ? 'جداول با موفقیت بروزرسانی شدند✅' : ( 'Error: ' . $command ) ) );

                break;

            # استفاده از دستورات

            case '/artisan':

                $user->setKeyboard( KEY_BACK_TO_MENU )->SendMessageHtml( "💠 Send Me Your Command . . ." )->setStatus( 'artisan' );

                break;

            # ساخت بک آپ

            case '/backup':


                $msg = $telegram->sendMessage( $user->getUserId(), Str::codeB( '♻️ Starting backup...' ) )[ 'result' ][ 'message_id' ];

                \Artisan::call( 'backup:run --disable-notifications' );

                sleep( 1 );
                $telegram->editMessageText( $user->getUserId(), $msg, Str::codeB( '✅ Successfully Create BackUp.' ) );

                sleep( 1 );
                $telegram->editMessageText( $user->getUserId(), $msg, Str::codeB( '📤 Uploading BackUp ....' ) );
                $telegram->sendChatAction( $user->getUserId(), TelegramBot::ActionUploadDocument );

                sleep( 1 );
                $files = Storage::files( env( 'APP_NAME' ) );
                foreach ( $files as $file )
                {

                    $telegram->sendDocument( $user->getUserId(), url()->to( 'storage/app/' . $file ), 'Your BackUp: ' . jdate()->format( 'Y/m/d H:i:s' ) );

                }

                \Artisan::call( 'backup:clean --disable-notifications' );
                Storage::deleteDirectory( env( 'APP_NAME' ) );

                $telegram->editMessageText( $user->getUserId(), $msg, Str::codeB( '✅ Backup completed!' ) );

                break;

            # help

            case '/help':

                $message = 'Commands Active:
                    /clear For Clear Cache Menu
                    /migrate Building Migrate Database
                    /backup Get BackUp From Bot
                    /user Which To Panel User
                    /admin Which To Panel Admin
                ' . '<tg-emoji emoji-id="5368324170671202286">👍</tg-emoji>';
                $user->SendMessageHtml( $message );

                break;

            #تعویض پنل

            case '🔃 برگشت به پنل کاربر 🔄':
            case '/user':
            case 'یوزر':

                if ( $user->isAdmin() )
                {

                    $message = '✅ تغییر پنل کاربری با موفقیت انجام شد.' . "\n";
                    $message .= '⚜️ شما هم اکنون در پنل <u>کاربر عادی</u> هستید.';
                    $user->setKeyboard( $this->userMenu() )->SendMessageHtml( $message )->togglePanel();

                }
                else throw new ExceptionWarning( 'شما ادمین نیستید' );

                break;

            default:

                switch ( $user->status )
                {

                    #مدیریت مدیران

                    case 'get_id_for_add_admin':

                        if ( $this->is_number() )
                        {

                            $message = '⚜️ سطح دسترسی کاربر رو وارد کنید:';
                            $user->SendMessageHtml( $message )
                                 ->setStatus( 'get_role_name_for_add_admin' )
                                 ->setData( [ 'id' => $this->text ] )
                            ;

                        }
                        else
                        {

                            throw new ExceptionError( 'آیدی عددی باید یک عدد باشد.' );

                        }

                        break;

                    case 'get_role_name_for_add_admin':

                        if ( $this->is_text() )
                        {

                            $new_admin = new User( $user->data[ 'id' ] );
                            if ( $new_admin->toAdmin( $this->text ) )
                            {

                                $message = 'کاربر ' . "<code>{$new_admin->getUserId()}</code>" . ' به لیست مدیران اضافه شد✅';
                                $user->SendMessageHtml( $message )->setStatus( '' );
                                $message = '🌐 پیام سیستم:' . "\n \n";
                                $message .= '⚜️ شما به عنوان یکی از مدیران ربات معرفی شدید.' . "\n \n";
                                $message .= '♨️ برای دریافت پنل خود از دستور /admin استفاده کنید.';
                                $new_admin->SendMessageHtml( $message );

                            }
                            else
                            {
                                throw new ExceptionError( 'در اضافه کردن ادمین خطایی رخ داد.' );
                            }

                        }
                        else
                        {
                            throw new ExceptionWarning( 'شما باید یک متن ارسال کنید.' );
                        }

                        break;

                    case 'get_id_for_remove_admin':

                        if ( $this->is_number() )
                        {

                            $new_admin = new User( $this->text );
                            if ( $new_admin->removeAdmin() )
                            {

                                $message = 'کاربر ' . "<code>{$new_admin->getUserId()}</code>" . ' از لیست مدیران حذف شد✅';
                                $user->SendMessageHtml( $message )->clearStatus();
                                $message = '🌐 پیام سیستم:' . "\n \n";
                                $message .= '⚜️ شما از لیست مدیران ربات حذف شدید، از همکاری شما سپاس گذاریم.';
                                $new_admin->setKeyboard( $this->userMenu() )->SendMessageHtml( $message );

                            }
                            else
                            {
                                throw new ExceptionError( 'در اضافه کردن ادمین خطایی رخ داد.' );
                            }

                        }
                        else
                        {

                            throw new ExceptionError( 'آیدی عددی باید یک عدد باشد.' );

                        }

                        break;

                    #ارسال پیام همگانی

                    case 'get_message_for_message_all':

                        $user->setStatus( '' );
                        $message    = '⚠️ عملیات ارسال پیام همگانی شروع شد ... توجه این عملیات ممکن است زمان بر باشد.' . "\n \n";
                        $message    .= '⭕️ عددی که در زیر مشاهده میکنید عدد تعداد پیام ارسالی موفق به کاربران است.';
                        $message    = $telegram->sendMessage(
                            $user->getUserId(), $message, $telegram->buildInlineKeyBoard( [
                            [
                                $telegram->buildInlineKeyboardButton( '0', '', 'counter' )
                            ]
                        ] )
                        );
                        $message_id = $message[ 'result' ][ 'message_id' ];
                        $i          = 0;

                        foreach ( \App\Models\User::All() as $item )
                        {
                            $result = $telegram->copyMessage( $item->user_id, $user->getUserId(), $this->message_id );
                            if ( isset( $result[ 'result' ][ 'message_id' ] ) ) $i ++;
                            if ( $i % 10 == 0 )
                            {
                                $telegram->editKeyboard(
                                    $user->getUserId(), $message_id, $telegram->buildInlineKeyBoard( [
                                    [
                                        $telegram->buildInlineKeyboardButton( $i, '', 'counter' )
                                    ]
                                ] )
                                );
                            }
                        }

                        $message = '✅ عملیات ارسال پیام همگانی با موفقیت به پایان رسید.';
                        $telegram->editMessageText(
                            $user->getUserId(), $message_id, $message, $telegram->buildInlineKeyBoard( [
                            [
                                $telegram->buildInlineKeyboardButton( $i, '', 'counter' )
                            ]
                        ] )
                        );

                        break;

                    case 'get_message_for_forward_all':

                        $user->setStatus( '' );
                        $message    = '⚠️ عملیات ارسال پیام همگانی شروع شد ... توجه این عملیات ممکن است زمان بر باشد.' . "\n \n";
                        $message    .= '⭕️ عددی که در زیر مشاهده میکنید عدد تعداد پیام ارسالی موفق به کاربران است.';
                        $message    = $telegram->sendMessage(
                            $user->getUserId(), $message, $telegram->buildInlineKeyBoard( [
                            [
                                $telegram->buildInlineKeyboardButton( '0', '', 'counter' )
                            ]
                        ] )
                        );
                        $message_id = $message[ 'result' ][ 'message_id' ];
                        $i          = 0;

                        foreach ( \App\Models\User::All() as $item )
                        {
                            $result = $telegram->forwardMessage( $item->user_id, $user->getUserId(), $this->message_id );
                            if ( isset( $result[ 'result' ][ 'message_id' ] ) ) $i ++;
                            if ( $i % 10 == 0 )
                            {
                                $telegram->editKeyboard(
                                    $user->getUserId(), $message_id, $telegram->buildInlineKeyBoard( [
                                    [
                                        $telegram->buildInlineKeyboardButton( $i, '', 'counter' )
                                    ]
                                ] )
                                );
                            }
                        }

                        $message = '✅ عملیات ارسال فوروراد همگانی با موفقیت به پایان رسید.';
                        $telegram->editMessageText(
                            $user->getUserId(), $message_id, $message, $telegram->buildInlineKeyBoard( [
                            [
                                $telegram->buildInlineKeyboardButton( $i, '', 'counter' )
                            ]
                        ] )
                        );

                        break;

                    case 'get_message_for_send_event':

                        $user->setStatus( '' );
                        $message    = '⚠️ عملیات ارسال پیام همگانی برای شرکت کنندگان شروع شد ... توجه این عملیات ممکن است زمان بر باشد.' . "\n \n";
                        $message    .= '⭕️ عددی که در زیر مشاهده میکنید عدد تعداد پیام ارسالی موفق به کاربران است.';
                        $message    = $telegram->sendMessage(
                            $user->getUserId(), $message, $telegram->buildInlineKeyBoard( [
                            [
                                $telegram->buildInlineKeyboardButton( '0', '', 'counter' )
                            ]
                        ] )
                        );
                        $message_id = $message[ 'result' ][ 'message_id' ];
                        $i          = 0;

                        $events = ParticipantEvents::where( 'event_id', $user->data[ 'id' ] )->get();

                        foreach ( $events as $item )
                        {
                            $result = $telegram->copyMessage( $item->user_id, $user->getUserId(), $this->message_id );
                            if ( isset( $result[ 'result' ][ 'message_id' ] ) ) $i ++;
                            if ( $i % 10 == 0 )
                            {
                                $telegram->editKeyboard(
                                    $user->getUserId(), $message_id, $telegram->buildInlineKeyBoard( [
                                    [
                                        $telegram->buildInlineKeyboardButton( $i, '', 'counter' )
                                    ]
                                ] )
                                );
                            }
                        }

                        $message = '✅ عملیات ارسال پیام همگانی برای شرکت کنندگان با موفقیت به پایان رسید.';
                        $telegram->editMessageText(
                            $user->getUserId(), $message_id, $message, $telegram->buildInlineKeyBoard( [
                            [
                                $telegram->buildInlineKeyboardButton( $i, '', 'counter' )
                            ]
                        ] )
                        );


                        break;

                    case 'get_message_for_send_form':

                        $user->setStatus( '' );
                        $message    = '⚠️ عملیات ارسال پیام همگانی برای شرکت کنندگان شروع شد ... توجه این عملیات ممکن است زمان بر باشد.' . "\n \n";
                        $message    .= '⭕️ عددی که در زیر مشاهده میکنید عدد تعداد پیام ارسالی موفق به کاربران است.';
                        $message    = $telegram->sendMessage(
                            $user->getUserId(), $message, $telegram->buildInlineKeyBoard( [
                            [
                                $telegram->buildInlineKeyboardButton( '0', '', 'counter' )
                            ]
                        ] )
                        );
                        $message_id = $message[ 'result' ][ 'message_id' ];
                        $i          = 0;

                        $events = UsersForm::where( 'form_id', $user->data[ 'id' ] )->get();

                        foreach ( $events as $item )
                        {
                            $result = $telegram->copyMessage( $item->user_id, $user->getUserId(), $this->message_id );
                            if ( isset( $result[ 'result' ][ 'message_id' ] ) ) $i ++;
                            if ( $i % 10 == 0 )
                            {
                                $telegram->editKeyboard(
                                    $user->getUserId(), $message_id, $telegram->buildInlineKeyBoard( [
                                    [
                                        $telegram->buildInlineKeyboardButton( $i, '', 'counter' )
                                    ]
                                ] )
                                );
                            }
                        }

                        $message = '✅ عملیات ارسال پیام همگانی برای شرکت کنندگان با موفقیت به پایان رسید.';
                        $telegram->editMessageText(
                            $user->getUserId(), $message_id, $message, $telegram->buildInlineKeyBoard( [
                            [
                                $telegram->buildInlineKeyboardButton( $i, '', 'counter' )
                            ]
                        ] )
                        );


                        break;

                    #ویرایش پیام های ربات

                    case 'edit_message':


                        if ( isset( $this->text ) && is_string( $this->text ) )
                        {

                            Message::find( $user->data )->update( [
                                'contact' => $this->text
                            ] );
                            $message = '✅ پیام با موفقیت بروزرسانی شد.';
                            $user->setKeyboard( $this->adminMenu() )->SendMessageHtml( $message )->clearStatus()->clearData();

                        }
                        else
                        {
                            throw new ExceptionWarning( 'شما باید یک متن ارسال کنید.' );
                        }


                        break;

                    #مدیریت منو های ربات

                    case 'get_name_new_sub_menu':

                        if ( $this->is_text() )
                        {

                            $message = '🔹 لطفا نوع دکمه را انتخاب کنید.';
                            $user->setKeyboard(
                                $telegram->buildInlineKeyBoard( [
                                    [
                                        $telegram->buildInlineKeyboardButton( '✏️ متنی', '', 'select_type_new_menu-text-get_content_new_sub_menu' ),
                                    ],
                                    [
                                        $telegram->buildInlineKeyboardButton( '📦 پست کانال', '', 'select_type_new_menu-message-get_content_new_sub_menu' ),
                                    ],
                                ] )
                            )->SendMessageHtml( $message )->setStatus( 'select_new_sub_menu' )->setData( [
                                'name' => $this->text
                            ] );

                        }
                        else
                        {
                            throw new ExceptionWarning( 'شما باید یک متن ارسال کنید' );
                        }


                        break;

                    case 'get_name_new_menu':

                        if ( $this->is_text() )
                        {

                            $message = '🔹 لطفا نوع دکمه را انتخاب کنید.';
                            $user->setKeyboard(
                                $telegram->buildInlineKeyBoard( [
                                    [
                                        $telegram->buildInlineKeyboardButton( '✏️ متنی', '', 'select_type_new_menu-text-get_content_new_menu' ),
                                    ],
                                    [
                                        $telegram->buildInlineKeyboardButton( '📦 پست کانال', '', 'select_type_new_menu-message-get_content_new_menu' ),
                                    ],
                                ] )
                            )->SendMessageHtml( $message )->setStatus( 'select_new_sub_menu' )->setData(
                                array_merge( $user->data, [
                                    'name' => $this->text
                                ] )
                            );

                        }
                        else
                        {
                            throw new ExceptionWarning( 'شما باید یک متن ارسال کنید' );
                        }


                        break;

                    case 'get_content_new_sub_menu':
                    case 'get_content_new_menu':

                        Menu::on()->create( [

                            'parent'     => $user->step,
                            'row'        => $user->data[ 'row' ] ?? 0,
                            'col'        => $user->data[ 'col' ] ?? 0,
                            'name'       => $user->data[ 'name' ],
                            'user_id'    => $user->getUserId(),
                            'message_id' => $this->message_id,
                            'message'    => $this->text,
                            'status'     => 1

                        ] );
                        $message = 'منو با موفقیت اضافه شد';
                        $user->SendMessageHtml( $message )->clearData()->clearStep()->clearStatus();


                        break;

                    case 'get_name_new_row_menu':

                        if ( $this->is_text() )
                        {


                            $message = '🔹 لطفا نوع دکمه را انتخاب کنید.';
                            $user->setKeyboard(
                                $telegram->buildInlineKeyBoard( [
                                    [
                                        $telegram->buildInlineKeyboardButton( '✏️ متنی', '', 'select_type_new_menu-text-get_content_new_row_menu' ),
                                    ],
                                    [
                                        $telegram->buildInlineKeyboardButton( '📦 پست کانال', '', 'select_type_new_menu-message-get_content_new_row_menu' ),
                                    ],
                                ] )
                            )->SendMessageHtml( $message )->setStatus( 'select_new_sub_menu' )->setData(
                                array_merge( ( $user->data ?? [] ), [
                                    'name' => $this->text
                                ] )
                            );

                        }
                        else
                        {
                            throw new ExceptionWarning( 'شما باید یک متن ارسال کنید' );
                        }


                        break;
                    case 'get_content_new_row_menu':


                        $menu = Menu::on()->find( $user->step );
                        Menu::on()->create( [

                            'parent'     => $menu->parent,
                            'row'        => $menu->row + 1,
                            'col'        => 0,
                            'name'       => $user->data[ 'name' ],
                            'user_id'    => $user->getUserId(),
                            'message_id' => $this->message_id,
                            'message'    => $this->text,

                        ] );
                        $message = 'منو با موفقیت اضافه شد';
                        $user->SendMessageHtml( $message )->clearData()->clearStep()->clearStatus();


                        break;

                    # ثبت نام دانشجو جدید

                    case 'register_new_user':

                        switch ( $user->step )
                        {

                            case 1:

                                if ( $this->is_number() )
                                {

                                    if ( ! Student::on()->where( 'students_id', $this->text )->exists() )
                                    {

                                        $message = '💢 کد ملی یا رمز عبور دانشجو را ارسال کنید:';
                                        $user->SendMessageHtml( $message )->setStep( 2 )->setData( [
                                            'stu' => $this->text
                                        ] );

                                    }
                                    else
                                    {
                                        throw new ExceptionWarning( 'این شماره دانشجویی قبلا وجود دارد.' );
                                    }

                                }
                                else
                                {
                                    throw new ExceptionWarning( 'شماره دانشجویی تنها میتواند یک عدد باشد.' );
                                }

                                break;

                            case 2:

                                if ( $this->is_text() )
                                {

                                    $message = '💢 نام دانشجو را ارسال کنید:';
                                    $user->SendMessageHtml( $message )->setStep( 3 )->setData( [
                                        'stu'  => $user->data[ 'stu' ],
                                        'pass' => $this->text
                                    ] );

                                }
                                else
                                {
                                    throw new ExceptionWarning( 'شما باید یک متن یا عدد وارد کنید.' );
                                }

                                break;

                            case 3:

                                if ( $this->is_text() )
                                {

                                    $message = '💢 نام خانوادگی دانشجو را ارسال کنید:';
                                    $user->SendMessageHtml( $message )->setStep( 4 )->setData( [
                                        'stu'  => $user->data[ 'stu' ],
                                        'pass' => $user->data[ 'pass' ],
                                        'name' => $this->text
                                    ] );

                                }
                                else
                                {
                                    throw new ExceptionWarning( 'شما باید یک متن یا عدد وارد کنید.' );
                                }

                                break;

                            case 4:

                                if ( $this->is_text() )
                                {

                                    $keyboard = [];
                                    $message  = '👇 لطفا انتخاب کنید دانشجویی که میخواهید آن را اضافه کنید جز کدام دانشگاه می باشد👇';
                                    foreach ( University::all() as $item )
                                    {
                                        $keyboard[][] = $telegram->buildInlineKeyboardButton( '🏢 ' . $item->name, '', 'add_new_user_2-' . $item->id );
                                    }
                                    $user->setKeyboard( $telegram->buildInlineKeyBoard( $keyboard ) )->SendMessageHtml( $message )->clearStatus()->clearStep()->setData( [
                                        'stu'    => $user->data[ 'stu' ],
                                        'pass'   => $user->data[ 'pass' ],
                                        'name'   => $user->data[ 'name' ],
                                        'family' => $this->text,
                                    ] );

                                }
                                else
                                {
                                    throw new ExceptionWarning( 'شما باید یک متن یا عدد وارد کنید.' );
                                }

                                break;

                        }

                        break;

                    case 'student_info':

                        if ( $this->is_text() )
                        {

                            $student = Student::where( 'students_id', $this->text )->first();

                            if ( isset( $student->id ) )
                            {

                                $message = '👤 نام و نام خانوادگی: ' . "<b><u>" . $student->first_name . ' ' . $student->last_name . "</u></b>" . "\n";
                                $message .= '🎗 شماره دانشجویی: ' . "<b><code>" . $student->students_id . "</code></b>" . "\n";
                                $message .= '🏢 دانشگاه: ' . "<b>" . $student->uni->name . "</b>" . "\n";
                                $message .= '🎓 رشته تحصیلی: ' . "<b>" . $student->section->name . "</b>" . "\n";
                                if ( ! empty( $student->login_at ) ) $message .= '💠 تاریخ ورود به حساب: ' . "\n" . Str::code( jdate( $student->login_at )->format( 'Y/m/d H:i:d' ) );

                                $user->setKeyboard(
                                    $telegram->buildInlineKeyBoard( [
                                        [
                                            $telegram->buildInlineKeyboardButton( text: '📯 اضافه کردن به یک رویداد', callback_data: 'add_student_to_event-' . $student->id )
                                        ],
                                        [
                                            $telegram->buildInlineKeyboardButton( text: '🗑 حذف دانشجو', callback_data: 'delete_student-' . $student->id ),
                                            $telegram->buildInlineKeyboardButton( text: '✏️ ویرایش دانشجو', callback_data: 'edit_student-' . $student->id ),
                                        ],
                                        [
                                            $telegram->buildInlineKeyboardButton( text: '❌ بستن پنل', callback_data: 'cancel' ),
                                        ]
                                    ] )
                                )->SendMessageHtml( $message );

                            }
                            else
                            {

                                throw new ExceptionError( 'شماره دانشجویی وجود ندارد.' );

                            }


                        }
                        else
                        {
                            throw new ExceptionWarning( 'شما باید یک متن ارسال کنید' );
                        }

                        break;

                    case 'edit_student':

                        if ( $this->is_text() )
                        {

                            Student::where( 'id', $user->data[ 'id' ] )->update( [
                                $user->data[ 'type' ] => $this->text
                            ] );
                            $message = 'عملیات ویرایش با موفقیت انجام شد✅';
                            $user->SendMessageHtml( $message )->reset();

                        }
                        else
                        {
                            throw new ExceptionWarning( 'تنها متن و عدد مورد قبول است.' );
                        }

                        break;

                    # ثبت رویداد جدید

                    case 'new_event_1':

                        switch ( $user->step )
                        {

                            case 1:

                                if ( $this->is_text() )
                                {

                                    $message = '🔻 سر فصل های دوره را ارسال کنید:';
                                    $user->SendMessageHtml( $message )->setStep( 2 )->setData( [
                                        'title' => $this->text,
                                        'type'  => 1
                                    ] );

                                }
                                else
                                {
                                    throw new ExceptionWarning( 'شما باید یک متن ارسال کنید.' );
                                }

                                break;

                            case 2:

                                if ( $this->is_text() )
                                {

                                    $message = '▪️ عکس پوستر دوره را ارسال کنید:';
                                    $user->SendMessageHtml( $message )->setStep( 3 )->setData(
                                        array_merge( $user->data, [
                                            'topics' => $this->text
                                        ] )
                                    );

                                }
                                else
                                {
                                    throw new ExceptionWarning( 'شما باید یک متن ارسال کنید.' );
                                }

                                break;

                            case 3:

                                if ( $this->is_photo() )
                                {

                                    $message = '🧑‍🏫 نام مدرس دوره را وارد کنید:';
                                    $user->SendMessageHtml( $message )->setStep( 4 )->setData(
                                        array_merge( $user->data, [
                                            'file_id' => $this->photo0_id
                                        ] )
                                    );

                                }
                                else
                                {
                                    throw new ExceptionWarning( 'شما باید یک عکس ارسال کنید.' );
                                }

                                break;

                            case 4:

                                if ( $this->is_text() )
                                {

                                    $message = '💰 قیمت دوره را وارد کنید:';
                                    $user->SendMessageHtml( $message )->setStep( 5 )->setData(
                                        array_merge( $user->data, [
                                            'teacher_name' => $this->text
                                        ] )
                                    );

                                }
                                else
                                {
                                    throw new ExceptionWarning( 'شما باید یک متن ارسال کنید.' );
                                }

                                break;

                            case 5:

                                if ( $this->is_number() )
                                {

                                    $message = '📜 توضیحاتی در مورد اگر میخواهید وارد کنید:' . "\n \n";
                                    $message .= '📍 در صورتی که میخواهید هیچ چیزی وارد نکنید دستور /null را ارسال کنید.';
                                    $user->SendMessageHtml( $message )->setStep( 6 )->setData(
                                        array_merge( $user->data, [
                                            'amount' => $this->text
                                        ] )
                                    );

                                }
                                else
                                {
                                    throw new ExceptionWarning( 'شما باید یک عدد ارسال کنید.' );
                                }

                                break;

                            case 6:

                                if ( $this->is_text() )
                                {

                                    $message = '👤 تعداد کاربرانی که میتوانند شرکت کنند را وارد کنید:' . "\n \n";
                                    $message .= '📍 برای ایجاد بدون محدودیت ثبت نام دستور /null را ارسال کنید.';
                                    $user->SendMessageHtml( $message )->setStep( 7 )->setData(
                                        array_merge( $user->data, [
                                            'description' => ( $this->text == '/null' ? null : $this->text )
                                        ] )
                                    );

                                }
                                else
                                {
                                    throw new ExceptionWarning( 'شما باید یک متن ارسال کنید.' );
                                }

                                break;

                            case 7:

                                if ( $this->is_number() )
                                {

                                    $message = '🗓 تاریخ پایان ثبت نام را لطفا وارد کنید:' . "\n";
                                    $message .= 'مثال:';
                                    $message .= "<code>" . jdate()->format( 'Y/m/d' ) . "</code>";
                                    $user->SendMessageHtml( $message )->setStep( 8 )->setData(
                                        array_merge( $user->data, [
                                            'count' => intval( $this->text )
                                        ] )
                                    );

                                }
                                else
                                {
                                    throw new ExceptionWarning( 'شما باید یک عدد ارسال کنید.' );
                                }

                                break;

                            case 8:

                                if ( $this->is_text() )
                                {

                                    $validation = new PersianValidators();

                                    if ( $validation->validateShamsiDate( '', $this->text, [ 'persian' ] ) )
                                    {

                                        $timestamp = Jalalian::fromFormat( 'Y/m/d', $this->text )->getTimestamp();

                                        $message = '💢 نوع ثبت نام دوره چگونه باشد؟';
                                        $user->setKeyboard(
                                            $telegram->buildInlineKeyBoard( [
                                                [
                                                    $telegram->buildInlineKeyboardButton( '✔️ پرداخت و رایگان برای افراد وارد شده', '', 'add_event-1' ),
                                                ],
                                                [
                                                    $telegram->buildInlineKeyboardButton( '✔️ فقط برای افراد وارد شده', '', 'add_event-2' ),
                                                ],
                                                [
                                                    $telegram->buildInlineKeyboardButton( '✔️ پرداخت تنها برای افراد وارد شده', '', 'add_event-3' ),
                                                ],
                                                [
                                                    $telegram->buildInlineKeyboardButton( '✔️ تنها از طریق پرداخت', '', 'add_event-0' ),
                                                ]
                                            ] )
                                        )->SendMessageHtml( $message )->setData(
                                            array_merge( $user->data, [
                                                'available_at' => date( 'Y-m-d 00:00:00', $timestamp )
                                            ] )
                                        )->clearStatus()->clearStep();

                                    }
                                    else
                                    {
                                        throw new ExceptionWarning( 'تاریخی که ارسال کرده اید اشتباه است.' );
                                    }

                                }
                                else
                                {
                                    throw new ExceptionWarning( 'شما باید یک متن ارسال کنید.' );
                                }

                                break;

                        }

                        break;

                    case 'new_event_2':

                        switch ( $user->step )
                        {

                            case 1:

                                if ( $this->is_text() )
                                {

                                    $message = '💢 متن نمایشی به هنگام اشتراکی مسابقه رو ارسال کنید:';
                                    $user->SendMessageHtml( $message )->setData( [
                                        'type'  => 2,
                                        'title' => $this->text
                                    ] )->setStep( $user->step + 1 );

                                }
                                else
                                {

                                    throw new ExceptionWarning( 'شما باید یک متن ارسال کنید.' );

                                }

                                break;

                            case 2:

                                if ( $this->is_text() )
                                {

                                    $message = '🏞 عکس پوستر مسابقات را ارسال کنید:';
                                    $user->SendMessageHtml( $message )->setData(
                                        array_merge( $user->data, [
                                            'topics' => $this->text
                                        ] )
                                    )->setStep( $user->step + 1 );

                                }
                                else
                                {

                                    throw new ExceptionWarning( 'شما باید یک متن ارسال کنید.' );

                                }

                                break;

                            case 3:

                                if ( $this->is_photo() )
                                {

                                    $message = '🤝 اسامی افرادی که در این مسابقات حامی یا اسپانسر شما بوده اند را وارد کنید:' . "\n \n";
                                    $message .= '⚠️ در صورتی که میخواهید این بخش را خالی بزارید میتوانید /skip را ارسال کنید.';
                                    $user->SendMessageHtml( $message )->setData(
                                        array_merge( $user->data, [
                                            'file_id' => $this->photo0_id
                                        ] )
                                    )->setStep( $user->step + 1 );

                                }
                                else
                                {

                                    throw new ExceptionWarning( 'شما باید یک عکس ارسال کنید.' );

                                }

                                break;

                            case 4:

                                if ( $this->is_text() )
                                {

                                    $message = '💰 هزینه ثبت نام مسابقه را  به تومان وارد کنید:' . "\n \n";
                                    $message .= '⚠️ توجه اگر مسابقه قرار است به صورت تیمی برگزار شود هزینه را برای کل افراد حساب کنید.';
                                    $user->SendMessageHtml( $message )->setData(
                                        array_merge( $user->data, [
                                            'teacher_name' => ( $this->text == '/skip' ? null : $this->text )
                                        ] )
                                    )->setStep( $user->step + 1 );

                                }
                                else
                                {

                                    throw new ExceptionWarning( 'شما باید یک متن ارسال کنید.' );

                                }

                                break;

                            case 5:

                                if ( $this->is_text() && $this->is_number() )
                                {

                                    $message = "📜 شرایط ثبت نام را وارد کنید:";
                                    $user->SendMessageHtml( $message )->setData(
                                        array_merge( $user->data, [
                                            'amount' => $this->text
                                        ] )
                                    )->setStep( $user->step + 1 );

                                }
                                else
                                {

                                    throw new ExceptionWarning( 'شما باید یک عدد ارسال کنید.' );

                                }

                                break;

                            case 6:

                                if ( $this->is_text() )
                                {

                                    $message = '👥 تعداد افراد/تیم هایی که میتوانند ثبت نام کنند را وارد کنید.';
                                    $user->SendMessageHtml( $message )->setData(
                                        array_merge( $user->data, [
                                            'description' => $this->text
                                        ] )
                                    )->setStep( $user->step + 1 );

                                }
                                else
                                {

                                    throw new ExceptionWarning( 'شما باید یک متن ارسال کنید.' );

                                }

                                break;

                            case 7:

                                if ( $this->is_number() )
                                {

                                    $message = '📝 ثبت نام به صورت انفرادی یا تیمی برگزار می شود؟' . "\n \n";
                                    $message .= '⚠️ لطفا از منو انتخاب کنید.';
                                    $user->setKeyboard(
                                        $telegram->buildKeyBoard( [
                                            [
                                                $telegram->buildKeyboardButton( '👤 انفرادی' ),
                                                $telegram->buildKeyboardButton( '👥 تیمی' ),
                                            ]
                                        ] )
                                    )->SendMessageHtml( $message )->setData(
                                        array_merge( $user->data, [
                                            'count' => $this->text
                                        ] )
                                    )->setStep( $user->step + 1 );

                                }
                                else
                                {

                                    throw new ExceptionWarning( 'شما باید یک متن ارسال کنید.' );

                                }

                                break;

                            case 8:

                                if ( $this->is_text() && in_array( $this->text, [ '👤 انفرادی', '👥 تیمی' ] ) )
                                {

                                    if ( $this->text == '👥 تیمی' )
                                    {

                                        $user->setStep( 9 );
                                        $message = '✔️ لطفا انتخاب کنید تعداد هر تیم چند نفر می باشد؟';

                                    }
                                    else
                                    {

                                        $user->setStep( 10 );
                                        $message = '🗓 تاریخ پایان ثبت نام را لطفا وارد کنید:' . "\n";
                                        $message .= 'مثال:';
                                        $message .= "<code>" . jdate()->format( 'Y/m/d' ) . "</code>";

                                    }

                                    $user->setKeyboard( $telegram->buildKeyBoardHide() )->SendMessageHtml( $message )->setData(
                                        array_merge( $user->data, [
                                            'data' => [ 'type_join' => ( $this->text == '👥 تیمی' ? 2 : 1 ) ]
                                        ] )
                                    );

                                }
                                else
                                {

                                    throw new ExceptionWarning( 'لطفا از کیبورد استفاده کنید.' );

                                }

                                break;

                            case 9:

                                if ( $this->is_number() )
                                {

                                    $message = '🔖 ببنید درست میگم یا نه؟' . "\n";
                                    $message .= '👥 ' . $user->data[ 'count' ] . 'تیم که هر تیم دارای ' . $this->text . ' عضو است.' . "\n";
                                    $message .= '🦦 پس به طور کلی ما ' . $user->data[ 'count' ] . ' تیم و ' . ( $this->text * $user->data[ 'count' ] ) . ' اعضا ثبت نامی باید داشته باشیم.' . "\n \n";
                                    $message .= '🗓 تاریخ پایان ثبت نام را لطفا وارد کنید:' . "\n";
                                    $message .= 'مثال:';
                                    $message .= "<code>" . jdate()->format( 'Y/m/d' ) . "</code>";
                                    $user->SendMessageHtml( $message )->setData(
                                        array_merge( $user->data, [
                                            'data' => array_merge( $user->data[ 'data' ], [ 'count_team' => $this->text ] )
                                        ] )
                                    )->setStep( $user->step + 1 );

                                }
                                else
                                {

                                    throw new ExceptionWarning( 'شما باید یک متن ارسال کنید.' );

                                }

                                break;

                            case 10:

                                if ( $this->is_text() )
                                {

                                    $validation = new PersianValidators();

                                    if ( $validation->validateShamsiDate( '', $this->text, [ 'persian' ] ) )
                                    {

                                        $timestamp = Jalalian::fromFormat( 'Y/m/d', $this->text )->getTimestamp();

                                        $message = '💢 نوع ثبت نام دوره چگونه باشد؟';
                                        $user->setKeyboard(
                                            $telegram->buildInlineKeyBoard( [
                                                [
                                                    $telegram->buildInlineKeyboardButton( '✔️ پرداخت و رایگان برای افراد وارد شده', '', 'add_event-1' ),
                                                ],
                                                [
                                                    $telegram->buildInlineKeyboardButton( '✔️ فقط برای افراد وارد شده', '', 'add_event-2' ),
                                                ],
                                                [
                                                    $telegram->buildInlineKeyboardButton( '✔️ پرداخت تنها برای افراد وارد شده', '', 'add_event-3' ),
                                                ],
                                                [
                                                    $telegram->buildInlineKeyboardButton( '✔️ تنها از طریق پرداخت', '', 'add_event-0' ),
                                                ]
                                            ] )
                                        )->SendMessageHtml( $message )->setData(
                                            array_merge( $user->data, [
                                                'available_at' => date( 'Y-m-d 00:00:00', $timestamp )
                                            ] )
                                        )->clearStatus()->clearStep();

                                    }
                                    else
                                    {
                                        throw new ExceptionWarning( 'تاریخی که ارسال کرده اید اشتباه است.' );
                                    }

                                }
                                else
                                {
                                    throw new ExceptionWarning( 'شما باید یک متن ارسال کنید.' );
                                }

                                break;

                        }

                        break;

                    case 'edit_event':

                        if ( ! empty( $this->photo0_id ) ) $this->text = $this->photo0_id;

                        $validation = new PersianValidators();
                        if ( $validation->validateShamsiDate( '', $this->text, [ 'persian' ] ) )
                        {

                            $timestamp  = Jalalian::fromFormat( 'Y/m/d', $this->text )->getTimestamp();
                            $this->text = date( 'Y-m-d 00:00:00', $timestamp );

                        }

                        if ( $this->is_text() )
                        {

                            Event::where( 'id', $user->data[ 'event' ] )->update( [
                                $user->data[ 'type' ] => $this->text
                            ] );
                            $message = 'عملیات ویرایش با موفقیت انجام شد✅';
                            $user->SendMessageHtml( $message )->reset();

                        }
                        else
                        {
                            throw new ExceptionWarning( 'تنها متن و عدد مورد قبول است.' );
                        }

                        break;

                    #مدیریت دانشگاه ها

                    case 'new_universities':

                        switch ( $user->step )
                        {

                            case 1:

                                if ( $this->is_text() )
                                {

                                    $message = '⚜️ در صورتی که میخواهید کد دانشگاه رو وارد کنید آن را ارسال کنید در غیر این صورت دستور /null را ارسال کنید.';
                                    $user->SendMessageHtml( $message )->setStep( 2 )->setData( $this->text );

                                }
                                else
                                {
                                    throw new ExceptionWarning( 'شما باید یک متن ارسال کنید.' );
                                }

                                break;

                            case 2:

                                if ( $this->is_text() )
                                {

                                    University::create( [ 'name' => $user->data, 'code' => ( $this->text == '/null' ? null : $this->text ) ] );
                                    $message = 'دانشگاه جدید با موفقیت ثبت شد✅';
                                    $user->SendMessageHtml( $message )->reset();

                                }
                                else
                                {
                                    throw new ExceptionWarning( 'شما باید یک متن ارسال کنید.' );
                                }

                                break;

                        }

                        break;

                    #مدیریت رشته ها

                    case 'new_section':

                        switch ( $user->step )
                        {

                            case 1:

                                if ( $this->is_text() )
                                {

                                    $message = '⚜️ در صورتی که میخواهید کد رشته رو وارد کنید آن را ارسال کنید در غیر این صورت دستور /null را ارسال کنید.';
                                    $user->SendMessageHtml( $message )->setStep( 2 )->setData( $this->text );

                                }
                                else
                                {
                                    throw new ExceptionWarning( 'شما باید یک متن ارسال کنید.' );
                                }

                                break;

                            case 2:

                                if ( $this->is_text() )
                                {

                                    Section::create( [ 'name' => $user->data, 'code' => ( $this->text == '/null' ? null : $this->text ) ] );
                                    $message = 'رشته جدید با موفقیت ثبت شد✅';
                                    $user->SendMessageHtml( $message )->reset();

                                }
                                else
                                {
                                    throw new ExceptionWarning( 'شما باید یک متن ارسال کنید.' );
                                }

                                break;

                        }

                        break;

                    # مدیریت فرم

                    case 'new_form':


                        switch ( $user->step )
                        {

                            case 1:

                                $message = '📍 نام فرم را وارد کنید.';
                                $user->SendMessageHtml( $message )->setStep( 2 )->setData( [

                                    'user_id'    => $user->getUserId(),
                                    'message_id' => $this->message_id,

                                ] );

                                break;

                            case 2:


                                if ( $this->is_text() )
                                {

                                    $message = '📍 ایدی عددی جایی که میخواهید لیست ثبت نامی ها به انها ارسال شود را وارد کنید:';
                                    $user->SendMessageHtml( $message )->setStep( 4 )->setData(
                                        array_merge( $user->data, [

                                            'name' => $this->text

                                        ] )
                                    );

                                }
                                else
                                {
                                    throw new ExceptionWarning( 'شما باید یک متن ارسال کنید.' );
                                }


                                break;

                            case 4:

                                if ( $this->is_text() )
                                {

                                    $message = '✔️ مراحل دریافت اطلاعات شروع شد. سوالات را ارسال کنید.';
                                    $user->SendMessageHtml( $message )->setStep( 3 )->setData(
                                        array_merge( $user->data, [

                                            'send_to' => $this->text

                                        ] )
                                    );

                                }
                                else
                                {
                                    throw new ExceptionWarning( 'شما باید یک متن ارسال کنید.' );
                                }

                                break;

                            case 3:


                                if ( $this->is_text() )
                                {

                                    $message = '👇 لطفا نوع ورودی داده را مشخص کنید.';
                                    $user->setKeyboard(
                                        $telegram->buildInlineKeyBoard( [
                                            [
                                                $telegram->buildInlineKeyboardButton( text: '📝 متن', callback_data: 'set_filter_form-text' ),
                                                $telegram->buildInlineKeyboardButton( text: '📝 متن فارسی', callback_data: 'set_filter_form-persian_text' ),
                                            ],
                                            [
                                                $telegram->buildInlineKeyboardButton( text: '0️⃣ عدد', callback_data: 'set_filter_form-number' ),
                                                $telegram->buildInlineKeyboardButton( text: '💳 پرداخت', callback_data: 'set_filter_form-payment' ),
                                            ],
                                            [
                                                $telegram->buildInlineKeyboardButton( text: '📞 شماره تلفن', callback_data: 'set_filter_form-phone' ),
                                                $telegram->buildInlineKeyboardButton( text: '🪪 کد ملی', callback_data: 'set_filter_form-national_code' ),
                                            ]
                                        ] )
                                    )->SendMessageHtml( $message )->clearStatus()->clearStep()->setData(
                                        array_merge( $user->data, [
                                            'question' => $this->text
                                        ] )
                                    );

                                }
                                else
                                {
                                    throw new ExceptionWarning( 'شما باید یک متن ارسال کنید.' );
                                }


                                break;

                        }


                        break;

                    case 'edit_form':

                        if ( $user->data[ 'type' ] == 'form' )
                        {

                            Form::where( 'id', $user->data[ 'form' ] )->update( [
                                'user_id'    => $user->getUserId(),
                                'message_id' => $this->message_id
                            ] );
                            $message = 'عملیات ویرایش با موفقیت انجام شد✅';
                            $user->SendMessageHtml( $message )->reset();

                        }
                        elseif ( $this->is_text() )
                        {

                            Form::where( 'id', $user->data[ 'form' ] )->update( [
                                $user->data[ 'type' ] => $this->text
                            ] );
                            $message = 'عملیات ویرایش با موفقیت انجام شد✅';
                            $user->SendMessageHtml( $message )->reset();

                        }
                        else
                        {
                            throw new ExceptionWarning( 'تنها متن و عدد مورد قبول است.' );
                        }

                        break;

                    // -----------------------------------

                    # مدیریت فایل

                    case 'get_folder_name':

                        if ( $this->is_text() && Files::where( 'hash', $this->text )->exists() )
                        {

                            foreach ( Files::where( 'hash', $this->text )->get() as $item )
                            {

                                $telegram->copyMessage( $user->getUserId(), $item->user_id, $item->message_id );

                            }

                        }
                        else
                        {
                            throw new ExceptionWarning( 'شما باید یک متن ارسال کنید' );
                        }

                        break;

                    case 'get_new_file':

                        if ( $this->is_text() && Files::where( 'hash', $this->text )->exists() )
                        {

                            $message = '🪄هیششش 🤫 فایل جدید رو ارسال کن🤭';
                            $user->SendMessageHtml( $message )->setStatus( 'get_file_for_folder' )->setData( [ 'id' => $this->text ] );

                        }
                        else
                        {

                            $hash = uniqid();

                            Files::create( [

                                'hash'       => $hash,
                                'user_id'    => $user->getUserId(),
                                'message_id' => $this->message_id

                            ] );

                            $link    = ( 'https://t.me/' . $telegram->getMe()[ 'result' ][ 'username' ] . '?start=file-' );
                            $message = '⚜️ فایل جدید با موفقیت ساخته شد✅' . "\n \n";
                            $message .= '💠 آدرس فایل: ' . "\n";
                            $message .= $link . $hash;
                            $user->setKeyboard(
                                $telegram->buildKeyBoard( [
                                    [
                                        $telegram->buildKeyboardButton( '📓 لیست فایل ها' ),
                                        $telegram->buildKeyboardButton( '📁 فایل جدید' ),
                                    ],
                                    [
                                        $telegram->buildInlineKeyboardButton( '▶️ برگشت به منو اصلی' )
                                    ]
                                ] )
                            )->SendMessageHtml( $message )->reset();

                        }

                        break;

                    case 'get_file_for_folder':

                        Files::create( [

                            'hash'       => $user->data[ 'id' ],
                            'user_id'    => $user->getUserId(),
                            'message_id' => $this->message_id

                        ] );

                        $link    = ( 'https://t.me/' . $telegram->getMe()[ 'result' ][ 'username' ] . '?start=file-' );
                        $message = '⚜️ فایل جدید با موفقیت به فولدر اضافه شد✅' . "\n \n";
                        $message .= '💠 فایل بعدی رو میتونی ارسال کنید';
                        $user->SendMessageHtml( $message )->setStatus( 'get_file_for_folder' );

                        break;

                    // -----------------------------------

                    case 'artisan':

                        if ( $this->is_text() )
                        {


                            try
                            {

                                \Artisan::call( $this->text );
                                $message = "💠 Result Of Command : '" . "\n" . Str::code( $this->text ) . "'" . "\n \n";
                                $message .= Str::codeB( \Artisan::output() );
                                $user->SendMessageHtml( $message );

                            }
                            catch ( \Exception $e )
                            {

                                $message = '❌ ' . Str::b( $e->getMessage() );
                                $user->SendMessageHtml( $message );

                            }

                        }
                        else
                        {

                            $user->SendMessageHtml( '⚠️ Undefiled' );

                        }

                        break;

                    // ---------------------------

                    default:
                        goto START_BOT_ADMIN;

                }

                break;

        }

    }

    public function manager()
    {
        $user     = new User( $this->chat_id );
        $telegram = tel();

        switch ( $this->text )
        {

            case '▶️ برگشت به منو اصلی':
            case '/start':

                START_BOT:
                $message = '🌟 به پنل مدیریتی خود خوش آمدید⭐️';
                $user->setKeyboard(
                    $telegram->buildKeyBoard( [
                        [
                            $telegram->buildKeyboardButton( '🎯 رویداد ها' )
                        ]
                    ] )
                )->SendMessageHtml( $message )->reset();

                break;

            case '🎯 رویداد ها':

                $events = Event::all();

                if ( count( $events ) > 0 )
                {

                    foreach ( $events as $item )
                    {

                        switch ( $item->type )
                        {

                            case 1:

                                $count   = ParticipantEvents::where( 'event_id', $item->id )->count();
                                $message = '🎓 دوره:  ' . Str::b( $item->title ) . "\n \n";
                                $message .= '🧑‍🏫 مدرس: ' . Str::u( $item->teacher_name ) . "\n \n";
                                $message .= '💰 هزینه دوره: ' . Str::b( number_format( $item->amount ) . ' تومان' ) . ' ' . ( in_array( $item->free_login_user, [ 1, 2 ] ) ? Str::bu( '( برای دانشجویان دانشکده منتظری رایگان )' ) : '' ) . "\n \n";
                                $message .= '👤 ظرفیت دوره: ' . Str::u( $item->count . ' نفر' ) . "\n";
                                $message .= '👨🏻‍🎓 تعداد دانشجو: ' . Str::u( $count . ' نفر' ) . "\n \n";

                                if ( $item->available_at > date( 'Y-m-d' ) )
                                {
                                    $message .= '🗓 زمان باقی مانده جهت ثبت نام: ' . "\n";
                                    $message .= Str::b( Str::date( $item->available_at ) ) . "\n \n";
                                }
                                else
                                {
                                    $message .= '❌ مهلت ثبت نام تمام شده است.' . "\n \n";
                                }
                                $message .= 'https://t.me/' . $telegram->getMe()[ 'result' ][ 'username' ] . '?start=' . $item->hash;

                                $telegram->sendPhoto(
                                    $user->getUserId(), $item->file_id, $message, $telegram->buildInlineKeyBoard( [
                                    [
                                        $telegram->buildInlineKeyboardButton( '📜 لیست شرکت کنندگان', '', 'list_participate_event-' . $item->id ),
                                        $telegram->buildInlineKeyboardButton( '👤 حضور و غیاب', '', 'roll_call_event-' . $item->id )
                                    ],
                                    [
                                        $telegram->buildInlineKeyboardButton( '🗑 لغو ثبت نام کاربر', '', 'remove_user_event-' . $item->id ),
                                    ],
                                    [
                                        $telegram->buildInlineKeyboardButton( '📮 ارسال پیام به شرکت کننده ها 📯', '', 'send_message_event-' . $item->id ),
                                    ]
                                ] )
                                );

                                break;

                        }

                    }

                }
                else
                {

                    $user->SendMessageHtml( '🔶 هیچ رویداد فعالی وجود ندارد.' );

                }

                break;


            #تعویض پنل

            case '/user':
            case 'یوزر':

                if ( $user->isAdmin() )
                {

                    $message = '✅ تغییر پنل کاربری با موفقیت انجام شد.' . "\n";
                    $message .= '⚜️ شما هم اکنون در پنل <u>کاربر عادی</u> هستید.';
                    $user->setKeyboard( $this->userMenu() )->SendMessageHtml( $message )->togglePanel();

                }
                else throw new ExceptionWarning( 'شما ادمین نیستید' );

                break;

            default:

                switch ( $user->status )
                {

                    case 'get_message_for_send_event':

                        $user->setStatus( '' );
                        $message    = '⚠️ عملیات ارسال پیام همگانی برای شرکت کنندگان شروع شد ... توجه این عملیات ممکن است زمان بر باشد.' . "\n \n";
                        $message    .= '⭕️ عددی که در زیر مشاهده میکنید عدد تعداد پیام ارسالی موفق به کاربران است.';
                        $message    = $telegram->sendMessage(
                            $user->getUserId(), $message, $telegram->buildInlineKeyBoard( [
                            [
                                $telegram->buildInlineKeyboardButton( '0', '', 'counter' )
                            ]
                        ] )
                        );
                        $message_id = $message[ 'result' ][ 'message_id' ];
                        $i          = 0;

                        $events = ParticipantEvents::where( 'event_id', $user->data[ 'id' ] )->get();

                        foreach ( $events as $item )
                        {
                            $result = $telegram->copyMessage( $item->user_id, $user->getUserId(), $this->message_id );
                            if ( isset( $result[ 'result' ][ 'message_id' ] ) ) $i ++;
                            if ( $i % 10 == 0 )
                            {
                                $telegram->editKeyboard(
                                    $user->getUserId(), $message_id, $telegram->buildInlineKeyBoard( [
                                    [
                                        $telegram->buildInlineKeyboardButton( $i, '', 'counter' )
                                    ]
                                ] )
                                );
                            }
                        }

                        $message = '✅ عملیات ارسال پیام همگانی برای شرکت کنندگان با موفقیت به پایان رسید.';
                        $telegram->editMessageText(
                            $user->getUserId(), $message_id, $message, $telegram->buildInlineKeyBoard( [
                            [
                                $telegram->buildInlineKeyboardButton( $i, '', 'counter' )
                            ]
                        ] )
                        );


                        break;

                    default:
                        goto START_BOT;

                }

                break;

        }

    }

    /**
     * @return void
     * @throws \Exception
     */
    private function subUser() : void
    {

        $user     = new User( $this->chat_id );
        $telegram = tel();

        if ( isset( $this->text_data[ 0 ] ) && $this->text_data[ 0 ] == '/start' )
        {

            $explode = explode( '-', $this->text_data[ 1 ] );
            switch ( $explode[ 0 ] )
            {

                case 'ticket':
                case 't':
                case 'support':

                    if ( isset( $explode[ 1 ] ) && isset( Ticket::LIST_TICKETS[ $explode[ 1 ] - 1 ] ) )
                    {

                        $message = 'تیکت با موضوع ' . "<b><u>" . Ticket::LIST_TICKETS[ $explode[ 1 ] - 1 ] . "</u></b>" . ' فعال شد .' . "\n \n";
                        $message .= '💬 لطفا پیام خود را ارسال کنید و تا پاسخ پشتیبان صبور باشید :';
                        $user->setStatus( 'get_message_ticket' )->setData( [
                            'id' => $explode[ 1 ] - 1
                        ] )->SendMessageHtml( $message );
                        die();

                    }
                    else
                    {
                        $this->text_data = [ '/start' ];
                        $this->private();
                    }

                    break;

                case 'event':

                    if ( ! $user->isOnChannel() )
                    {

                        $user->setKeyboard(
                            $telegram->buildInlineKeyBoard( [
                                [
                                    $telegram->buildInlineKeyboardButton( text: '⬅️ ورود به کانال ➡️', url: 'https://t.me/montazeri_computer' )
                                ]
                            ] )
                        )->SendMessageHtml( str_replace( [ '%name%', '%id%' ], [ $this->first_name, $user->getUserId() ], Message::get( 'join-channel' ) ) );
                        die();

                    }

                    $item = ParticipantEvents::where( 'data', 'LIKE', '%"link":"' . $explode[ 1 ] . '"%' );

                    if ( $item->exists() )
                    {

                        $participant_event = $item->first();

                        if ( $participant_event->user_id != $user->getUserId() )
                        {

                            $event = $participant_event->event;

                            if ( $event->data[ 'count_team' ] > ( $participant_event->data[ 'count' ] ?? 1 ) )
                            {

                                if ( isset( $participant_event->data[ 'status' ] ) && $participant_event->data[ 'status' ] == 'invite_team' )
                                {

                                    if ( is_numeric( $user->student_id ) && $event->free_login_user == 1 )
                                    {

                                        if ( ! $user->isRegisteredEvent( $event ) )
                                        {

                                            $message = '📋 قوانین و شرایط ثبت نام در این دوره از مسابقات:' . "\n \n" . $event->description . "\n \n" . "⚜️ آیا شرایط برگزاری مسابقات را قبول دارید؟";
                                            $user->setKeyboard(
                                                $telegram->buildInlineKeyBoard( [
                                                    [
                                                        $telegram->buildInlineKeyboardButton( text: 'پذیرش شرایط و ثبت نام ✅', callback_data: 'register_user_team_event-' . $participant_event->id ),
                                                        $telegram->buildInlineKeyboardButton( text: '❌ انصراف', callback_data: 'close_plan' )
                                                    ]
                                                ] )
                                            );

                                        }
                                        else
                                        {

                                            $message = '❌ شما قبلا در این مسابقه ثبت نام کرده اید✋';

                                        }

                                    }
                                    elseif ( $event->free_login_user == 2 )
                                    {

                                        if ( is_numeric( $user->student_id ) )
                                        {

                                            if ( ! $user->isRegisteredEvent( $event ) )
                                            {

                                                $message = '📋 قوانین و شرایط ثبت نام در این دوره از مسابقات:' . "\n \n" . $event->description . "\n \n" . "⚜️ آیا شرایط برگزاری مسابقات را قبول دارید؟";
                                                $user->setKeyboard(
                                                    $telegram->buildInlineKeyBoard( [
                                                        [
                                                            $telegram->buildInlineKeyboardButton( text: 'پذیرش شرایط و ثبت نام ✅', callback_data: 'register_user_team_event-' . $participant_event->id ),
                                                            $telegram->buildInlineKeyboardButton( text: '❌ انصراف', callback_data: 'close_plan' )
                                                        ]
                                                    ] )
                                                );

                                            }
                                            else
                                            {

                                                $message = '❌ شما قبلا در این مسابقه ثبت نام کرده اید✋';

                                            }

                                        }
                                        else
                                        {

                                            $message = '✋ برای پیوستن به این رویداد باید اول وارد حساب کاربری خود بشوید.';

                                        }

                                    }
                                    elseif ( ! $user->isRegisteredEvent( $event ) )
                                    {

                                        $message = '📋 قوانین و شرایط ثبت نام در این دوره از مسابقات:' . "\n \n" . $event->description . "\n \n" . "⚜️ آیا شرایط برگزاری مسابقات را قبول دارید؟";
                                        $user->setKeyboard(
                                            $telegram->buildInlineKeyBoard( [
                                                [
                                                    $telegram->buildInlineKeyboardButton( text: 'پذیرش شرایط و ثبت نام ✅', callback_data: 'register_user_team_event-' . $participant_event->id ),
                                                    $telegram->buildInlineKeyboardButton( text: '❌ انصراف', callback_data: 'close_plan' )
                                                ]
                                            ] )
                                        );

                                    }
                                    else
                                    {

                                        $message = '❌ شما قبلا در این مسابقه ثبت نام کرده اید✋';

                                    }

                                }
                                else
                                {

                                    $message = '⚜️ تیم در حال بررسی است🤝';

                                }

                            }
                            else
                            {

                                $message = '😓 متاسفم ظرفیت تیم تکمیل شده است ✋';

                            }

                        }
                        else
                        {

                            $message = '😁 نمیشه که خودت هم تیمی خودت بشی 😉';

                        }

                        $user->SendMessageHtml( $message );
                        die();

                    }

                    break;

                case 'form':


                    $item = Form::where( 'hash', $explode[ 1 ] );

                    if ( $item->exists() )
                    {

                        $form = $item->first();

                        if ( ! UsersForm::where( 'user_id', $user->getUserId() )->where( 'form_id', $form->id )->exists() )
                        {

                            if ( $form->participate > 0 || is_null( $form->participate ) )
                            {

                                if ( $form->status == Form::STATUS_PUBLIC )
                                {

                                    $telegram->copyMessage( $user->getUserId(), $form->user_id, $form->message_id );
                                    $message = '⚜️ فرم ثبت نام تا دقایقی دیگر در اختیار شما قرار میگیرد ✔️';
                                    $user->SendMessageHtml( $message );
                                    sleep( 2 );
                                    $message = $form->questions[ 0 ][ 'name' ];
                                    if ( $form->questions[ 0 ][ 'validate' ] == 'phone' )
                                        $user->setKeyboard(
                                            $telegram->buildKeyBoard( [
                                                [
                                                    $telegram->buildKeyboardButton( '📞 اشتراک گذاری شماره همراهم 📱', true )
                                                ]
                                            ],
                                                'برای اشتراک گذاری شماره همراهتان میتوانید از منو زیر استفاده کنید'
                                            ),
                                        );
                                    else
                                        $user->setKeyboard( $telegram->buildKeyBoardHide() );

                                    $user->SendMessageHtml( $message )->setStatus( 'get_info_form' )->setStep( 0 )->setData( [ 'id' => $form->id ] );

                                }
                                else
                                {
                                    $message = '❌ امکان استفاده از این فرم وجود ندارد ☹️';
                                    $user->SendMessageHtml( $message );
                                }

                            }
                            else
                            {
                                $message = '😮‍💨 متاسفم اما ظرفیت این فرم تکمیل شده است❌';
                                $user->SendMessageHtml( $message );
                            }

                        }
                        else
                        {
                            $message = '⚠️ شما قبلا این فرم را پر کرده اید.';
                            $user->SendMessageHtml( $message );
                        }

                        die();

                    }

                    break;

                case 'file':

                    if ( ! $user->isOnChannel() )
                    {

                        $user->setKeyboard(
                            $telegram->buildInlineKeyBoard( [
                                [
                                    $telegram->buildInlineKeyboardButton( text: '⬅️ ورود به کانال ➡️', url: 'https://t.me/montazeri_computer' )
                                ]
                            ] )
                        )->SendMessageHtml( str_replace( [ '%name%', '%id%' ], [ $this->first_name, $user->getUserId() ], Message::get( 'join-channel' ) ) );
                        die();

                    }

                    $file = Files::where( 'hash', $explode[ 1 ] );
                    if ( $file->exists() )
                    {

                        foreach ( $file->get() as $item )
                        {

                            $telegram->copyMessage( $user->getUserId(), $item->user_id, $item->message_id, [

                                'protect_content' => true

                            ] );

                        }

                        die();

                    }

                    break;

                case 'food':

                    $this->text_data = [ '/food' ];
                    $this->text      = '/food';
                    $this->private();
                    return;

                    break;

                case 'coupon':

                    switch ( $explode[ 1 ] )
                    {

                        case 1:


                            $message = '🎁 کد تخفیف <u>نقشه سفر مشتری در کسب و کار های دیجیتال</u> 🎉' . "\n\n";
                            $message .= '👇🏻👇🏻👇🏻👇🏻👇🏻👇🏻👇🏻👇🏻' . "\n";
                            $message .= '🔖 <code>MontazeriComputer</code>' . "\n \n";
                            $message .= '📌 برای کپی کردن کد تخفیف میتوانید بر روی آن کلیک کنید 🙌🏻';
                            $user->SendMessageHtml( $message );

                            if ( ! in_array( $user->getUserId(), cache( 'coupon-1402-08-27' ) ?? [] ) )
                            {
                                cache()->forever(
                                    'coupon-1402-08-27', array_merge( [
                                        $user->getUserId()
                                    ], ( cache( 'coupon-1402-08-27' ) ?? [] ) )
                                );
                            }


                            break;

                        case 2:


                            if ( $user->isOnChannel() )
                            {

                                $message = '🎁 کد تخفیف <u>از استعدادیابی تا ورود به بازارکار</u> 🎉' . "\n\n";
                                $message .= '👇🏻👇🏻👇🏻👇🏻👇🏻👇🏻👇🏻👇🏻' . "\n";
                                $message .= '🔖 <code>MCA</code>' . "\n \n";
                                $message .= '📌 برای کپی کردن کد تخفیف میتوانید بر روی آن کلیک کنید 🙌🏻';
                                $telegram->sendMessage( $user->getUserId(), $message, null, 'html', [
                                    'protect_content' => true
                                ] );

                            }
                            else
                            {

                                $user->setKeyboard(
                                    $telegram->buildInlineKeyBoard( [
                                        [
                                            $telegram->buildInlineKeyboardButton( text: '⬅️ ورود به کانال ➡️', url: 'https://t.me/montazeri_computer' )
                                        ]
                                    ] )
                                )->SendMessageHtml( str_replace( [ '%name%', '%id%' ], [ $this->first_name, $user->getUserId() ], Message::get( 'join-channel' ) ) );

                            }


                            break;

                        case 3:


                            if ( $user->isOnChannel() )
                            {

                                $message = '🎁 کد تخفیف 45 درصدی <u>آکادمی گیم‌دوجو و هلدینگ دانش‌بنیان طراحان سفید</u> 🎉' . "\n\n";
                                $message .= '👇🏻👇🏻👇🏻👇🏻👇🏻👇🏻👇🏻👇🏻' . "\n";
                                $message .= '🔖 <code>MONTAZERI</code>' . "\n \n";
                                $message .= '📌 برای کپی کردن کد تخفیف میتوانید بر روی آن کلیک کنید 🙌🏻';
                                $telegram->sendMessage( $user->getUserId(), $message, null, 'html', [
                                    'protect_content' => true
                                ] );

                            }
                            else
                            {

                                $user->setKeyboard(
                                    $telegram->buildInlineKeyBoard( [
                                        [
                                            $telegram->buildInlineKeyboardButton( text: '⬅️ ورود به کانال ➡️', url: 'https://t.me/montazeri_computer' )
                                        ]
                                    ] )
                                )->SendMessageHtml( str_replace( [ '%name%', '%id%' ], [ $this->first_name, $user->getUserId() ], Message::get( 'join-channel' ) ) );

                            }


                            break;

                    }
                    die();

                    break;

                case 'post':

                    $post = Post::where( 'id', $explode[ 1 ] );
                    if ( $post->exists() )
                    {

                        $post = $post->first();

                        $telegram->copyMessage( $user->getUserId(), $post->chat_id, $post->message_id, [

                            'protect_content' => true

                        ] );


                        die();

                    }

                    break;

                default:

                    $event = Event::where( 'hash', $this->text_data[ 1 ] );

                    if ( $event->exists() )
                    {

                        $item = $event->first();

                        switch ( $item->type )
                        {

                            case 1:

                                $count   = ParticipantEvents::where( 'event_id', $item->id )->count();
                                $message = '🎓 دوره:  ' . Str::b( $item->title ) . "\n \n";
                                $message .= '🧑‍🏫 مدرس: ' . Str::u( $item->teacher_name ) . "\n \n";
                                $message .= '💰 هزینه دوره: ' . Str::b( number_format( $item->amount ) . ' تومان' ) . ' ' . ( in_array( $item->free_login_user, [ 1, 2 ] ) ? Str::bu( '( برای دانشجویان دانشکده منتظری رایگان )' ) : '' ) . "\n \n";
                                $message .= '👤 ظرفیت دوره: ' . Str::u( $item->count . ' نفر' ) . "\n";
                                $message .= '👨🏻‍🎓 تعداد دانشجو: ' . Str::u( $count . ' نفر' ) . "\n \n";

                                if ( $item->available_at > date( 'Y-m-d' ) )
                                {
                                    $message .= '🗓 زمان باقی مانده جهت ثبت نام: ' . "\n";
                                    $message .= Str::b( Str::date( $item->available_at ) ) . "\n \n";
                                }
                                else
                                {
                                    $message .= '❌ مهلت ثبت نام تمام شده است.' . "\n \n";
                                }

                                $message .= '👇جهت دریافت اطلاعات بیشتر در مورد دوره بر روی دکمه زیر کلیک کنید👇';

                                $telegram->sendPhoto(
                                    $user->getUserId(), $item->file_id, $message, $telegram->buildInlineKeyBoard( [
                                    [
                                        $telegram->buildInlineKeyboardButton( '📥 شرکت در دوره 📥', '', 'event_participate-' . $item->id )
                                    ]
                                ] )
                                );

                                break;

                            case 2:

                                $count   = match ( $item->data[ 'type_join' ] )
                                {
                                    default => ParticipantEvents::where( 'event_id', $item->id )->count(),
                                    2       => ParticipantEvents::where( 'event_id', $item->id )->where( 'payment_type', '!=', 'JoinTeam' )->count(),
                                };
                                $message = '🏆 مسابقه : ' . Str::b( $item->title ) . "\n \n";
                                if ( ! empty( $item->teacher_name ) ) $message .= '🤝 حامیان مسابقات : ' . Str::bu( $item->teacher_name ) . "\n";
                                $message .= '💰 هزینه شرکت در مسابقه : ' . Str::b( number_format( $item->amount ) . ' تومان' ) . ' ' . ( in_array( $item->free_login_user, [ 1, 2 ] ) ? Str::bu( '( برای دانشجویان دانشکده منتظری رایگان )' ) : '' ) . "\n";
                                $message .= '⭐️ تعداد شرکت کنندگان : ' . Str::u( $count . ' ' . ( $item->data[ 'type_join' ] == 2 ? 'تیم' : 'نفر' ) ) . "\n";
                                $message .= '👤 ظرفیت مسابقه : ' . Str::u( $item->count . ' ' . ( $item->data[ 'type_join' ] == 2 ? 'تیم' : 'نفر' ) ) . "\n";
                                $message .= '🗓 زمان باقی مانده جهت ثبت نام:' . "\n";

                                if ( date( 'Y-m-d', strtotime( $item->available_at ) ) > date( 'Y-m-d' ) )
                                {
                                    $message .= '🗓 زمان باقی مانده جهت ثبت نام: ' . "\n";
                                    $message .= Str::b( Str::date( $item->available_at ) ) . "\n \n";
                                }
                                else
                                {
                                    $message .= '❌ مهلت ثبت نام تمام شده است.' . "\n \n";
                                }

                                $message .= '👇جهت دریافت اطلاعات بیشتر در مورد دوره بر روی دکمه زیر کلیک کنید👇' . "\n";
                                $message .= '📣 @montazeri_computer';
                                $telegram->sendPhoto( $user->getUserId(), $item->file_id, $message, $telegram->buildInlineKeyBoard( [ [ $telegram->buildInlineKeyboardButton( '🏆 شرکت در مسابقه 🎮', '', 'event_participate-' . $item->id ) ] ] ) );

                                break;

                        }

                        die();

                    }

                    break;

            }

        }

        $this->text_data = [ '/start' ];
        $this->private();

    }

    /**
     * @return void
     * @throws ExceptionWarning
     */
    public function group()
    {
        $this->supergroup();
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function supergroup()
    {

        $telegram = telegram();

        if ( $this->chat_id == env( 'GP_SUPPORT' ) )
        {

            if ( isset( $this->reply_id ) && isset( $this->message->reply_to_message->text ) )
            {

                if ( preg_match( '/\d+/u', $this->message->reply_to_message->text, $from_id ) )
                {

                    if ( isset( $from_id[ 0 ] ) )
                    {

                        $user_id = $from_id[ 0 ];

                        if ( User::isUserExists( $user_id ) )
                        {

                            $user_select = new User( $user_id );

                            switch ( explode( ' ', $this->text )[ 0 ] )
                            {


                                case 'بن':
                                case '/ban':


                                    $list_bans = json_decode( Storage::get( 'public/bans.json' ) );

                                    if ( ! in_array( $user_select->getUserId(), $list_bans ) )
                                    {

                                        $list_bans[] = $user_select->getUserId();
                                        Storage::put( 'public/bans.json', json_encode( $list_bans ) );
                                        $message = '⛔️ شما از ربات مسدود شدید.';
                                        $user_select->SendMessageHtml( $message )->reset();
                                        $message = '✅ کاربر با موفقیت مسدود شد.';

                                    }
                                    else
                                    {

                                        $message = '✋ کاربر قبلا در لیست مسدود شده ها می باشد.';

                                    }

                                    $telegram->sendMessage( $this->chat_id, $message );

                                    break;

                                case 'ان بن':
                                case '/unban':

                                    $list_bans = json_decode( Storage::get( 'public/bans.json' ) );

                                    if ( in_array( $user_select->getUserId(), $list_bans ) )
                                    {

                                        unset( $list_bans[ array_search( $user_select->getUserId(), $list_bans ) ] );
                                        Storage::put( 'public/bans.json', json_encode( $list_bans ) );
                                        $message = '✅ مسدودیت شما از ربات برداشته شد.';
                                        $user_select->SendMessageHtml( $message )->reset();
                                        $message = '✅ کاربر با موفقیت رفع مسدود شد.';

                                    }
                                    else
                                    {

                                        $message = '✋ کاربر مسدود نمی باشد.';

                                    }

                                    $telegram->sendMessage( $this->chat_id, $message );

                                    break;

                                case 'اطلاعات':
                                case '/info':
                                case 'ا':

                                    $message = '👤 پروفایل کاربر:' . "\n \n";
                                    $message .= '💳 آیدی عددی:  ' . $user_select->code() . "\n";
                                    if ( ! empty( $user_select->name ) ) $message .= '👤 نام و نام خانوادگی:  ' . "<b><u>" . $user_select->name . "</u></b>" . "\n \n";

                                    $message .= '➖➖➖➖➖➖➖' . "\n";

                                    if ( ! empty( $user_select->student_id ) )
                                    {

                                        $link    = $user_select->user();
                                        $message .= '🔗 حساب متصل:' . "\n";
                                        $message .= '👤 نام و نام خانوادگی: ' . "<b><u>" . $link->uni->first_name . ' ' . $link->uni->last_name . "</u></b>" . "\n";
                                        $message .= '🎗 شماره دانشجویی: ' . "<b><code>" . $link->uni->students_id . "</code></b>" . "\n";
                                        $message .= '🏢 دانشگاه: ' . "<b>" . $link->uni->uni->name . "</b>" . "\n";
                                        $message .= '🎓 رشته تحصیلی: ' . "<b>" . $link->uni->section->name . "</b>" . "\n";

                                    }
                                    else
                                    {

                                        $message .= '❌ حساب شما متصل نمی باشد.' . "\n";

                                    }
                                    $telegram->sendMessage( $this->chat_id, $message );

                                    break;

                                case '/add':

                                    if ( ! Club::on()->where( 'user_id', $user_select->getUserId() )->exists() )
                                    {

                                        if ( $this->from_id == env('ADMIN_LOG') )
                                        {

                                            Club::on()->create( [
                                                'user_id' => $user_select->getUserId()
                                            ] );
                                            $message = '🌐 پیام سیستمی:' . "\n \n";
                                            $message .= '⚜️ عضویت شما در باشگاه اعضای انجمن علمی کامپیوتر منتظری تایید ✅' . "\n";
                                            $message .= '🎉 ورود شما را به انجمن تبریک می گوییم 🤝' . "\n \n";
                                            $message .= '⚠️ لطفا از بلاک و حذف ربات خودکاری کنید زیرا در حال حاضر تنها راه ارتباطی با ما شما از طریق ربات امکان پذیر است.';
                                            $user_select->SendMessageHtml( $message );
                                            $message = '⚜️ عضویت کاربر با موفقیت انجام شد ✅';
                                            $telegram->sendMessage( $this->chat_id, $message, null, TelegramBot::HtmlMode, [
                                                'reply_to_message_id' => $this->message->reply_to_message->message_id
                                            ] );

                                        }
                                        else
                                        {
                                            throw new ExceptionWarning( 'شما مجاز به استفاده از این دستور نیستید.' );
                                        }

                                    }
                                    else
                                    {
                                        throw new ExceptionWarning( 'این کاربر قبلا در باشگاه انجمن عضو است.' );
                                    }


                                    break;

                                default:

                                    $telegram->copyMessage( $user_select->getUserId(), $this->chat_id, $this->message_id, [
                                        'reply_markup' => $telegram->buildInlineKeyBoard( [
                                            [
                                                $telegram->buildInlineKeyboardButton( '📌 پاسخ به پیام', '', 'reply_to_answer-' . $this->message_id )
                                            ]
                                        ] )
                                    ] );
                                    /*$message = '<a href="' . $this->message_id . '.ir"> </a>' . 'جهت پاسخ میتوانید روی همین پیام ریپلای کنید و پاسخ خود را ارسال کنید.';
                                    $telegram->sendMessage( $user_select->getUserId(), $message, null, TelegramBot::HtmlMode, [
                                        'reply_to_message_id'         =>  $msg[ 'result' ][ 'message_id' ] ,
                                    ] );*/

                                    break;


                            }

                        }
                        else
                        {
                            throw new ExceptionWarning( 'کاربر مورد نظر یافت نشد.' );
                        }

                    }

                }

            }

        }

        if ( $this->chat_id == '' )
        {

            $is_link = \Illuminate\Support\Str::of(
                \Illuminate\Support\Str::of( $this->text ?? $this->caption ?? $this->forward->text ?? $this->forward->caption )
                                       ->trim()
                                       ->lower()
                                       ->matchAll( '/\b[A-Za-z]+\b/' )
                                       ->implode( '' )
            )->contains( [
                'https://t.me',
                'https://telegram.me',
                'telegram.me',
                't.me',
                'tme',
                'telegramme',
                '/+',
                'porn',
            ] );

            if ( $is_link )
            {


                $group = telegram()->getChatMember( $this->chat_id, $this->from_id );

                if ( $group[ 'ok' ] && $group[ 'result' ][ 'status' ] == 'member' )
                {

                    telegram()->deleteMessage( $this->chat_id, $this->message_id );

                }


            }

        }

        if ( $this->chat_id == '' )
        {

            if ( isset( $this->message->new_chat_participant ) )
            {

                $user = new User( $this->message->new_chat_participant->id );

                $query = UsersForm::where( 'user_id', $user->getUserId() )->where( function ( Builder $query ) {
                    $query->where( 'form_id', '' )->orWhere( 'form_id', '' );
                } );

                if ( ! $query->exists() )
                    $telegram->banChatMember( '', $user->getUserId() );
                else
                {

                    $telegram->endpoint( 'promoteChatMember', [

                        'chat_id'                => '',
                        'user_id'                => $user->getUserId(),
                        'can_manage_chat'        => false,
                        'is_anonymous'           => false,
                        'can_delete_messages'    => false,
                        'can_manage_video_chats' => false,
                        'can_restrict_members'   => false,
                        'can_promote_members'    => false,
                        'can_change_info'        => false,
                        'can_invite_users'       => false,
                        'can_post_stories'       => false,
                        'can_edit_stories'       => false,
                        'can_delete_stories'     => false,
                        'can_post_messages'      => false,
                        'can_edit_messages'      => false,
                        'can_pin_messages'       => true,
                        'can_manage_topics'      => false,

                    ] );
                    $telegram->endpoint( 'setChatAdministratorCustomTitle', [

                        'chat_id'      => '',
                        'user_id'      => $user->getUserId(),
                        'custom_title' => $query->first()->form_id == '' ? 'PES' : 'FIFA',

                    ] );


                }

            }

        }

        if ( \Illuminate\Support\Str::is( '*/install*', $this->text ) )
        {

            $groups = cache()->get( 'reserve_groups' ) ?? [];

            if ( ! in_array( $this->chat_id, $groups ) )
            {

                cache()->forever(
                    'reserve_groups', array_merge(
                        $groups,
                        [ $this->chat_id ]
                    )
                );

                $telegram->sendMessage( $this->chat_id, 'Install Successfully ✅' );

            }

        }

    }

}
