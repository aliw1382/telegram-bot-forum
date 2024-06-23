<?php

namespace App\Observers;

use App\helper\Str;
use App\helper\User;
use App\Models\ParticipantEvents;

class ParticipantEventsObserver
{

    /**
     * @param ParticipantEvents $participantEvents
     * @return void
     * @throws \Exception
     */
    public function created( ParticipantEvents $participantEvents ) : void
    {

        $message = '🔔 کاربر جدیدی در رویداد " ' . $participantEvents->event->title . ' " شرکت کرد.' . "\n \n";

        $user = new User( $participantEvents->user_id );

        $message .= '📡 مشخصات کاربر:  ' . $user->mention( 'Profile' ) . "\n";
        $message .= '👤 آیدی عددی:  ' . $user->code() . "\n";
        if ( ! empty( $user->name ) ) $message .= '👤 نام و نام خانوادگی:  ' . Str::bu( $user->name ) . "\n";
        $message .= "\n";

        if ( is_numeric( $participantEvents->student_id ) )
        {

            $link    = $participantEvents->stu();
            $message .= '🔗 حساب متصل:' . "\n";
            $message .= '👤 نام و نام خانوادگی: ' . "<b><u>" . $link->uni->first_name . ' ' . $link->uni->last_name . "</u></b>" . "\n";
            $message .= '🎗 شماره دانشجویی: ' . "<b><code>" . $link->uni->students_id . "</code></b>" . "\n";
            $message .= '🏢 دانشگاه: ' . "<b>" . $link->uni->uni->name . "</b>" . "\n";
            $message .= '🎓 رشته تحصیلی: ' . "<b>" . $link->uni->section->name . "</b>" . "\n \n";

        }

        $message .= '🏷 شیوه ثبت نام:  ';
        switch ( $participantEvents->payment_type )
        {

            case 'LoginAccount':

                $message .= Str::b( '👤 حساب کاربری وارد شده' ) . "\n";

                break;

            case 'payment':

                $message .= Str::b( '💳 درگاه پرداخت' ) . "\n";
                $message .= '📍 شماره تراکنش: ' . Str::codeB( ( $participantEvents->data[ 'ref_id' ] ?? 'یافت نشد' ) ) . "\n";
                $message .= '📬 توکن تراکنش: ' . Str::codeB( ( $participantEvents->data[ 'authority' ] ?? 'یافت نشد' ) ) . "\n";

                break;

            case 'JoinTeam':

                $message .= Str::b( '💠 پیوستن به تیم' ) . "\n";

                break;

            case 'AdminRegister':

                $message .= '🛂 توسط ادمین ( ' . Str::code( $participantEvents->data[ 'admin_id' ] ?? 'Not Found' ) . ' )' . "\n";

                break;

            default:

                $message .= Str::b( '⚠️ خطا در پردازش نحوه پرداخت' ) . "\n";

                break;

        }

        $message .= "\n" . "🗓 تاریخ ثبت نام:" . "\n" . Str::codeB( jdate()->format( 'Y/m/d H:i:s' ) );

        telegram()->sendMessage( env( 'CHANNEL_LOG' ), $message );

        if ( $participantEvents->payment_type != 'JoinTeam' )
        {

            $participantEvents->event->count = $participantEvents->event->count - 1;
            $participantEvents->event->save();

        }


    }

}
