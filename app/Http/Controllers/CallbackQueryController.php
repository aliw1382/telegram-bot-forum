<?php

namespace App\Http\Controllers;

use App\Exceptions\ExceptionError;
use App\Exceptions\ExceptionWarning;
use App\helper\Payment;
use App\helper\Str;
use App\helper\TelegramData;
use App\helper\User;
use App\Models\Event;
use App\Models\Form;
use App\Models\Menu;
use App\Models\Message;
use App\Models\Option;
use App\Models\ParticipantEvents;
use App\Models\Plan;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\Ticket;
use App\Models\University;
use App\Models\UsersForm;
use App\Models\Vote;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use function PHPUnit\Framework\at;

class CallbackQueryController extends TelegramData
{
    public function __construct( object $update )
    {
        parent::__construct( $update );
    }

    /**
     * @throws \Exception
     */
    public function private()
    {

        $user     = new User( $this->chatid );
        $telegram = tel();

        if ( in_array( $user->getUserId(), json_decode( Storage::get( 'public/bans.json' ) ) ) )
        {
            $user->SendMessageHtml( '✋ شما مسدود هستید ⛔️' );
            $telegram->deleteMessage( $this->chatid, $this->messageid );
            exit();
        }
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

        $data = $this->query_data;
        switch ( $data[ 0 ] )
        {

            case 'reply_to_answer':

                $telegram->copyMessage( $user->getUserId(), $user->getUserId(), $this->messageid );
                $telegram->deleteMessage( $user->getUserId(), $this->messageid );
                $message = '💬 لطفا پیام خود را ارسال کنید و تا پاسخ پشتیبان صبور باشید :';
                $user->SendMessageHtml( $message )->setStatus( 'get_message_to_reply' )->setData( [
                    'id' => $data[ 1 ]
                ] );

                break;

            case 'new_ticket':

                if ( isset( Ticket::LIST_TICKETS[ $data[ 1 ] ] ) )
                {

                    $message = 'تیکت با موضوع ' . "<b><u>" . Ticket::LIST_TICKETS[ $data[ 1 ] ] . "</u></b>" . ' فعال شد .' . "\n \n";
                    $message .= '💬 لطفا پیام خود را ارسال کنید و تا پاسخ پشتیبان صبور باشید :';
                    $user->setStatus( 'get_message_ticket' )->setData( [
                        'id' => $data[ 1 ]
                    ] );
                    $telegram->editMessageText( $user->getUserId(), $this->messageid, $message );

                }
                else
                {
                    $telegram->answerCallbackQuery( $this->dataid, '😓 آخ متاسفم برای این موضوع هنوز فعال نشده است لطفا ار موضوعات دیگر استفاده کنید 🤕', true );
                }

                break;

            case 'close_plan':

                $telegram->editMessageText( $user->getUserId(), $this->messageid, '✅ پنل بسته شد.' );

                break;

            case 'cancel':

                $telegram->editMessageText( $user->getUserId(), $this->messageid, '❌ عملیات لغو شد.' );

                break;

            case 'delete_plan':

                $telegram->deleteMessage( $user->getUserId(), $this->messageid );
                $user->SendMessageHtml( '❌ عملیات لغو شد.' );

                break;

            case 'exit_connected_account':

                if ( isset( $user->student_id ) )
                {

                    $message = '⚠️ آیا مطمعن هستید که میخواهید از حساب " ' . $user->uni->students_id . ' " خارج شوید؟';
                    $telegram->editMessageText(
                        $user->getUserId(), $this->messageid, $message, $telegram->buildInlineKeyBoard( [
                        [
                            $telegram->buildInlineKeyboardButton( '✅ تایید', '', 'exit_connected_account_2' ),
                            $telegram->buildInlineKeyboardButton( '⛔️ انصراف', '', 'cancel' ),
                        ]
                    ] )
                    );
                }
                else
                {
                    $telegram->deleteMessage( $user->getUserId(), $this->messageid );
                }

                break;

            case 'exit_connected_account_2':

                if ( isset( $user->student_id ) )
                {

                    Student::on()->where( 'id', $user->student_id )->update( [
                        'login_at' => null
                    ] );
                    $message = '✅ عملیات خروج از حساب با موفقیت انجام شد.';
                    $user->update( [
                        'student_id' => null
                    ] )->reset();
                    $telegram->editMessageText( $user->getUserId(), $this->messageid, $message );

                }
                else
                {
                    $telegram->deleteMessage( $user->getUserId(), $this->messageid );
                }

                break;

            case 'btn':

                $btn     = Message::find( $data[ 1 ] );
                $message = '📝 شما در حال ویرایش " ' . $btn->title . ' " هستید.' . "\n \n";
                $message .= '🖋 متن کنونی ⬇️' . "\n";
                $message .= $btn->contact;
                $telegram->editMessageText(
                    $user->getUserId(), $this->messageid, $message, $telegram->buildInlineKeyBoard( [
                    [
                        $telegram->buildInlineKeyboardButton( '⛔️ انصراف', '', 'cancel' )
                    ]
                ] )
                );
                $user->setStatus( 'edit_message' )->setData( $data[ 1 ] );


                break;

            // ----------------------------------
            # Menu

            case 'menu':

                $menu     = Menu::on()->find( $data[ 1 ] == 0 ? 1 : $data[ 1 ] );
                $keyboard = $this->menu( $data[ 1 ] );

                $message = 'شما در حال ویرایش منو  ' . "<b><code>$menu->name</code></b>" . '  ربات هستید.';

                if ( count( $keyboard ) > 0 )
                {
                    $telegram->editMessageText( $user->getUserId(), $this->messageid, $message, $telegram->buildInlineKeyBoard( $keyboard ) );
                }
                else
                {
                    $telegram->editMessageText(
                        $user->getUserId(), $this->messageid, $message, $telegram->buildInlineKeyBoard( [
                        [
                            $telegram->buildInlineKeyboardButton( '➕ اضافه کردن منو', '', 'new_sub_menu-' . $data[ 1 ] )
                        ]
                    ] )
                    );
                }

                break;

            case 'new_sub_menu':

                $message = '🔸 عنوان ( اسم ) دکمه را ارسال کنید.';
                $telegram->editMessageText( $user->getUserId(), $this->messageid, $message );
                $user->setStatus( 'get_name_new_sub_menu' )->setStep( $data[ 1 ] );

                break;

            case 'new_row_menu':

                $message = '🔸 عنوان ( اسم ) دکمه را ارسال کنید.';
                $telegram->editMessageText( $user->getUserId(), $this->messageid, $message );
                $user->setStatus( 'get_name_new_row_menu' )->setStep( $data[ 1 ] );

                break;

            case 'new_menu':

                $message = '🔸 عنوان ( اسم ) دکمه را ارسال کنید.' . "\n \n";
                $telegram->editMessageText( $user->getUserId(), $this->messageid, $message );
                $user->setStatus( 'get_name_new_menu' )->setStep( $data[ 1 ] )->setData(
                    array_merge( ( $user->data ?? [] ), [

                        'parent' => $data[ 1 ],
                        'row'    => $data[ 2 ],
                        'col'    => $data[ 3 ],

                    ] )
                );

                break;

            case 'delete_menu':

                $menu    = Menu::on()->find( $data[ 1 ] );
                $message = '❌ آیا از حذف منو " ' . "<b><code>$menu->name</code></b>" . ' " اطمینان دارید؟؟ با حذف این منو تمامی منو های زیر منو آن نیز حذف می شود✋';
                $telegram->editMessageText(
                    $user->getUserId(), $this->messageid, $message, $telegram->buildInlineKeyBoard( [
                    [
                        $telegram->buildInlineKeyboardButton( '✅ مطمعنم، حذف شود', '', 'delete_menu_2-' . $data[ 1 ] ),
                        $telegram->buildInlineKeyboardButton( '❌ انصراف', '', 'cancel' ),
                    ]
                ] )
                );

                break;

            case 'delete_menu_2':

                $menu    = Menu::on()->find( $data[ 1 ] );
                $message = '✅ منو " ' . "<b><code>$menu->name</code></b>" . ' " با موفقیت حذف شد.';
                $telegram->editMessageText( $user->getUserId(), $this->messageid, $message );
                $menu->delete();
                Menu::on()->where( 'parent', $data[ 1 ] )->delete();

                break;

            case 'select_type_new_menu':

                switch ( $data[ 1 ] )
                {

                    case 'text':
                    case 'message':

                        $message = '🔻 محتوای دکمه " ' . "<b>{$user->data['name']}</b>" . ' " را ارسال کنید.';
                        $telegram->editMessageText( $user->getUserId(), $this->messageid, $message );
                        $user->setStatus( $data[ 2 ] );

                        break;

                }

                break;

            // --------------------------

            # Register New Student

            case 'add_new_user_2':

                $keyboard = [];
                $message  = '👇 لطفا انتخاب کنید دانشجویی که میخواهید آن را اضافه کنید رشته تحصلی او چیست👇';
                foreach ( Section::all() as $item )
                {
                    $keyboard[][] = $telegram->buildInlineKeyboardButton( '🎓 دانشجوی رشته ' . $item->name, '', 'add_new_user-' . $data[ 1 ] . '-' . $item->id );
                }

                $telegram->editMessageText( $user->getUserId(), $this->messageid, $message, $telegram->buildInlineKeyBoard( $keyboard ) );

                break;

            case 'add_new_user':

                if ( isset( $user->data[ 'stu' ] ) && isset( $user->data[ 'pass' ] ) && isset( $user->data[ 'name' ] ) && isset( $user->data[ 'family' ] ) )
                {

                    Student::on()->create( [

                        'students_id'   => $user->data[ 'stu' ],
                        'first_name'    => $user->data[ 'name' ],
                        'last_name'     => $user->data[ 'family' ],
                        'national_code' => Hash::make( $user->data[ 'pass' ] ),
                        'section_id'    => $data[ 2 ],
                        'uni_id'        => $data[ 1 ]

                    ] );
                    $message = '✅ کاربر با موفقیت ساخته شد.';
                    $telegram->editMessageText( $user->getUserId(), $this->messageid, $message );

                }
                else
                {
                    $telegram->deleteMessage( $this->chatid, $this->messageid );
                }

                break;

            case 'delete_student':

                $student = Student::find( $data[ 1 ] );

                $message = '📇 شما در حال حذف دانشجوی " ' . $student->first_name . ' ' . $student->last_name . ' " هستید. آیا از این کار مطمئن هستید؟';
                $telegram->editMessageText(
                    $user->getUserId(), $this->messageid, $message, $telegram->buildInlineKeyBoard( [
                    [
                        $telegram->buildInlineKeyboardButton( '✅ تایید', '', 'delete_student_2-' . $student->id ),
                        $telegram->buildInlineKeyboardButton( '❌ انصراف', '', 'cancel' ),
                    ]
                ] )
                );

                break;

            case 'delete_student_2':

                Student::where( 'id', $data[ 1 ] )->delete();
                $message = 'دانشجو با موفقیت حذف گردید✅';
                $telegram->editMessageText( $user->getUserId(), $this->messageid, $message );

                break;

            case 'edit_student':

                $telegram->editKeyboard(
                    $user->getUserId(), $this->messageid, $telegram->buildInlineKeyBoard( [
                    [
                        $telegram->buildInlineKeyboardButton( text: '📝 ویرایش نام', callback_data: 'edit_student_2-first_name-' . $data[ 1 ] ),
                        $telegram->buildInlineKeyboardButton( text: '📝 ویرایش نام خانوادگی', callback_data: 'edit_student_2-last_name-' . $data[ 1 ] ),
                    ],
                    [
                        $telegram->buildInlineKeyboardButton( text: '📝 ویرایش کدملی', callback_data: 'edit_student_2-national_code-' . $data[ 1 ] ),
                        $telegram->buildInlineKeyboardButton( text: '📝 ویرایش دانشگاه', callback_data: 'edit_student_2-uni_id-' . $data[ 1 ] ),
                    ],
                    [
                        $telegram->buildInlineKeyboardButton( text: '📝 ویرایش رشته', callback_data: 'edit_student_2-section_id-' . $data[ 1 ] ),
                    ],
                    [
                        $telegram->buildInlineKeyboardButton( text: '↩️ بازگشت', callback_data: 'student_info-' . $data[ 1 ] ),
                    ]
                ] )
                );

                break;

            case 'edit_student_2':


                switch ( $data[ 1 ] )
                {


                    case 'uni_id':

                        $keyboard = [];
                        $message  = '👇 لطفا انتخاب کنید دانشجوی جز کدام دانشگاه می باشد👇';
                        foreach ( University::all() as $item )
                        {
                            $keyboard[][] = $telegram->buildInlineKeyboardButton( '🏢 ' . $item->name, '', 'set_info_student-' . $item->id . '-' . $data[ 2 ] . '-' . $data[ 1 ] );
                        }
                        $telegram->editMessageText( $user->getUserId(), $this->messageid, $message, $telegram->buildInlineKeyBoard( $keyboard ) );

                        break;

                    case 'section_id':

                        $keyboard = [];
                        $message  = '👇 لطفا انتخاب کنید دانشجوی مشغول به تحصیل چه رشته ای می باشد👇';
                        foreach ( Section::all() as $item )
                        {
                            $keyboard[][] = $telegram->buildInlineKeyboardButton( '🏢 ' . $item->name, '', 'set_info_student-' . $item->id . '-' . $data[ 2 ] . '-' . $data[ 1 ] );
                        }
                        $telegram->editMessageText( $user->getUserId(), $this->messageid, $message, $telegram->buildInlineKeyBoard( $keyboard ) );


                        break;

                    default:

                        $message = '⚜️ لطفا مقدار جدید را وارد نمایید:' . "\n \n" . Str::bu( '⚠️ توجه هیچ تست صحیح بودن اطلاعات در این مرحله وجود ندارد لذا لطفا اطلاعات صحیح در این قسمت وارد کنید.' );
                        $user->SendMessageHtml( $message )->setStatus( 'edit_student' )->setData( [
                            'id'   => $data[ 2 ],
                            'type' => $data[ 1 ]
                        ] );

                        break;

                }

                break;

            case 'set_info_student':

                Student::where( 'id', $data[ 2 ] )->update( [
                    $data[ 3 ] => $data[ 1 ]
                ] );
                $message = 'عملیات ویرایش با موفقیت انجام شد✅';
                $telegram->editMessageText( $user->getUserId(), $this->messageid, $message );

                break;

            case 'add_student_to_event':

                $student = Student::find( $data[ 1 ] );

                if ( isset( $student->user->user_id ) )
                {

                    $keyboard = [];
                    foreach ( Event::where( 'type', 1 )->get() as $item )
                    {
                        $keyboard[][] = $telegram->buildInlineKeyboardButton( text: '🎗 ' . $item->title, callback_data: 'add_student_to_event_2-' . $student->user->user_id . '-' . $item->id );
                    }

                    $keyboard[][] = $telegram->buildInlineKeyboardButton( text: '↩️ بازگشت', callback_data: 'student_info-' . $data[ 1 ] );

                    $message = '🔰 لطفا انتخاب کنید کاربر را میخواهید در کدام رویداد ثبت نام کنید:';
                    $telegram->editMessageText( $user->getUserId(), $this->messageid, $message, $telegram->buildInlineKeyBoard( $keyboard ) );

                }
                else
                {

                    $message = '🔥 برای اضافه کردن دانشجو نیاز است اول شماره دانشجویی به یک حساب تلگرامی متصل شده باشد.';
                    $telegram->editMessageText( $user->getUserId(), $this->messageid, $message );

                }

                break;

            case 'add_student_to_event_2':

                $user_item = new User( $data[ 1 ] );
                $event     = Event::find( $data[ 2 ] );

                if ( ! $user_item->isRegisteredEvent( $event ) && $user_item->registerEvent( $event, 'AdminRegister', [ 'admin_id' => $user->getUserId() ] ) )
                {

                    $message = '✔️ شما توسط ادمین در رویداد " " ثبت نام شدید ✅';
                    $user_item->SendMessageHtml( $message );
                    $message = '✅ ثبت نام با موفقیت انجام شد✔️';

                }
                else
                {
                    $message = '⁉️ این کاربر قبلا در این رویداد شرکت کرده است!';
                }
                $telegram->editMessageText( $this->chatid, $this->messageid, $message );

                break;

            case 'student_info':

                $telegram->editKeyboard(
                    $user->getUserId(), $this->messageid, $telegram->buildInlineKeyBoard( [
                    [
                        $telegram->buildInlineKeyboardButton( text: '📯 اضافه کردن به یک رویداد', callback_data: 'add_student_to_event-' . $data[ 1 ] )
                    ],
                    [
                        $telegram->buildInlineKeyboardButton( text: '🗑 حذف دانشجو', callback_data: 'delete_student-' . $data[ 1 ] ),
                        $telegram->buildInlineKeyboardButton( text: '✏️ ویرایش دانشجو', callback_data: 'edit_student-' . $data[ 1 ] ),
                    ],
                    [
                        $telegram->buildInlineKeyboardButton( text: '❌ بستن پنل', callback_data: 'cancel' ),
                    ]
                ] )
                );

                break;

            // --------------------------------

            # new Event

            // -- Admin --

            case 'new_event':

                $message = '⚜️ لطفا عنوان رویداد رو وارد کنید:';
                $telegram->editMessageText( $user->getUserId(), $this->messageid, $message );
                $user->setStatus( 'new_event_' . $data[ 1 ] )->setStep( 1 );

                break;

            case 'add_event':

                $event = Event::create(
                    array_merge( $user->data, [
                        'user_id'         => $user->getUserId(),
                        'free_login_user' => $data[ 1 ],
                        'hash'            => uniqid()
                    ] )
                );

                if ( isset( $event->id ) )
                {

                    $message = 'رویداد با موفقیت اضافه شد✅';
                    $telegram->editMessageText( $user->getUserId(), $this->messageid, $message );
                    $user->clearData();

                }
                else
                {
                    throw new ExceptionError( 'متاسفانه خطایی در اضافه کردن رویداد به دیتابیس رخ داد!' );
                }


                break;

            case 'edit_event':

                $telegram->editKeyboard(
                    $user->getUserId(), $this->messageid, $telegram->buildInlineKeyBoard( [
                    [
                        $telegram->buildInlineKeyboardButton( '✏️ ویرایش عنوان', '', 'edit_event_2-title-' . $data[ 1 ] ),
                        $telegram->buildInlineKeyboardButton( '✏️ ویرایش لینک', '', 'edit_event_2-hash-' . $data[ 1 ] ),
                    ],
                    [
                        $telegram->buildInlineKeyboardButton( '✏️ ویرایش سر فصل ها', '', 'edit_event_2-topics-' . $data[ 1 ] ),
                        $telegram->buildInlineKeyboardButton( '✏️ ویرایش نام مدرس', '', 'edit_event_2-teacher_name-' . $data[ 1 ] ),
                    ],
                    [
                        $telegram->buildInlineKeyboardButton( '✏️ ویرایش قیمت', '', 'edit_event_2-amount-' . $data[ 1 ] ),
                        $telegram->buildInlineKeyboardButton( '✏️ ویرایش توضیحات', '', 'edit_event_2-description-' . $data[ 1 ] ),
                    ],
                    [
                        $telegram->buildInlineKeyboardButton( '✏️ ویرایش ظرفیت', '', 'edit_event_2-count-' . $data[ 1 ] ),
                        $telegram->buildInlineKeyboardButton( '✏️ ویرایش پوستر', '', 'edit_event_2-file_id-' . $data[ 1 ] ),
                    ],
                    [
                        $telegram->buildInlineKeyboardButton( '✏️ ویرایش نوع ثبت نام', '', 'edit_event_2-type-' . $data[ 1 ] ),
                        $telegram->buildInlineKeyboardButton( '✏️ ویرایش تاریخ ثبت نام', '', 'edit_event_2-available_at-' . $data[ 1 ] ),
                    ],
                ] )
                );

                break;

            case 'edit_event_2':


                switch ( $data[ 1 ] )
                {

                    case 'type':

                        $message = '🔻 روش پرداخت جدید را انتخاب کنید:';
                        $user->setKeyboard(
                            $telegram->buildInlineKeyBoard( [
                                [
                                    $telegram->buildInlineKeyboardButton( '✔️ پرداخت و رایگان برای افراد وارد شده', '', 'change_type_event-1-' . $data[ 2 ] ),
                                ],
                                [
                                    $telegram->buildInlineKeyboardButton( '✔️ فقط برای افراد وارد شده', '', 'change_type_event-2-' . $data[ 2 ] ),
                                ],
                                [
                                    $telegram->buildInlineKeyboardButton( '✔️ پرداخت تنها برای افراد وارد شده', '', 'change_type_event-3-' . $data[ 2 ] ),
                                ],
                                [
                                    $telegram->buildInlineKeyboardButton( '✔️ تنها از طریق پرداخت', '', 'change_type_event-0-' . $data[ 2 ] ),
                                ]
                            ] )
                        )->SendMessageHtml( $message );

                        break;

                    default:

                        $message = '⚜️ لطفا مقدار جدید را وارد نمایید:' . "\n \n" . Str::bu( '⚠️ توجه هیچ تست صحیح بودن اطلاعات در این مرحله وجود ندارد لذا لطفا اطلاعات صحیح در این قسمت وارد کنید.' );
                        $user->SendMessageHtml( $message )->setStatus( 'edit_event' )->setData( [
                            'event' => $data[ 2 ],
                            'type'  => $data[ 1 ]
                        ] );

                        break;

                }


                break;

            case 'delete_event':

                $event = Event::find( $data[ 1 ] );

                $message = '🔔 شما در حال حذف رویداد " ' . Str::b( $event->title ) . ' " هستید. آیا از این کار اطمینان دارید؟';
                $telegram->editMessageCaption( $user->getUserId(), $this->messageid, $message );
                $telegram->editKeyboard(
                    $user->getUserId(), $this->messageid, $telegram->buildInlineKeyBoard( [
                    [
                        $telegram->buildInlineKeyboardButton( '✅ تایید', '', 'delete_event_2-' . $event->id ),
                        $telegram->buildInlineKeyboardButton( '❌ انصراف', '', 'delete_plan' ),
                    ],
                ] )
                );

                break;

            case 'delete_event_2':

                Event::where( 'id', $data[ 1 ] )->delete();
                $telegram->deleteMessage( $user->getUserId(), $this->messageid );
                $message = 'با موفقیت رویداد حذف شد✅';
                $user->SendMessageHtml( $message );

                break;

            case 'change_type_event':

                $event                  = Event::find( $data[ 2 ] );
                $event->free_login_user = $data[ 1 ];
                $event->save();

                $message = 'عملیات ویرایش با موفقیت انجام شد✅';
                $telegram->editMessageText( $this->chatid, $this->messageid, $message );

                break;

            case 'send_message_event':

                $message = '📫 متن پیام خود را برای فرستادن ارسال کنید:';
                $user->setKeyboard( KEY_BACK_TO_MENU )->SendMessageHtml( $message )->setStatus( 'get_message_for_send_event' )->setData( [ 'id' => $data[ 1 ] ] );

                break;

            case 'list_participate_event':

                $participant_event = ParticipantEvents::where( 'event_id', $data[ 1 ] )->get();

                $message = '📜 لیست ثبت نام کنندگان رویداد : ' . Str::code( Event::find( $data[ 1 ] )->title ) . "\n \n";
                $msg     = '';
                foreach ( $participant_event as $item )
                {

                    $user_item = new User( $item->user_id );
                    $message   .= '👤 کاربر: ' . Str::code( $user_item->getUserId() ) . "\n";
                    if ( ! empty( $user_item->name ) ) $message .= '👤 نام و نام خانوادگی:  ' . Str::bu( $user_item->name ) . "\n";

                    if ( is_numeric( $item->student_id ) )
                    {

                        $link    = $item->stu();
                        $message .= '🔗 حساب متصل:' . "\n";
                        $message .= '👤 نام و نام خانوادگی: ' . "<b><u>" . $link->uni->first_name . ' ' . $link->uni->last_name . "</u></b>" . "\n";
                        $message .= '🎗 شماره دانشجویی: ' . "<b><code>" . $link->uni->students_id . "</code></b>" . "\n";
                        $message .= '🏢 دانشگاه: ' . "<b>" . $link->uni->uni->name . "</b>" . "\n";
                        $message .= '🎓 رشته تحصیلی: ' . "<b>" . $link->uni->section->name . "</b>" . "\n";

                    }

                    $message .= '🏷 شیوه ثبت نام:  ';
                    switch ( $item->payment_type )
                    {

                        case 'LoginAccount':

                            $message .= Str::b( '👤 حساب کاربری وارد شده' ) . "\n";

                            break;

                        case 'payment':

                            $message .= Str::b( '💳 درگاه پرداخت' ) . "\n";
                            $message .= '📍 شماره تراکنش: ' . Str::codeB( ( $item->data[ 'ref_id' ] ?? 'یافت نشد' ) ) . "\n";
                            $message .= '📬 توکن تراکنش: ' . Str::codeB( ( $item->data[ 'authority' ] ?? 'یافت نشد' ) ) . "\n";

                            break;

                        case 'JoinTeam':

                            $message .= Str::b( '💠 پیوستن به تیم' ) . "\n";

                            break;

                        case 'AdminRegister':

                            $message .= '🛂 توسط ادمین ( ' . Str::code( $item->data[ 'admin_id' ] ?? 'Not Found' ) . ' )';

                            break;

                        default:

                            $message .= Str::b( '⚠️ خطا در پردازش نحوه پرداخت' ) . "\n";

                            break;

                    }

                    if ( mb_strlen( $message, 'UTF-8' ) + mb_strlen( $msg, 'UTF-8' ) > 4090 )
                    {
                        $user->SendMessageHtml( $msg );
                        $msg = '';
                    }
                    $msg     .= $message . "\n \n";
                    $message = '';

                }

                if ( mb_strlen( $msg, 'UTF-8' ) > 0 )
                {
                    $user->SendMessageHtml( $msg );
                }

                break;

            case 'roll_call_event':

                $participant_event = ParticipantEvents::where( 'event_id', $data[ 1 ] )->get();

                $keyboard = [];
                $i        = 0;

                foreach ( $participant_event as $item )
                {

                    $keyboard[ $i ][] = $telegram->buildInlineKeyboardButton( text: '👤 ' . $item->user_id, callback_data: 'id_user' );
                    if ( is_numeric( $item->student_id ) ) $keyboard[ $i ][] = $telegram->buildInlineKeyboardButton( text: $item->student->students_id, callback_data: 'stu_id_user' );
                    $keyboard[ $i ++ ][] = $telegram->buildInlineKeyboardButton( text: '❌', callback_data: 'roll_call_user_event-' . $item->id );

                }

                $keyboard[][] = $telegram->buildInlineKeyboardButton( text: '✔️ ثبت لیست ✔️', callback_data: 'set_roll_call_event-' . $data[ 1 ] );
                $keyboard[][] = $telegram->buildInlineKeyboardButton( text: '❌ بستن منو ❌', callback_data: 'delete_plan' );

                $telegram->editKeyboard( $this->chatid, $this->messageid, $telegram->buildInlineKeyBoard( $keyboard ) );

                break;

            case 'roll_call_user_event':

                $keyboard = collect( (array) $this->callback_query->message->reply_markup->inline_keyboard );

                $keyboard->each( function ( $arr ) use ( $data, $user ) {

                    if ( $arr[ count( $arr ) - 1 ]->callback_data == 'roll_call_user_event-' . $data[ 1 ] )
                    {

                        $arr[ count( $arr ) - 1 ]->text = $arr[ count( $arr ) - 1 ]->text == '❌' ? '✅' : '❌';

                    }

                    return $arr;

                } );

                $telegram->editKeyboard( $this->chatid, $this->messageid, $telegram->buildInlineKeyBoard( $keyboard->toArray() ) );


                break;

            case 'set_roll_call_event':

                $keyboard = collect( (array) $this->callback_query->message->reply_markup->inline_keyboard );

                $event = Event::find( $data[ 1 ] );

                $message = '🔔 حضور و غیاب دروه ' . Str::bu( $event->title ) . ' در تاریخ : ( ' . Str::code( jdate()->format( 'Y/m/d' ) ) . "\n \n";

                $keyboard->each( function ( $arr ) use ( $message ) {


                    if ( str_contains( $arr[ count( $arr ) - 1 ]->callback_data, 'roll_call_user_event' ) )
                    {

                        $data = explode( '-', $arr[ count( $arr ) - 1 ]->callback_data );

                        $participant_event = ParticipantEvents::find( $data[ 1 ] );

                        $message .= '👤 کاربر ' . Str::code( $participant_event->user_id ) . ' در کلاس حضور ';

                        $text = '🌐 پیام سیستمی:' . "\n \n";
                        if ( $arr[ count( $arr ) - 1 ]->text == '❌' )
                        {

                            $text    .= '⭕️ شما در دوره ' . Str::b( $participant_event->event->title ) . ' غیبت خوردید ❌';
                            $message .= 'نداشتند❌';

                        }
                        else
                        {

                            $text    .= '⭕️ شما در دوره ' . Str::b( $participant_event->event->title ) . ' شرکت کردید✅';
                            $message .= 'داشتند ✅';

                        }

                        $message .= "\n";

                        telegram()->sendMessage( $participant_event->user_id, $text );

                    }

                    return $arr;

                } );

                $telegram->answerCallbackQuery( $this->dataid, '💠 حضور و غیاب با موفقیت انجام شد ✅' );

                $telegram->sendMessage( env( 'CHANNEL_LOG' ), $message );

                $telegram->deleteMessage( $this->chatid, $this->messageid );

                break;

            case 'remove_user_event':

                $participant_event = ParticipantEvents::where( 'event_id', $data[ 1 ] )->get();

                $keyboard = [];
                $i        = 0;

                foreach ( $participant_event as $item )
                {

                    $keyboard[ $i ][] = $telegram->buildInlineKeyboardButton( text: '👤 ' . $item->user_id, callback_data: 'id_user' );
                    if ( is_numeric( $item->student_id ) ) $keyboard[ $i ][] = $telegram->buildInlineKeyboardButton( text: $item->student->students_id, callback_data: 'stu_id_user' );
                    $keyboard[ $i ++ ][] = $telegram->buildInlineKeyboardButton( text: '🗑', callback_data: 'remove_user_from_event-' . $item->id );

                }

                $keyboard[][] = $telegram->buildInlineKeyboardButton( text: '❌ بستن منو ❌', callback_data: 'delete_plan' );

                $telegram->editKeyboard( $this->chatid, $this->messageid, $telegram->buildInlineKeyBoard( $keyboard ) );


                break;

            case 'remove_user_from_event':

                $participant_event = ParticipantEvents::find( $data[ 1 ] );
                $user
                    ->setKeyboard(
                        $telegram->buildInlineKeyBoard( [
                            [
                                $telegram->buildInlineKeyboardButton( text: '✅ تایید', callback_data: 'remove_user_from_event_2-' . $data[ 1 ] ),
                                $telegram->buildInlineKeyboardButton( text: '❌ انصراف', callback_data: 'cancel' ),
                            ]
                        ] )
                    )
                    ->SendMessageHtml( '✋ شما در حال حذف " ' . Str::code( $participant_event->user_id ) . ' " از رویداد " ' . $participant_event->event->title . ' " هستید! ایا از انجام این کار اطمینان دارید؟' )
                ;

                break;

            case 'remove_user_from_event_2':

                $participant_event = ParticipantEvents::find( $data[ 1 ] );

                $message = '✅ ثبت نام کاربر " ' . Str::code( $participant_event->user_id ) . ' " در رویداد "' . $participant_event->event->title . '" لغو شد✔️';
                $telegram->editMessageText( $this->chatid, $this->messageid, $message );

                $message = '🔔 ثبت نام شما در رویداد " ' . $participant_event->event->title . ' " لغو شد❌';
                $telegram->sendMessage( $participant_event->user_id, $message );

                $participant_event->delete();

                break;

            // -- User --

            case 'event_participate':

                if ( $user->spam( 10 ) )
                {

                    $telegram->answerCallbackQuery(
                        $this->dataid,
                        '⚠️ شرایط شرکت در مسابقه رو با دقت بخوانید!',
                        true
                    );

                    $event = Event::find( $data[ 1 ] );

                    if ( $event->available_at > date( 'Y-m-d 00:00:00' ) )
                    {

                        if ( $event->count > 0 || is_null( $event->count ) )
                        {

                            switch ( $event->type )
                            {

                                case 1:

                                    if ( is_numeric( $user->student_id ) && $event->free_login_user == 1 )
                                    {

                                        if ( ! $user->isRegisteredEvent( $event ) )
                                        {

                                            $message = '📜 نکاتی در مورد دوره:' . "\n \n" . $event->description . "\n \n" . "⚜️ آیا شرایط برگزاری دوره را قبول دارید؟";
                                            $user->setKeyboard(
                                                $telegram->buildInlineKeyBoard( [
                                                    [
                                                        $telegram->buildInlineKeyboardButton( text: 'پذیرش شرایط و ثبت نام ✅', callback_data: 'register_user_event-' . $event->id ),
                                                        $telegram->buildInlineKeyboardButton( text: '❌ انصراف', callback_data: 'close_plan' )
                                                    ]
                                                ] )
                                            );

                                        }
                                        else
                                        {

                                            $message = '❌ شما قبلا در این دوره ثبت نام کرده اید✋';

                                        }

                                        $telegram->deleteMessage( $this->chatid, $this->messageid );
                                        $user->SendMessageHtml( $message );

                                    }
                                    elseif ( $event->free_login_user == 2 )
                                    {

                                        if ( is_numeric( $user->student_id ) )
                                        {

                                            if ( ! $user->isRegisteredEvent( $event ) )
                                            {

                                                $message = '📜 نکاتی در مورد دوره:' . "\n \n" . $event->description . "\n \n" . "⚜️ آیا شرایط برگزاری دوره را قبول دارید؟";
                                                $user->setKeyboard(
                                                    $telegram->buildInlineKeyBoard( [
                                                        [
                                                            $telegram->buildInlineKeyboardButton( text: 'پذیرش شرایط و ثبت نام ✅', callback_data: 'register_user_event-' . $event->id ),
                                                            $telegram->buildInlineKeyboardButton( text: '❌ انصراف', callback_data: 'close_plan' )
                                                        ]
                                                    ] )
                                                );

                                            }
                                            else
                                            {

                                                $message = '❌ شما قبلا در این دوره ثبت نام کرده اید✋';

                                            }

                                        }
                                        else
                                        {

                                            $message = '❌ متاسفم این دوره تنها برای افرادی که وارد حساب کاربری شان در ربات شده باشند امکان پذیر است❗️';

                                        }

                                        $telegram->deleteMessage( $this->chatid, $this->messageid );
                                        $user->SendMessageHtml( $message );

                                    }
                                    elseif ( ! $user->isRegisteredEvent( $event ) )
                                    {


                                        if ( ! empty( $event->description ) )
                                        {

                                            $message = '📜 نکاتی در مورد دوره:' . "\n \n";
                                            $message .= $event->description;
                                            $user->SendMessageHtml( $message );

                                        }

                                        if ( $event->free_login_user != 3 || is_numeric( $user->student_id ) )
                                        {

                                            $msg = $telegram->sendMessage( $user->getUserId(), '🔄 در حال صدور صورتحساب ' );

                                            $payment = new Payment( $event->amount, $user->getUserId() );
                                            $payment->config()->detail( 'detail', [ 'type' => 'event', 'event' => [ 'id' => $event->id ] ] );
                                            $payment->config()->detail( 'description', $event->title . ' - ' . $user->getUserId() );
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
                                            $message .= '📦بابت: رویداد ' . Str::bu( $event->title ) . "\n \n";
                                            $message .= '⚠️ لطفا توجه داشته باشید هنگام پرداخت از استفاده هرگونه ' . Str::bu( 'فیلترشکن خودداری' ) . ' کنید.' . "\n \n";
                                            $message .= '⚠️ توجه درگاه پرداخت از سمت زرین پال تایید و قابل اعتماد است ✅' . "\n";
                                            $message .= Str::b( $payment_url ) . "\n";
                                            $message .= '📍 لینک یک بار مصرف و 2 دقیقه زمان دارد استفاده از آن است.' . "\n\n";
                                            $message .= Str::b( '👇 برای پرداخت بر روی دکمه زیر کلیک کنید👇' );
                                            $telegram->editMessageText(
                                                $user->getUserId(), $msg[ 'result' ][ 'message_id' ], $message, $telegram->buildInlineKeyBoard( [
                                                [
                                                    $telegram->buildInlineKeyboardButton( '💳 پرداخت', $payment_url )
                                                ]
                                            ] )
                                            );
                                            $telegram->deleteMessage( $this->chatid, $this->messageid );

                                        }
                                        else
                                        {

                                            $telegram->deleteMessage( $this->chatid, $this->messageid );
                                            $user->SendMessageHtml( '😓 با عرض پوزش این دوره تنها برای افرادی می باشد که وارد حساب شان شده باشند✋' );

                                        }

                                    }
                                    else
                                    {

                                        $user->SendMessageHtml( '❌ شما قبلا در این دوره ثبت نام کرده اید✋2' );
                                        $telegram->deleteMessage( $this->chatid, $this->messageid );

                                    }

                                    break;

                                case 2:

                                    if ( is_numeric( $user->student_id ) && $event->free_login_user == 1 )
                                    {

                                        if ( ! $user->isRegisteredEvent( $event ) )
                                        {

                                            $message = '📋 قوانین و شرایط ثبت نام در این دوره از مسابقات:' . "\n \n" . $event->description . "\n \n" . "⚜️ آیا شرایط برگزاری مسابقات را قبول دارید؟";
                                            $user->setKeyboard(
                                                $telegram->buildInlineKeyBoard( [
                                                    [
                                                        $telegram->buildInlineKeyboardButton( text: 'پذیرش شرایط و ثبت نام ✅', callback_data: 'register_user_event-' . $event->id ),
                                                        $telegram->buildInlineKeyboardButton( text: '❌ انصراف', callback_data: 'close_plan' )
                                                    ]
                                                ] )
                                            );

                                        }
                                        else
                                        {

                                            $message = '❌ شما قبلا در این مسابقه ثبت نام کرده اید✋';

                                        }

                                        $telegram->deleteMessage( $this->chatid, $this->messageid );
                                        $user->SendMessageHtml( $message );

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
                                                            $telegram->buildInlineKeyboardButton( text: 'پذیرش شرایط و ثبت نام ✅', callback_data: 'register_user_event-' . $event->id ),
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

                                            $message = '❌ متاسفم این مسابقه تنها برای افرادی که وارد حساب کاربری شان در ربات شده باشند امکان پذیر است❗️';

                                        }

                                        $telegram->deleteMessage( $this->chatid, $this->messageid );
                                        $user->SendMessageHtml( $message );

                                    }
                                    elseif ( ! $user->isRegisteredEvent( $event ) )
                                    {

                                        if ( ! empty( $event->description ) )
                                        {

                                            $message = '📋 قوانین و شرایط ثبت نام در این دوره از مسابقات:' . "\n \n";
                                            $message .= $event->description;
                                            $user->SendMessageHtml( $message );

                                        }

                                        if ( $event->free_login_user != 3 || is_numeric( $user->student_id ) )
                                        {

                                            $msg = $telegram->sendMessage( $user->getUserId(), '🔄 در حال صدور صورتحساب ' );

                                            $payment = new Payment( $event->amount, $user->getUserId() );
                                            $payment->config()->detail( 'detail', [ 'type' => 'race', 'event' => [ 'id' => $event->id ] ] );
                                            $payment->config()->detail( 'description', $event->title . ' - ' . $user->getUserId() );
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
                                            $message .= '📦بابت: شرکت در مسابقه ' . Str::bu( $event->title ) . "\n \n";
                                            $message .= '⚠️ لطفا توجه داشته باشید هنگام پرداخت از استفاده هرگونه ' . Str::bu( 'فیلترشکن خودداری' ) . ' کنید.' . "\n \n";
                                            $message .= '⚠️ توجه درگاه پرداخت از سمت زرین پال تایید و قابل اعتماد است ✅' . "\n";
                                            $message .= Str::b( $payment_url ) . "\n";
                                            $message .= '📍 لینک یک بار مصرف و 2 دقیقه اعتبار دارد.' . "\n\n";
                                            $message .= Str::b( '👇 برای پرداخت بر روی دکمه زیر کلیک کنید👇' );
                                            $telegram->editMessageText(
                                                $user->getUserId(), $msg[ 'result' ][ 'message_id' ], $message, $telegram->buildInlineKeyBoard( [
                                                [
                                                    $telegram->buildInlineKeyboardButton( '💳 پرداخت', $payment_url )
                                                ]
                                            ] )
                                            );
                                            $telegram->deleteMessage( $this->chatid, $this->messageid );

                                        }
                                        else
                                        {

                                            $telegram->deleteMessage( $this->chatid, $this->messageid );
                                            $user->SendMessageHtml( '😓 با عرض پوزش این مسابقه تنها برای افرادی می باشد که وارد حساب شان شده باشند✋' );

                                        }

                                    }
                                    else
                                    {

                                        $user->SendMessageHtml( '❌ شما قبلا در این مسابقه ثبت نام کرده اید✋' );
                                        $telegram->deleteMessage( $this->chatid, $this->messageid );

                                    }

                                    break;

                            }

                        }
                        else
                        {

                            $telegram->deleteMessage( $this->chatid, $this->messageid );
                            $message = '❌ متاسفم ظرفیت ثبت نام برای این رویداد تکمیل شده است❗️';
                            $user->SendMessageHtml( $message );

                        }

                    }
                    else
                    {

                        $telegram->deleteMessage( $this->chatid, $this->messageid );
                        $message = '❌ متاسفم زمان ثبت نام برای این رویداد تمام شده است❗️';
                        $user->SendMessageHtml( $message );

                    }

                }
                else
                {
                    $telegram->answerCallbackQuery( $this->dataid, '⚠️ هر 10 ثانیه یک بار میتوانید از این بخش استفاده کنید.' );
                    $telegram->deleteMessage( $this->chatid, $this->messageid );
                }

                break;

            case 'register_user_event':

                $event = Event::find( $data[ 1 ] );

                if ( $event->available_at > date( 'Y-m-d 00:00:00' ) )
                {

                    if ( ! $user->isRegisteredEvent( $event ) )
                    {

                        if ( $event->count > 0 || is_null( $event->count ) )
                        {

                            $telegram->editMessageText( $this->chatid, $this->messageid, $this->callback_query->message->text );

                            switch ( $event->type )
                            {

                                case 1:

                                    $message = '✅ شما با موفقیت در دوره " ' . Str::b( $event->title ) . ' " ما ثبت نام شدید🤝' . "\n \n";
                                    $message .= '🔔 لطفا از حذف ربات خودداری کنید اطلاعات مربوط به ورود به دوره و نحوه برگزاری دوره به شما از طریق ربات اطلاع رسانی می گردد.';
                                    $user->SendMessageHtml( $message )->registerEvent( $event, 'LoginAccount' );

                                    break;


                                case 2:

                                    $message = '✔️ نام شما در مسابقه " ' . Str::b( $event->title ) . ' " با موفقیت اضافه شد ✅' . "\n \n";
                                    $message .= 'برای ورود و ' . Str::bu( 'تکمیل ثبت نام' ) . ' دستور /panel را ارسال کنید 🤝' . "\n \n";
                                    $message .= Str::bu( '⚠️ همچنین از مسدود یا حذف ربات خودداری کنید زیرا تمام اطلاع رسانی های مربوط به مسابقات از طریق ربات به شما اطلاع رسانی خواهد شد🙏' );
                                    $user->SendMessageHtml( $message )->registerEvent( $event, 'LoginAccount', [ 'role' => 'owner' ] );

                                    break;

                            }

                        }
                        else
                        {

                            $telegram->deleteMessage( $this->chatid, $this->messageid );
                            $message = '❌ متاسفم ظرفیت ثبت نام برای این مسابقه تکمیل شده است❗️';
                            $user->SendMessageHtml( $message );

                        }


                    }
                    else
                    {

                        $message = '❌ شما قبلا در این رویداد ثبت نام کرده اید✋';
                        $telegram->editMessageText( $this->chatid, $this->messageid, $message );

                    }

                }
                else
                {

                    $telegram->deleteMessage( $this->chatid, $this->messageid );
                    $message = '❌ متاسفم زمان ثبت نام برای این مسابقه تمام شده است❗️';
                    $user->SendMessageHtml( $message );

                }

                break;

            // -- Panel Team --

            case 'panel_event':

                $event = ParticipantEvents::find( $data[ 1 ] );

                $message = '🎗 ' . $event->event->title . "\n \n";
                $message .= '📮نام تیم شما: ' . ( $event->data[ 'team' ] ?? 'مشخص نشده' ) . "\n";
                $message .= '👤 تعداد اعضای تیم: ' . ( $event->data[ 'count' ] ?? 1 ) . ' نفر' . "\n";
                $message .= '💠 وضعیت تیم شما: ' . match ( ( $event->data[ 'status' ] ?? '' ) )
                    {
                        'invite_team' => '📯 در انتظار پیوستن هم تیمی',
                        'ready'       => '✔️ انتظار برای درخواست ثبت نام',
                        'process'     => '🔄 در حال بررسی',
                        'accept'      => '✅ تایید شده است',
                        default       => '🔄 در انتظار ثبت نام سر گروه'
                    };

                $telegram->editMessageText(
                    $user->getUserId(), $this->messageid, $message, $telegram->buildInlineKeyBoard( [
                    [
                        $telegram->buildInlineKeyboardButton( text: '✏️ ویرایش نام تیم', callback_data: 'edit_name_team-' . $event->id ),
                        $telegram->buildInlineKeyboardButton( text: '📯 دعوت هم تیمی', callback_data: 'invite_team-' . $event->id ),
                    ],
                    [
                        $telegram->buildInlineKeyboardButton( text: '🗑 حذف هم تیمی', callback_data: 'remove_team-' . $event->id ),
                    ],
                    [
                        $telegram->buildInlineKeyboardButton( text: '✅ تکمیل ثبت نام ✅', callback_data: 'submit_team-' . $event->id ),
                    ]
                ] )
                );

                break;

            case 'edit_name_team':

                $participant_event = ParticipantEvents::find( $data[ 1 ] );
                if ( isset( $participant_event->data[ 'status' ] ) && ( $participant_event->data[ 'status' ] == 'process' || $participant_event->data[ 'status' ] == 'accept' ) )
                {
                    $telegram->deleteMessage( $this->chatid, $this->messageid );
                    die();
                }

                $message = '📮 نام تیمتان را وارد کنید:' . "\n \n";
                $message .= Str::b( '⚠️ از استفاده از کلمات نامناسب و ناپسند برای انتخاب نام تیم خودداری کنید❗️ در غیر این صورت تیم شما حذف می شود❗️' );
                $user->SendMessageHtml( $message )->setStatus( 'get_name_team' )->setData( [ 'id' => $data[ 1 ] ] );

                break;

            case 'invite_team':

                $event = ParticipantEvents::find( $data[ 1 ] );

                if ( ! isset( $event->data[ 'team' ] ) || empty( $event->data[ 'team' ] ) )
                {
                    $telegram->answerCallbackQuery( $this->dataid, '🚫 برای استفاده از این قسمت اول باید یک نام برای تیم خود انتخاب کنید.' );
                    die();
                }

                /*$link = ( 'https://t.me/' . $telegram->getMe()[ 'result' ][ 'username' ] . '?start=event-' . $hash );

                $message = '🔗 لینک دعوت هم تیمی جهت شرکت در مسابقه:' . "\n" . Str::codeB( $link );
                $telegram->editMessageText( $this->chatid, $this->messageid, $message );

                $message = '👋 سلام رفیق،' . "\n";
                $message .= '👤 ' . $event->user->name . ' درخواست هم تیمی او در مسابقه ' . Str::b( $event->event->title ) . ' که توسط انجمن علمی کامپیوتر دانشکده منتظری تشکیل شده است را دارد.' . "\n \n";
                $message .= '🎮 جهت پیوستن به تیم او بر روی لینک زیر کلیک کن و عضو تیم او بشو.' . "\n";
                $message .= $link . "\n";
                $message .= '📣 @Montazeri_Computer';
                $user->SendMessageHtml( $message );*/

                $message = '💠 برای دعوت دوست خود لطفا با کلیک بر روی دکمه زیر دوست خود را از لیست مخاطبین خود انتخاب کرده.' . "\n";
                $message .= '⚠️ توجه داشته باشید دوست شما باید قبلا در ربات ثبت نام و وارد حساب کاربری اش شده باشد🙏';
                $user->setKeyboard(
                    json_encode( [
                        'keyboard'        => [
                            [
                                [ 'text' => '🎮 دعوت دوستان 🎯', 'request_user' => [ 'request_id' => $event->id, 'user_is_bot' => false ] ]
                            ],
                            [
                                [ 'text' => '▶️ برگشت به منو اصلی' ]
                            ],
                        ],
                        'resize_keyboard' => true,
                    ] )
                )->SendMessageHtml( $message )->setStatus( 'invite_team' );

                break;

            case 'remove_team':

                $members  = ParticipantEvents::where( 'payment_type', 'JoinTeam' )
                                             ->where( 'data', 'LIKE', '%"event":' . $data[ 1 ] . '%' )
                                             ->get()
                ;
                $message  = '📍 کدام هم تیمی خود را میخواهید حذف کنید؟';
                $keyboard = [];
                foreach ( $members as $item )
                {

                    $keyboard[][] = $telegram->buildInlineKeyboardButton( text: '👈 ' . $item->user->name, callback_data: 'remove_user_team-' . $item->id . '-' . $data[ 1 ] );

                }

                $keyboard [][] = $telegram->buildInlineKeyboardButton( text: '🔙 برگشت', callback_data: 'panel_event-' . $data[ 1 ] );

                $telegram->editMessageText( $this->chatid, $this->messageid, $message, $telegram->buildInlineKeyBoard( $keyboard ) );

                break;

            case 'remove_user_team':

                $participant_event = ParticipantEvents::find( $data[ 1 ] );

                if ( ! isset( $participant_event->data[ 'status' ] ) || $participant_event->data[ 'status' ] != 'invite_team' )
                {
                    $telegram->deleteMessage( $this->chatid, $this->messageid );
                    die();
                }

                $message = '⚠️ مطمئنی میخوای هم تیمی ات " ' . Str::code( $participant_event->user->name ) . ' " را حذف کنی؟';
                $telegram->editMessageText(
                    $this->chatid, $this->messageid, $message, $telegram->buildInlineKeyBoard( [
                    [
                        $telegram->buildInlineKeyboardButton( text: '✅ تایید', callback_data: 'remove_user_team_2-' . $data[ 1 ] . '-' . $data[ 2 ] ),
                        $telegram->buildInlineKeyboardButton( text: '❌ انصراف', callback_data: 'cancel' ),
                    ]
                ] )
                );

                break;

            case 'remove_user_team_2':

                $participant_event = ParticipantEvents::find( $data[ 2 ] );

                if ( ! isset( $participant_event->data[ 'status' ] ) || $participant_event->data[ 'status' ] != 'invite_team' )
                {
                    $telegram->deleteMessage( $this->chatid, $this->messageid );
                    die();
                }

                ParticipantEvents::where( 'id', $data[ 1 ] )->delete();

                $participant_event->data = array_merge( $participant_event->data, [

                    'count'  => $participant_event->data[ 'count' ] - 1,
                    'status' => $participant_event->data[ 'count' ] - 1 != $participant_event->event->data[ 'count_team' ] ? 'invite_team' : 'ready'

                ] );

                $message = 'هم تیمی ات با موفقیت حذف شد✅';
                $telegram->editMessageText( $this->chatid, $this->messageid, $message );

                break;

            case 'register_user_team_event':

                $participant_event = ParticipantEvents::find( $data[ 1 ] );
                $event             = $participant_event->event;

                if ( ! isset( $participant_event->data[ 'status' ] ) || $participant_event->data[ 'status' ] != 'invite_team' )
                {
                    $telegram->deleteMessage( $this->chatid, $this->messageid );
                    die();
                }

                if ( ! $user->isRegisteredEvent( $event ) )
                {

                    $telegram->editMessageText( $this->chatid, $this->messageid, $this->callback_query->message->text );

                    $message = '✔️ الان دیگه هم تیمی ' . $participant_event->user->name . ' شدی🎉';
                    $user->SendMessageHtml( $message )->registerEvent( $event, 'JoinTeam', [ 'event' => $participant_event->id ] );

                    $owner_team = new User( $participant_event->user_id );
                    $owner_team->SendMessageHtml( '😃 ' . $user->name . ' به تیم شما پیوست 🎉' );


                    if ( ( $participant_event->data[ 'count' ] ?? 1 ) + 1 == $event->data[ 'count_team' ] )
                    {
                        $participant_event->data = array_merge( $participant_event->data, [

                            'status' => 'ready'

                        ] );
                    }
                    $participant_event->data = array_merge( $participant_event->data, [

                        'count' => isset( $participant_event->data[ 'count' ] ) ? $participant_event->data[ 'count' ] + 1 : 2

                    ] );
                    $participant_event->save();


                }
                else
                {

                    $message = '❌ شما قبلا در این رویداد ثبت نام کرده اید✋';
                    $telegram->editMessageText( $this->chatid, $this->messageid, $message );

                }

                break;

            case 'submit_team':

                if ( $user->spam() )
                {

                    $participant_event = ParticipantEvents::find( $data[ 1 ] );
                    $event             = $participant_event->event;

                    if ( isset( $participant_event->data[ 'team' ] ) && ! empty( $participant_event->data[ 'team' ] ) )
                    {

                        if ( $event->data[ 'count_team' ] == $participant_event->data[ 'count' ] )
                        {

                            if ( $participant_event->data[ 'status' ] == 'ready' )
                            {

                                $message = '🕹 درخواست جدید برای مسابقه " ' . Str::b( $event->title ) . ' " ثبت نام تیم:' . "\n \n";
                                $message .= '💎 نام تیم: ' . Str::code( $participant_event->data[ 'team' ] ) . "\n \n";
                                $message .= '👤 سازنده تیم: ' . "\n";
                                $message .= '🏷 نام و نام خانوادگی: ' . $user->mention( $participant_event->user->name ) . "\n";
                                $message .= '🏷 یوزر آیدی: ' . Str::codeB( $participant_event->user_id ) . "\n";
                                if ( $participant_event->student_id ) $message .= '🏷 شماره دانشجویی: ' . Str::codeB( $participant_event->student->students_id ) . "\n";
                                $message .= "\n" . '📜 اعضای تیم:' . "\n";
                                $members = ParticipantEvents::where( 'payment_type', 'JoinTeam' )
                                                            ->where( 'data', 'LIKE', '%"event":' . $data[ 1 ] . '%' )
                                                            ->get()
                                ;
                                foreach ( $members as $item )
                                {

                                    $user_team = new User( $item->user_id );

                                    if ( is_numeric( $user_team->student_id ) )
                                    {

                                        $message .= '🏷 نام و نام خانوادگی: ' . $user->mention( $user_team->name ) . "\n";
                                        $message .= '🏷 یوزر آیدی: ' . Str::codeB( $user_team->getUserId() ) . "\n";
                                        if ( $item->student_id ) $message .= '🏷 شماره دانشجویی: ' . Str::codeB( $item->student->students_id ) . "\n";
                                        $message .= "\n";

                                    }
                                    else
                                    {

                                        $telegram->editMessageText( $this->chatid, $this->messageid, '👤 هم تیمت از حسابش خارج شده!' );
                                        die();

                                    }

                                }

                                $message .= "\n" . '✅ با ثبت نام این تیم موافقت میکنید؟';
                                $telegram->sendMessage(
                                    env( 'CHANNEL_LOG' ), $message, $telegram->buildInlineKeyBoard( [
                                    [
                                        $telegram->buildInlineKeyboardButton( text: '✅ تایید', callback_data: 'accept_event-' . $participant_event->id ),
                                        $telegram->buildInlineKeyboardButton( text: '🔴 رد کردن', callback_data: 'reject_event-' . $participant_event->id ),
                                    ]
                                ] )
                                );

                                $participant_event->data = array_merge( $participant_event->data, [
                                    'status' => 'process'
                                ] );
                                $participant_event->save();

                                $message = 'درخواست ثبت نام تیم شما برای ما ارسال شد ✅';
                                $telegram->editMessageText( $this->chatid, $this->messageid, $message );

                            }
                            else
                            {

                                $telegram->answerCallbackQuery( $this->dataid, '⚠️ در خواست شما در حال پیگیری می باشد.' );

                            }


                        }
                        else
                        {

                            $telegram->answerCallbackQuery( $this->dataid, '⚠️ ظرفیت تیم شما هنوز کامل نشده است' );

                        }

                    }
                    else
                    {

                        $telegram->answerCallbackQuery( $this->dataid, '⚠️ شما هنوز نامی برای تیم خود انتخاب نکردید' );

                    }

                }
                else
                {
                    throw new ExceptionWarning( 'هر 1 دقیقه یک بار میتوانید درخواست دهید.' );
                }

                break;

            # Form

            case 'set_filter_form':

                if ( isset( $user->data[ 'name' ] ) && isset( $user->data[ 'user_id' ] ) && isset( $user->data[ 'message_id' ] ) && isset( $user->data[ 'question' ] ) )
                {

                    if ( in_array( $data[ 1 ], [ 'text', 'persian_text', 'number', 'phone', 'national_code', 'payment' ] ) )
                    {

                        $question = ( $user->data[ 'questions' ] ?? [] );
                        if ( ! empty( $user->data[ 'question' ] ) )
                        {
                            $question[] = [
                                'name'     => $user->data[ 'question' ],
                                'validate' => $data[ 1 ]
                            ];
                        }

                        if ( $data[ 1 ] == 'payment' )
                        {

                            if ( count( $question ) == 1 )
                            {

                                $message = '⚠️ ورودی اول نمیتواند از نوع پرداختی باشد.' . "\n \n" . '✔️ سوال بعدی را ارسال کنید.';
                                $telegram->editMessageText( $user->getUserId(), $this->messageid, $message );
                                $user->setStatus( 'new_form' )->setStep( 3 );
                                exit();

                            }

                            if ( ! is_numeric( $user->data[ 'question' ] ) )
                            {

                                $telegram->answerCallbackQuery( $this->dataid, '❌ مبلغ وارد شده صحیح نمی باشد.' );
                                exit();

                            }

                            $user->setData(
                                array_merge( $user->data, [

                                    'questions' => $question,
                                    'question'  => ''

                                ] )
                            );
                            $this->query_data[ 0 ] = 'submit_form';
                            $this->private();
                            exit();

                        }

                        $message = '✔️ سوال بعدی را ارسال کنید.';
                        $telegram->editMessageText(
                            $user->getUserId(), $this->messageid, $message, $telegram->buildInlineKeyBoard( [
                            [
                                $telegram->buildInlineKeyboardButton( text: '✅ پایان سوالات ✔️', callback_data: 'submit_form' )
                            ]
                        ] )
                        );
                        $user->setStatus( 'new_form' )->setStep( 3 )->setData(
                            array_merge( $user->data, [

                                'questions' => $question,
                                'question'  => ''

                            ] )
                        );

                    }
                    else
                    {
                        throw new ExceptionWarning( 'نوع داده سنجی که انتخاب کردید یافت نشد.' );
                    }

                }
                else
                {
                    $message = '🔴 داده ها ناقص شده اند، مجدد مراحل ثبت فرم را انجام دهید.';
                    $telegram->editMessageText( $user->getUserId(), $this->messageid, $message );
                }

                break;

            case 'submit_form':

                if ( isset( $user->data[ 'name' ] ) && isset( $user->data[ 'user_id' ] ) && isset( $user->data[ 'message_id' ] ) && isset( $user->data[ 'question' ] ) )
                {

                    $hash = uniqid();
                    $link = ( 'https://t.me/' . $telegram->getMe()[ 'result' ][ 'username' ] . '?start=form-' . $hash );
                    Form::on()->create( array_merge( $user->data, [ 'hash' => $hash ] ) );
                    $message = '✅ فرم با موفقیت ساخته شد ✔️' . "\n \n";
                    $message .= Str::code( $link ) . "\n \n";
                    $message .= $link;
                    $user->reset();

                }
                else
                {
                    $message = '🔴 داده ها ناقص شده اند، مجدد مراحل ثبت فرم را انجام دهید.';
                }
                $telegram->editMessageText( $user->getUserId(), $this->messageid, $message );

                break;

            case 'edit_form':

                $telegram->editKeyboard(
                    $user->getUserId(), $this->messageid, $telegram->buildInlineKeyBoard( [
                    [
                        $telegram->buildInlineKeyboardButton( '✏️ ویرایش عنوان', '', 'edit_form_2-name-' . $data[ 1 ] ),
                        $telegram->buildInlineKeyboardButton( '✏️ ویرایش لینک', '', 'edit_form_2-hash-' . $data[ 1 ] ),
                    ],
                    [
                        $telegram->buildInlineKeyboardButton( '✏️ ویرایش پوستر', '', 'edit_form_2-form-' . $data[ 1 ] ),
                        $telegram->buildInlineKeyboardButton( '✏️ ویرایش ارسال گزارش', '', 'edit_form_2-send_to-' . $data[ 1 ] ),
                    ],
                    [
                        $telegram->buildInlineKeyboardButton( '✏️ ویرایش تعداد شرکت کنندگان', '', 'edit_form_2-participate-' . $data[ 1 ] ),
                    ],
                ] )
                );

                break;

            case 'edit_form_2':

                $message = '⚜️ لطفا مقدار جدید را وارد نمایید:' . "\n \n" . Str::bu( '⚠️ توجه هیچ تست صحیح بودن اطلاعات در این مرحله وجود ندارد لذا لطفا اطلاعات صحیح در این قسمت وارد کنید.' );
                $user->SendMessageHtml( $message )->setStatus( 'edit_form' )->setData( [
                    'form' => $data[ 2 ],
                    'type' => $data[ 1 ]
                ] );

                break;

            case 'delete_form':

                $form = Form::find( $data[ 1 ] );

                $message = '🔔 شما در حال حذف فرم " ' . Str::b( $form->name ) . ' " هستید. آیا از این کار اطمینان دارید؟';
                $telegram->editMessageCaption( $user->getUserId(), $this->messageid, $message );
                $telegram->editKeyboard(
                    $user->getUserId(), $this->messageid, $telegram->buildInlineKeyBoard( [
                    [
                        $telegram->buildInlineKeyboardButton( '✅ تایید', '', 'delete_form_2-' . $form->id ),
                        $telegram->buildInlineKeyboardButton( '❌ انصراف', '', 'delete_plan' ),
                    ],
                ] )
                );

                break;

            case 'delete_form_2':

                Form::where( 'id', $data[ 1 ] )->delete();
                $telegram->deleteMessage( $user->getUserId(), $this->messageid );
                $message = 'با موفقیت رویداد حذف شد✅';
                $user->SendMessageHtml( $message );

                break;

            case 'list_participate_form':

                $form_users = UsersForm::where( 'form_id', $data[ 1 ] )->orderBy( 'id' )->get();

                if ( count( $form_users ) > 0 )
                {

                    foreach ( $form_users as $form_user )
                    {

                        $user_item = new User( $form_user->user_id );
                        $form      = $form_user->form;

                        $message = '⚜️ کاربر: ' . $user_item->mention() . "\n";
                        $message .= '📜 فرم: ' . $form->name . "\n\n";
                        foreach ( $form_user->value as $key => $item )
                        {

                            if ( $key == 'payment_id' )
                            {

                                $payment = \App\Models\Payment::find( $item );
                                $message .= Str::b( '💳 درگاه پرداخت' ) . "\n";
                                $message .= '📍 شماره تراکنش: ' . Str::codeB( ( $payment->ref_id ?? 'یافت نشد' ) ) . "\n";
                                $message .= '📬 توکن تراکنش: ' . Str::codeB( ( $payment->transaction_id ?? 'یافت نشد' ) ) . "\n";

                            }
                            else
                            {

                                $message .= $form->questions[ $key ][ 'name' ] . " : " . $item . "\n";

                            }


                            $message .= "\n";

                            if ( mb_strlen( $message ) > 4000 )
                            {
                                telegram()->sendMessage( $form->send_to, $message );
                                $message = '';
                            }

                        }

                        $user->SendMessageHtml( $message );

                    }

                }
                else
                {
                    $user->SendMessageHtml( '❌ هیچکس هنوز این فرم را پر نکرده است.' );
                }


                break;

            case 'send_message_to_participate_form':

                $message = '📫 متن پیام خود را برای فرستادن ارسال کنید:';
                $user->setKeyboard( KEY_BACK_TO_MENU )->SendMessageHtml( $message )->setStatus( 'get_message_for_send_form' )->setData( [ 'id' => $data[ 1 ] ] );

                break;

            case 'change_status':

                $form         = Form::find( $data[ 1 ] );
                $form->status = (int) ! $form->status;
                $form->save();

                $telegram->editKeyboard(
                    $user->getUserId(), $this->messageid, $telegram->buildInlineKeyBoard( [
                    [
                        $telegram->buildInlineKeyboardButton( text: '✏️ ویرایش', callback_data: 'edit_form-' . $form->id ),
                        $telegram->buildInlineKeyboardButton( text: '🗑 حذف', callback_data: 'delete_form-' . $form->id )
                    ],
                    [
                        $telegram->buildInlineKeyboardButton( text: '📋 لیست شرکت کنندگان', callback_data: 'list_participate_form-' . $form->id . '-1' ),
                    ],
                    [
                        $telegram->buildInlineKeyboardButton(
                            text: match ( $form->status )
                            {
                                Form::STATUS_PUBLIC  => '✅ فعال',
                                Form::STATUS_DELETED => '❌ مخفی شده'
                            }, callback_data: 'change_status-' . $form->id
                        ),
                    ]
                ] )
                );

                break;

            // --------------------------------------

            case 'send_vote':

                switch ( $data[ 2 ] )
                {

                    case 'form':

                        $form = Form::find( $data[ 1 ] );

                        if ( $form->exists() && isset( $form->id ) )
                        {

                            $message = '🎗 با تشکر از شما بابت شرکت در ' . Str::b( $form->name ) . ' 🙏' . "\n \n";
                            $message .= '⚜️ درصورتی که دوست داشتید میتوانید در نظرسنجی ما شرکت کنید🤝😁';

                            foreach ( $form->users as $item )
                            {

                                $user_item = new User( $item->user_id );
                                $user_item->setKeyboard(
                                    $telegram->buildInlineKeyBoard( [
                                        [
                                            $telegram->buildInlineKeyboardButton( text: '✅ شرکت در نظرسنجی', callback_data: 'participate_in_vote-form-' . $data[ 1 ] ),
                                            $telegram->buildInlineKeyboardButton( text: '❌ مایل به شرکت نیستم', callback_data: 'vote-0-form-' . $data[ 1 ] ),
                                        ]
                                    ] )
                                )->SendMessageHtml( $message );

                            }

                        }
                        else
                        {
                            $telegram->deleteMessage( $user->getUserId(), $this->messageid );
                        }


                        break;

                }

                $telegram->answerCallbackQuery( $this->dataid, 'با موفقیت انجام شد ✅' );

                break;

            case 'participate_in_vote':

                $telegram->editKeyboard(
                    $user->getUserId(), $this->messageid, $telegram->buildInlineKeyBoard( [
                    [
                        $telegram->buildInlineKeyboardButton( text: '⭐️⭐️⭐️⭐️⭐️', callback_data: 'vote-5-' . $data[ 1 ] . '-' . $data[ 2 ] ),
                    ],
                    [
                        $telegram->buildInlineKeyboardButton( text: '⭐️⭐️⭐️⭐️', callback_data: 'vote-4-' . $data[ 1 ] . '-' . $data[ 2 ] ),
                    ],
                    [
                        $telegram->buildInlineKeyboardButton( text: '⭐️⭐️⭐️', callback_data: 'vote-3-' . $data[ 1 ] . '-' . $data[ 2 ] ),
                    ],
                    [
                        $telegram->buildInlineKeyboardButton( text: '⭐️⭐️️', callback_data: 'vote-2-' . $data[ 1 ] . '-' . $data[ 2 ] ),
                    ],
                    [
                        $telegram->buildInlineKeyboardButton( text: '⭐️️', callback_data: 'vote-1-' . $data[ 1 ] . '-' . $data[ 2 ] ),
                    ]
                ] )
                );

                break;

            case 'vote':

                $model = match ( $data[ 2 ] )
                {
                    'form' => Form::class
                };

                if ( ! Vote::where( 'user_id', $user->getUserId() )->where( 'model', $model )->where( 'model_id', $data[ 3 ] )->exists() )
                {

                    Vote::create( [

                        'user_id'  => $user->getUserId(),
                        'star'     => $data[ 1 ],
                        'model'    => $model,
                        'model_id' => $data[ 3 ],

                    ] );

                    $message = '✔️ نظر شما ثبت شد ✅' . "\n \n";
                    $message .= 'در صورتی که نظری دارید و دوست دارید به گوش ما برسانید میتوانید از طریق بخش ارتباط با ما، یا /contact_us به ما بگویید🙏';

                }
                else
                {

                    $message = '❌ شما قبلا در این نظرسنجی شرکت کرده اید.';

                }


                $telegram->editMessageText( $user->getUserId(), $this->messageid, $message );


                break;

            // --------------------------

            #food

            case 'plan_food':

                $message = '🔮 اشتراک مورد نظر شما :' . "\n";
                $message .= '👈 ' . Str::u( Subscription::PLANS[ $data[ 1 ] ][ 'name' ] ) . "\n \n";
                $message .= '💰هزینه اشتراک ( تومان )' . "\n";
                $message .= '🔸 قیمت: ' . Str::bu( number_format( Subscription::PLANS[ $data[ 1 ] ][ 'amount' ] ) . ' تومان' ) . "\n \n";
                $message .= '💳 روش پرداخت' . "\n";
                $message .= Str::bu( '🔸 درگاه امن زرین پال' ) . "\n \n";
                $message .= '🔻 شرایط و قوانین استفاده از سرویس رزرو اتوماتیک غذای سلف را قبول دارم و قصد خرید اشتراک دارم ✅';

                $telegram->editMessageText(
                    $user->getUserId(), $this->messageid, $message, $telegram->buildInlineKeyBoard( [
                    [
                        $telegram->buildInlineKeyboardButton( text: '📚 شرایط و قوانین', callback_data: 'rr' ),
                    ],
                    [
                        $telegram->buildInlineKeyboardButton( text: '🛒 پذیرش قوانین و خرید اشتراک ✅', callback_data: 'buy_food-' . $data[ 1 ] ),
                    ]
                ] )
                );

                break;

            case 'buy_food':

                $telegram->editMessageText( $user->getUserId(), $this->messageid, '🔄 در حال صدور صورتحساب ' );

                $payment = new Payment( Subscription::PLANS[ $data[ 1 ] ][ 'amount' ], $user->getUserId() );
                $payment->config()->detail( 'detail', [ 'type' => 'subscription', 'subscription' => [ 'id' => $data[ 1 ] ] ] );
                $payment->config()->detail( 'description', 'خرید اشتراک - ' . $data[ 1 ] . '-' . $user->getUserId() );
                $payment_url = $payment->toUrl();

                for ( $i = 0; $i < 2; $i ++ )
                {

                    for ( $j = 1; $j <= 4; $j ++ )
                    {

                        $buffer = str_repeat( '▪️', $j );
                        $telegram->editMessageText( $user->getUserId(), $this->messageid, $buffer . ' در حال صدور صورتحساب ' . $buffer );
                        sleep( 1 );

                    }

                }

                $message = '🧾 فاکتور پرداخت برای شما ساخته شد✅' . "\n \n";
                $message .= Str::b( '💠 مشخصات فاکتور:' ) . "\n";
                $message .= '💰 مبلغ: ' . Str::b( number_format( $payment->getAmount() ) . ' تومان' ) . "\n";
                $message .= '📦بابت: اشتراک ' . Str::bu( Subscription::PLANS[ $data[ 1 ] ][ 'name' ] ) . "\n \n";
                $message .= '⚠️ لطفا توجه داشته باشید هنگام پرداخت از استفاده هرگونه ' . Str::bu( 'فیلترشکن خودداری' ) . ' کنید.' . "\n \n";
                $message .= '⚠️ توجه درگاه پرداخت از سمت زرین پال تایید و قابل اعتماد است ✅' . "\n";
                $message .= Str::b( $payment_url ) . "\n";
                $message .= '📍 لینک یک بار مصرف و 2 دقیقه زمان دارد استفاده از آن است.' . "\n\n";
                $message .= Str::b( '👇 برای پرداخت بر روی دکمه زیر کلیک کنید👇' );
                $telegram->editMessageText(
                    $user->getUserId(), $this->messageid, $message, $telegram->buildInlineKeyBoard( [
                    [
                        $telegram->buildInlineKeyboardButton( '💳 پرداخت', $payment_url )
                    ]
                ] )
                );

                break;

            case 'setting_food':

                if ( $user->subscription() > 0 )
                {

                    $message = '📲 لطفا شماره دانشجویی و رمز عبور سامانه سماد خود را ارسال کنید.' . "\n \n";
                    $message .= '⚠️ لطفا طبق الگوریتم زیر شماره دانشجویی و رمز عبور را ارسال کنید.' . "\n";
                    $message .= 'شماره دانشجویی رمز عبور' . "\n";
                    $message .= '🔸 مثال:' . "\n";
                    $message .= '00112233 44556677' . "\n";
                    $message .= '▪️ منظور این است که اول شماره دانشجویی خود را نوشته و با گذاشتن یک فاصله رمز عبور را وارد کنید.';
                    $telegram->editMessageText( $user->getUserId(), $this->messageid, $message );
                    $user->setStatus( 'get_setting_food' );

                }
                else
                {
                    throw new ExceptionWarning( 'برای استفاده از این بخش نیاز است اشتراک تهیه کنید.' );
                }

                break;

            case 'setting_reserve_notification':


                $user->update( [
                    'reserve_status' => $user->user()->reserve_status == 'off' ? 'on' : 'off'
                ] );

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

                $telegram->editMessageText(
                    $user->getUserId(), $this->messageid, $message, $telegram->buildInlineKeyBoard( [
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
                );

                break;

            case 'reserved':

                $telegram->answerCallbackQuery( $this->dataid, '✅ از همکاری شما سپاس گذاریم 🤝' );
                $telegram->editMessageText( $user->getUserId(), $this->messageid, $this->callback_query->message->text );
                $user->update( [

                    'reserve_status' => 'done'

                ] );

                break;

            // ----------------------------

            default:

                $telegram->answerCallbackQuery( $this->dataid, '🔴 بزودی این بخش فعال می شود.' );

                break;

        }


    }

    /**
     * @return void
     * @throws \Exception
     */
    public function channel()
    {

        $telegram = tel();

        $data = $this->query_data;
        switch ( $data[ 0 ] )
        {

            case 'accept_event':

                $participant_event = ParticipantEvents::find( $data[ 1 ] );
                $user_owner        = new User( $participant_event->user_id );

                $message = '✔️ ثبت نام شما تکمیل شد ✅' . "\n \n";
                $message .= '⚠️ لطفا از مسدود کردن ربات خودداری کنید زیرا تمام اطلاع رسانی های مربوط به مسابقه از طریق ربات به شما اطلاع داده خواهد شد.';
                $user_owner->SendMessageHtml( $message );

                $participant_event->data = array_merge( $participant_event->data, [
                    'status' => 'accept'
                ] );
                $participant_event->save();

                $message = \Illuminate\Support\Str::replace( '✅ با ثبت نام این تیم موافقت میکنید؟', '✔️ توسط ' . $this->fromid . ' تایید شد ✅', $this->callback_query->message->text );
                $telegram->editMessageText( $this->chatid, $this->messageid, $message );

                break;

            case 'reject_event':

                $participant_event = ParticipantEvents::find( $data[ 1 ] );
                $user_owner        = new User( $participant_event->user_id );

                $message = '❌ تیم شما رد شد. لطفا اطلاعات ثبت شده را مجدد بررسی کنید.';
                $user_owner->SendMessageHtml( $message );

                $participant_event->data = array_merge( $participant_event->data, [
                    'status' => 'ready'
                ] );
                $participant_event->save();

                $telegram->deleteMessage( $this->chatid, $this->messageid );

                break;

            default:

                $telegram->answerCallbackQuery( $this->dataid, '🔴 بزودی این بخش فعال می شود.' );

                break;

        }

    }

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

        $telegram = tel();

        $data = $this->query_data;
        switch ( $data[ 0 ] )
        {

            case 'reserved_group':

                $telegram->answerCallbackQuery( $this->dataid, '🙂 خوشحالیم که تونستیم بهت کمک کنیم ☺️🤝' . "\n" . '📣 @montazeri_computer', true );

                break;

            default:

                $telegram->answerCallbackQuery( $this->dataid, '🔴 بزودی این بخش فعال می شود.' );

                break;

        }

    }

