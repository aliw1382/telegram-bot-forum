<?php

namespace App\Observers;

use App\helper\Str;
use App\helper\User;
use App\Models\Form;
use App\Models\Payment;
use App\Models\UsersForm;

class ParticipateFormObserver
{

    public function created( UsersForm $usersForm )
    {

        $user_item = new User( $usersForm->user_id );
        $form      = $usersForm->form;

        $message = '⚜️ کاربر: ' . $user_item->mention() . "\n";
        $message .= '📜 فرم: ' . $form->name . "\n \n";
        foreach ( $usersForm->value as $key => $item )
        {

            if ( $key == 'payment_id' )
            {

                $payment = Payment::find( $item );
                $message .= Str::b( '💳 درگاه پرداخت' ) . "\n";
                $message .= '📍 شماره تراکنش: ' . Str::codeB( ( $payment->ref_id ?? 'یافت نشد' ) ) . "\n";
                $message .= '📬 توکن تراکنش: ' . Str::codeB( ( $payment->transaction_id ?? 'یافت نشد' ) ) . "\n";

            }
            else
            {

                $message .= $form->questions[ $key ][ 'name' ] . " : " . $item . "\n";

            }

            if ( mb_strlen( $message ) > 4000 )
            {
                telegram()->sendMessage( $form->send_to, $message );
                $message = '';
            }

        }
        telegram()->sendMessage( $form->send_to, $message );

        if ( ! is_null( $form->participate ) )
        {

            $form->participate = $form->participate - 1;

            if ( $form->participate == 0 ) $form->status = Form::STATUS_DELETED;

            $form->save();

        }


    }

}
