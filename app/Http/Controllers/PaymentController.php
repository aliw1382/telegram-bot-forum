<?php

namespace App\Http\Controllers;

use App\Broadcasts\PaymentMobileBroadcast;
use App\helper\Payment as PaymentHelper;
use App\helper\Str;
use App\helper\User;
use App\Http\Controller;
use App\Models\AddressQrUser;
use App\Models\Event;
use App\Models\Form;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsersForm;
use App\Models\UsersParticipatePart;
use Illuminate\Http\Request;
use Illuminate\Support\Str as StrSupport;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Payment\Facade\Payment;

class PaymentController extends Controller
{

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Exception
     */
    public function verify( Request $request )
    {

        $auth   = $request->get( 'Authority' );
        $status = $request->get( 'Status' );

        $telegram = tel();

        if ( isset( $auth ) && isset( $status ) && PaymentHelper::exists( $auth ) )
        {

            try
            {

                $transaction = PaymentHelper::payment( $auth );
                $user        = new User( $transaction->user_id, false );

                if ( $transaction->ref_id === null )
                {

                    $receipt = Payment::amount( $transaction->amount )->transactionId( $transaction->transaction_id )->verify();
                    PaymentHelper::verify( $auth, $receipt->getReferenceId() );

                    $message = '📮 پرداخت شما با موفقیت انجام شد ✅' . "\n \n";
                    $message .= '💳 شماره تراکنش: ' . $receipt->getReferenceId() . "\n";
                    $message .= '💰 مبلغ تراکنش: ' . number_format( $transaction->amount ) . ' تومان' . "\n";
                    $message .= '📍درگاه پرداخت: زرین پال' . "\n";
                    $message .= '📅 زمان تراکنش: ' . "\n";
                    $message .= jdate()->format( 'Y/m/d H:i:s' );
                    $user->SendMessageHtml( $message );

                    $detail = (object) $transaction->detail;

                    switch ( $detail->type )
                    {

                        case 'event':

                            $event = Event::find( $detail->event[ 'id' ] );

                            if ( $user->registerEvent( $event, 'payment', [ 'id' => $transaction->id, 'ref_id' => $receipt->getReferenceId(), 'authority' => $auth ] ) )
                            {

                                $message = '✅ شما با موفقیت در دوره " ' . Str::b( $event->title ) . ' " ما ثبت نام شدید🤝' . "\n \n";
                                $message .= '🔔 لطفا از حذف ربات خودداری کنید اطلاعات مربوط به ورود به دوره و نحوه برگزاری دوره به شما از طریق ربات اطلاع رسانی می گردد.';

                            }
                            else
                            {

                                $message = '❌ شما قبلا در این دوره ثبت نام کرده اید✋';
                                $user->addWallet( $transaction->amount, 'بابت شرکت مجدد در دوره ' . $event->title );

                            }

                            $user->SendMessageHtml( $message );

                            break;

                        case 'race':

                            $event = Event::find( $detail->event[ 'id' ] );

                            if ( $user->registerEvent( $event, 'payment', array_merge( [ 'id' => $transaction->id, 'ref_id' => $receipt->getReferenceId(), 'authority' => $auth ], ( $event->data[ 'type_join' ] == 2 ? [ 'role' => 'owner' ] : [] ) ) ) )
                            {

                                $message = '✔️ نام شما در مسابقه " ' . Str::b( $event->title ) . ' " با موفقیت اضافه شد ✅' . "\n \n";
                                if ( $event->data[ 'type_join' ] == 2 ) $message .= 'برای ورود و ' . Str::bu( 'تکمیل ثبت نام' ) . ' دستور /panel را ارسال کنید 🤝' . "\n \n";
                                $message .= Str::bu( '⚠️ همچنین از مسدود یا حذف ربات خودداری کنید زیرا تمام اطلاع رسانی های مربوط به مسابقات از طریق ربات به شما اطلاع رسانی خواهد شد🙏' );
                                $user->SendMessageHtml( $message );

                            }
                            else
                            {

                                $message = '❌ شما قبلا در این مسابقه ثبت نام کرده اید✋';
                                $user->addWallet( $transaction->amount, 'بابت شرکت مجدد در دوره ' . $event->title );

                            }

                            $user->SendMessageHtml( $message );

                            break;

                        case 'form':

                            $form = Form::find( $detail->form[ 'id' ] );

                            $model = UsersForm::create( [

                                'user_id' => $user->getUserId(),
                                'form_id' => $form->id,
                                'value'   => array_merge( $detail->form[ 'data_form' ], [ 'payment_id' => $transaction->id ] )

                            ] );

                            $message = '✅ اطلاعات ثبت نام شما در فرم " ' . $form->name . ' " با موفقیت انجام شد 🙏' . "\n \n";
                            $message .= 'با تشکر از همکاری شما🤝';
                            $user->SendMessageHtml( $message );

                            if ( isset( $detail->mobile ) && $detail->mobile )
                            {

                                $qr = AddressQrUser::create( [

                                    'user_id'  => $transaction->user_id,
                                    'uuid'     => uniqid(),
                                    'model'    => $model::class,
                                    'model_id' => $model->id,

                                ] );

                                $participate = UsersParticipatePart::create( [

                                    'uuid'     => $qr->uuid,
                                    'ip'       => $request->ip(),
                                    'agent'    => $request->header( 'user-agent' ),
                                    'type'     => 'code',
                                    'model'    => $qr->model ?? null,
                                    'model_id' => $qr->model_id ?? null

                                ] );

                                PaymentMobileBroadcast::dispatch( [

                                    'status'      => true,
                                    'code'        => 200,
                                    'description' => 'success',
                                    'type'        => 'registered',
                                    'ID'          => $participate->id ?? $qr->uuid,
                                    'photo'       => null,
                                    'response'    => [

                                        'key'   => collect( $form->questions )
                                            ->pluck( 'name' )
                                            ->filter( fn( $value ) => ! is_numeric( $value ) )
                                            ->map( fn( $value ) => strip_tags( $value ) )
                                            ->toArray(),
                                        'value' => $model->value,

                                    ]

                                ], [ $detail->device_key ] );

                            }


                            break;

                        case 'subscription':

                            $subscription = Subscription::PLANS[ $detail->subscription[ 'id' ] ];

                            $message = '🎉 تبریک اشتراک شما فعال شد ✅' . "\n \n";
                            $message .= '🛍 اشتراک خریداری شده:' . Str::bu( $subscription[ 'name' ] ) . "\n \n";
                            $message .= '⚜️ لطفا از بخش تنظیمات اطلاعات مربوط به ورود به سامانه را وارد کنید🤝';
                            $user->addSubscription( $subscription[ 'day' ] )->SendMessageHtml( $message );

                            break;

                    }

                }
                else
                {

                    $message = '🔸 درخواست شما قبلا تایید شده است!';
                    $user->SendMessageHtml( $message );

                }

            }
            catch ( InvalidPaymentException $e )
            {

                $transaction = PaymentHelper::payment( $auth );
                $user        = new User( $transaction->user_id );

                switch ( $e->getCode() )
                {

                    case - 54:
                    case - 51:

                        $message = '❌ پرداخت توسط شما لغو شد.';
                        $user->SendMessage( $message );

                        break;

                    default:

                        $message = "<i>ERROR ON PAYMENT</i>" . "\n \n";
                        $message .= "<i>ERROR LINE: {" . $e->getLine() . "}</i>" . "\n \n";
                        $message .= "<i>ERROR Code: {" . $e->getCode() . "}</i>" . "\n \n";
                        $message .= "<u>ERROR ON FILE: {" . $e->getFile() . "}</u>" . "\n \n";
                        $message .= "<b>CONTACT ERROR: [" . $e->getMessage() . "]</b>";
                        tel()->sendMessage( env( 'ADMIN_LOG' ), $message );

                        break;
                }


            }

        }

        return redirect( 'https://yek.link/esa-pc' );

    }

}
