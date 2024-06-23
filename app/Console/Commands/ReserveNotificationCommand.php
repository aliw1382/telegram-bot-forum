<?php

namespace App\Console\Commands;

use App\helper\Str;
use App\Models\User;
use Illuminate\Console\Command;
use Morilog\Jalali\Jalalian;
use PharIo\Manifest\Exception;

class ReserveNotificationCommand extends Command
{
    protected $signature = 'reserve:notification';

    protected $description = 'Command description';

    /**
     * @return void
     * @throws \Exception
     */
    public function handle() : void
    {

        $today    = strtolower( date( 'l' ) );
        $telegram = telegram();

        $message = '🔔 امروز ' . Str::b( Jalalian::now()->format( 'd F Y' ) ) . ' است. امروز زمان رزو غذا است🍕' . "\n \n";
        $message .= '🏷 مراحل رزو غذا:' . "\n";
        $message .= '1️⃣ وارد سایت سماد شده ( ' . Str::a( 'ورژن قدیمی', 'https://saba1.tvu.ac.ir/index.rose' ) . ' ، ' . Str::a( 'ورژن جدید', 'https://samad.app/' ) . ' ) ' . Str::u( 'نام کاربری' ) . ' شما همان ' . Str::b( 'شماره دانشجویی' ) . ' شما است و ' . Str::u( 'رمز عبور' ) . ' همان ' . Str::b( 'کد ملی' ) . ' شما می باشد.' . "\n";
        $message .= '2️⃣ بر روی رزو غذا کلیک کرده و دانشگاهی که در آن مشغول به تحصیل هستید را انتخاب کنید.' . "\n";
        $message .= '3️⃣ غذا هفته آینده را باز کرده سپس روز هایی که میخواهید غذا دریافت کنید را انتخاب کنید.' . "\n";
        $message .= '4️⃣ سپس اقدام به افرایش موجودی کنید.' . "\n";
        $message .= '5️⃣ به سایت برگشته و دوباره اقدام به انتخاب غذا های هفته کنید، پس از آن بر روی گزینه تایید کلیک کرده ' . Str::b( '( توجه کنید که پیغام رزو غذا موفق بود برای شما نمایش داده شود )' ) . "\n";
        $message .= '6️⃣ بر روی رزرو کردم کلیک کنید تا دیگر پیام یادآوری برای شما ارسال نشود.' . "\n \n";
        $message .= 'موفق و پیروز باشید 🙏';


        if (
            in_array( $today, [
                'sunday',
                'monday',
                'tuesday',
                'wednesday',
                'wednesday',
                'thursday',
            ] )
        )
        {

            $users = User::where( 'reserve_status', 'on' )->get();

            foreach ( $users as $user )
            {

                $user = new \App\helper\User( $user->user_id );
                try
                {
                    if ( ! $user->isOnChannel() ) continue;
                }
                catch ( Exception | \Throwable $exception )
                {
                }

                $user->setKeyboard(
                    $telegram->buildInlineKeyBoard( [
                        [
                            $telegram->buildInlineKeyboardButton( '📋 رزرو کردم', '', 'reserved' )
                        ]
                    ] )
                )->SendMessageHtml( $message );


            }

        }


        if ( $today == 'wednesday' )
        {

            $message = '🔔 امروز ' . Str::b( Jalalian::now()->format( 'd F Y' ) ) . ' است. امروز زمان رزو غذا است🍕' . "\n \n";
            $message .= '🏷 مراحل رزو غذا:' . "\n";
            $message .= '1️⃣ وارد سایت سماد شده ( ' . Str::a( 'ورژن قدیمی', 'https://saba1.tvu.ac.ir/index.rose' ) . ' ، ' . Str::a( 'ورژن جدید', 'https://samad.app/' ) . ' ) ' . Str::u( 'نام کاربری' ) . ' شما همان ' . Str::b( 'شماره دانشجویی' ) . ' شما است و ' . Str::u( 'رمز عبور' ) . ' همان ' . Str::b( 'کد ملی' ) . ' شما می باشد.' . "\n";
            $message .= '2️⃣ بر روی رزو غذا کلیک کرده و دانشگاهی که در آن مشغول به تحصیل هستید را انتخاب کنید.' . "\n";
            $message .= '3️⃣ غذا هفته آینده را باز کرده سپس روز هایی که میخواهید غذا دریافت کنید را انتخاب کنید.' . "\n";
            $message .= '4️⃣ سپس اقدام به افرایش موجودی کنید.' . "\n";
            $message .= '5️⃣ به سایت برگشته و دوباره اقدام به انتخاب غذا های هفته کنید، پس از آن بر روی گزینه تایید کلیک کرده ' . Str::b( '( توجه کنید که پیغام رزو غذا موفق بود برای شما نمایش داده شود )' ) . "\n";
            $message .= 'موفق و پیروز باشید 🙏';

            foreach ( cache()->get( 'reserve_groups' ) as $item )
            {

                $telegram->sendMessage(
                    $item, $message, $telegram->buildInlineKeyBoard( [
                    [
                        $telegram->buildInlineKeyboardButton( '📋 رزرو کردم', '', 'reserved_group' )
                    ]
                ] )
                );

            }

        }


        if ( $today == 'thursday' )
        {

            User::where( 'reserve_status', 'done' )->update( [
                'reserve_status' => 'on'
            ] );

        }

        $this->info( 'Successfully' );

    }
}