    /**
     * @return void
     * @throws \Exception
     */
    public function index()
    {

        $telegram = tel();

        $data = $this->query_data;
        switch ( $data[ 0 ] )
        {

            case 'update_time':

                if ( date( 'Y-m-d H:i:s' ) < '2024-03-20 06:36:26' )
                {

                    if ( $this->spam( $this->callback_query->from->id ) )
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

                        $telegram->endpoint( 'editMessageText', [
                            'inline_message_id'        => $this->callback_query->inline_message_id,
                            'text'                     => $message,
                            'parse_mode'               => 'html',
                            'reply_markup'             => json_encode( [
                                'inline_keyboard' => [
                                    [
                                        [ 'text' => '♻️ بررسی مجدد', 'callback_data' => 'update_time' ]
                                    ],
                                    [
                                        [ 'text' => '↗️ اشتراک گذاری', 'switch_inline_query' => '' ]
                                    ],
                                ]
                            ] ),
                            'disable_web_page_preview' => true
                        ] );

                        $telegram->answerCallbackQuery( $this->callback_query->id, '♻️ بروزرسانی با موفقیت انجام شد ✅', true );

                    }
                    else
                    {

                        $telegram->answerCallbackQuery( $this->callback_query->id, '❌ هر کاربر هر 1 دقیقه یک بار میتواند بروزرسانی را انجام دهد❗️', true );

                    }

                }
                else
                {

                    $message = '<b><u>🎉 سال 1403 بر شما مبارک 🎉</u></b>' . "\n \n";
                    $message .= '<b>🔸 انجمن علمی کامپیوتر دانشکده منتظری فرارسیدن سال نو را به شما تبریک می گویید🎊🤝</b>' . "\n \n";
                    $message .= '➖➖➖➖➖➖➖➖' . "\n" . '📣 @Montazeri_Computer';
                    $telegram->endpoint( 'editMessageText', [
                        'inline_message_id'        => $this->callback_query->inline_message_id,
                        'text'                     => $message,
                        'parse_mode'               => 'html',
                        'disable_web_page_preview' => true
                    ] );

                }

                break;

            case 'answer_vote_game':

                $telegram->endpoint( 'editMessageText', [

                    'inline_message_id' => $this->callback_query->inline_message_id,
                    'text'              => '🎗 واحد ارتباط با صنعت انجمن علمی کامپیوتر دانشکده منتظری مشهد:

💡 انجمن علمی کامپیوتر منتظری قصد دارد <b>با همکاری دیگر انجمن های کامپیوتر مشهد</b> و <b>یکی از بهترین شرکت های بازی سازی خراسان</b> یک برنامه به صورت مشترک در حوزه 🎮 بازی سازی برگزار کند و برای این موضوع نیازمندیم بدانیم چه تعداد از دوستان به حوزه بازی سازی علاقه‌مند هستند به همین منظور، لطفاً در نظر سنجی زیر شرکت کنید🙏',

                    'parse_mode' => 'html'

                ] );
                $telegram->answerCallbackQuery( $this->dataid, '✨ این رای گیری به اتمام رسیده است 🥹' );
                die();
                if ( ! DB::table( 'game_vote' )->where( 'user_id', $this->fromid )->exists() )
                {

                    DB::table( 'game_vote' )->insert( [

                        'user_id'       => $this->fromid,
                        'update'        => json_encode( $this->update ),
                        'info'          => ( json_encode( $telegram->getChat( $this->fromid ) ) ?? null ),
                        'option_select' => $data[ 1 ],

                    ] );

                    $telegram->answerCallbackQuery(
                        $this->dataid,
                        '✨ رای شما با موفقیت ثبت گردید ✅',
                        true
                    );

                }
                else
                {

                    $telegram->answerCallbackQuery(
                        $this->dataid,
                        '✋ شما قبلا در این رای گیری شرکت کردید ❌',
                        true
                    );

                }


                break;

            default:

                $telegram->answerCallbackQuery( $this->dataid, '🔴 بزودی این بخش فعال می شود.' );

                break;

        }

    }

    /**
     * @param int $parent
     * @return array
     * @throws \Exception
     */
    public function menu( int $parent ) : array
    {
        $tel       = tel();
        $menus     = Menu::on()->where( 'parent', $parent )->orderBy( 'row' )->orderBy( 'col' )->orderBy( 'created_at' )->get();
        $keyboard  = [];
        $last      = [];
        $last_temp = [];

        $i    = 0;
        $x    = 0;
        $temp = - 1;
        foreach ( $menus as $item )
        {
            if ( $temp == - 1 ) $temp = $item->row;
            if ( $item->row != $temp )
            {
                $i ++;
                $x = 0;
                if ( count( $keyboard[ $i - 1 ] ) < 3 )
                    $keyboard[ $i - 1 ][] = $tel->buildInlineKeyboardButton( '➕ اضافه کردن ستون', '', 'new_menu-' . $last_temp->parent . '-' . $last_temp->row . '-' . ( $last_temp->col + 1 ) );
                $temp = $item->row;
            }

            $keyboard[ $i ][ $x ++ ] = $tel->buildInlineKeyboardButton( $item->name, '', 'menu-' . $item->id );

            $last      = $item;
            $last_temp = $item;
            $last->row = $i;
            $last->col = $x - 1;

        }

        if ( $parent > 0 )
        {

            $st = count( $keyboard ) == 1;
            if ( isset( $last->row ) && $st ) $keyboard[][] = $tel->buildInlineKeyboardButton( '➕ اضافه کردن سطر', '', 'new_row_menu-' . $last->id );

            if ( isset( $last->id ) && count( $keyboard[ $last->row ] ) < 3 ) $keyboard[ $last->row ][] = $tel->buildInlineKeyboardButton( '➕ اضافه کردن ستون', '', 'new_menu-' . $last->parent . '-' . $last->row . '-' . ( $last->col + 1 ) );
            if ( isset( $item->id ) && count( $keyboard[ $item->row ] ) < 4 && ! $st ) $keyboard[][] = $tel->buildInlineKeyboardButton( '➕ اضافه کردن سطر', '', 'new_row_menu-' . $item->id );

            if ( count( $keyboard ) == 0 ) $keyboard[][] = $tel->buildInlineKeyboardButton( '➕ اضافه کردن سطر و ستون', '', 'new_sub_menu-' . $parent );

            $back         = Menu::on()->where( 'parent', '<', $parent )->groupBy( 'parent' )->orderByDesc( 'parent' )->limit( 1 )->first();
            $keyboard[][] = $tel->buildInlineKeyboardButton( '↩️ بازگشت به منو قبل', '', 'menu-' . $back->parent );
            if ( $parent > 1 ) $keyboard[ count( $keyboard ) - 1 ][] = $tel->buildInlineKeyboardButton( '🗑 حذف منو', '', 'delete_menu-' . $parent );

        }
        return $keyboard;
    }

    /**
     * @param int $user_id
     * @param int $timeRef
     * @return bool
     */
    public function spam( int $user_id, int $timeRef = 60 ) : bool
    {

        Storage::makeDirectory( '/telegram/users/' . $user_id );
        $path = '/telegram/users/' . $user_id . '/time.txt';

        if ( ! Storage::exists( $path ) || ( time() - Storage::get( $path ) ) > $timeRef )
        {

            Storage::put( $path, time() );
            return true;

        }

        return false;

    }

}
