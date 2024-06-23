<?php

namespace App\helper;

use App\Models\Payment as PaymentModel;
use Illuminate\Support\Facades\File;
use Shetabit\Multipay\Invoice;
use Shetabit\Multipay\Payment as PaymentClass;

class Payment
{
    /**
     * @var \Shetabit\Multipay\Invoice
     */
    private Invoice $invoice;

    /**
     * @var int
     */
    private int $amount;

    /**
     * @var int
     */
    private int $user_id;

    /**
     * @var \Shetabit\Multipay\Payment
     */
    private PaymentClass $payment;

    /**
     * @param int $amount
     * @param int $user_id
     * @throws \Exception
     */
    public function __construct( int $amount, int $user_id )
    {

        $paymentConfig = require( config_path( 'payment.php' ) );

        $payment = new PaymentClass( $paymentConfig );


        $invoice = new Invoice();

        $this->amount  = $amount;
        $this->user_id = $user_id;
        $invoice->amount( $amount );

        $this->invoice = $invoice;
        $this->payment = $payment;

    }

    /**
     * @return \Shetabit\Multipay\Invoice
     */
    public function config() : Invoice
    {
        return $this->invoice;
    }

    /**
     * @var object
     */
    private object $data;

    /**
     * @return \App\helper\Payment
     * @throws \Exception
     */
    private function render() : Payment
    {
        $this->payment->config( 'description', $this->invoice->getDetail( 'description' ) );

        $result     = $this->payment->callbackUrl( route( 'payment' ) )->purchase( $this->invoice, function ( $driver, $transactionId ) {

            $paymentConfig = require( config_path( 'payment.php' ) );
            PaymentModel::create( [

                'user_id'        => $this->user_id,
                'transaction_id' => $transactionId,
                'amount'         => $this->amount,
                'detail'         => $this->invoice->getDetail( 'detail' ),
                'driver'         => $paymentConfig[ 'default' ]

            ] );

        } );

        $jsonData   = $result->pay()->toJson();
        $this->data = json_decode( $jsonData );
        return $this;
    }

    /**
     * @return string|null
     * @throws \Exception
     */
    public function toUrl() : ?string
    {
        return $this->render()->data->action ?? null;
    }

    /**
     * @param string $auth
     * @return bool
     */
    public static function exists( string $auth ) : bool
    {
        return (bool) PaymentModel::where( 'transaction_id', $auth )->exists();
    }

    /**
     * @param string $auth
     * @return PaymentModel|false
     */
    public static function payment( string $auth )
    {
        if ( ! self::exists( $auth ) ) return false;
        return PaymentModel::where( 'transaction_id', $auth )->first();
    }

    /**
     * @param string $auth
     * @param string $ref_id
     * @return false|mixed
     */
    public static function verify( string $auth, string $ref_id )
    {
        if ( ! self::exists( $auth ) ) return false;
        return PaymentModel::where( 'transaction_id', $auth )->update( [
            'ref_id' => $ref_id
        ] );
    }

    /**
     * @return int
     */
    public function getAmount() : int
    {
        return $this->amount;
    }

}
