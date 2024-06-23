<?php

namespace App\Exports;

use App\Models\Event;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeExport;

class EventExport implements FromCollection, ShouldAutoSize, WithEvents, WithTitle
{

    /**
     * @var Event
     */
    protected Event $event;

    public function __construct( protected string $hash )
    {
        $this->event = Event::where( 'hash', $this->hash )->firstOrFail();
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {

        $data = collect();

        $event             = $this->event;
        $participate_event = $event->participate;

        switch ( $event->type )
        {

            case 2:

                $data->add( [

                    'شناسه',
                    'یوزر آیدی',
                    'رویداد',
                    'شماره دانشجویی',
                    'نام و نام خانوادگی',
                    'رشته تحصیلی',
                    'روش شرکت',
                    'اطلاعات روش شرکت',
                    'تاریخ ثبت نام'

                ] );

                foreach ( $participate_event as $item )
                {

                    $data->add( [

                        $item->id,
                        $item->user_id,
                        $event->title,
                        $item?->student?->students_id,
                        $item?->student?->first_name . ' ' . $item?->student?->last_name,
                        $item?->student?->section->name,
                        match ( $item->payment_type )
                        {
                            'payment'       => 'پرداخت',
                            'JoinTeam'      => 'دعوت به تیم ',
                            'LoginAccount'  => 'ورود به حساب',
                            'AdminRegister' => 'توسط ادمین '
                        },
                        match ( $item->payment_type )
                        {
                            'payment'       => $item->data[ 'ref_id' ],
                            'JoinTeam'      => $item->data[ 'id' ],
                            'AdminRegister' => $item->data[ 'admin_id' ],
                            default         => '',
                        },
                        jdate( $item->created_at )->format( 'Y-m-d H:i:s' )


                    ] );

                }

                break;

        }

        return $data;

    }

    /**
     * @return mixed
     */
    public function registerEvents() : array
    {
        return [
            BeforeExport::class => function ( BeforeExport $event ) {
                $event->writer->getDelegate()->getSecurity()->setLockWindows( true );
                $event->writer->getDelegate()->getSecurity()->setLockStructure( true );
                $event->writer->getDelegate()->getSecurity()->setWorkbookPassword( $this->hash );
            },
            AfterSheet::class   => function ( AfterSheet $event ) {
                $event->sheet->setRightToLeft( true );
                $event->sheet->getProtection()->setPassword( $this->hash );
            },
        ];
    }

    /**
     * @return string
     */
    public function title() : string
    {
        return $this->event->title;
    }
}
