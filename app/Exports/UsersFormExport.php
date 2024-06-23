<?php

namespace App\Exports;

use App\Models\Form;
use App\Models\Payment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Events\BeforeSheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Worksheet;

class UsersFormExport implements FromCollection, ShouldAutoSize, WithEvents, WithTitle
{

    /**
     * @var Form
     */
    private Form $form;

    /**
     * @param string $hash
     */
    public function __construct( protected string $hash )
    {
        $this->form = Form::where( 'hash', $this->hash )->firstOrFail();
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {

        $data = collect( [] );

        $form = $this->form;

        $questions = [];
        foreach ( $form->questions as $question )
        {
            if ( $question[ 'validate' ] == 'payment' )
                $questions[] = 'اطلاعات پرداختی';
            else
                $questions[] = $question[ 'name' ];
        }
        $questions[] = 'یوزر آیدی';

        $data->add( $questions );

        foreach ( $form->users as $item )
        {

            $temp = collect( (array) $item->value );

            $temp->push( $item->user_id );

            if ( isset( $item->value[ 'payment_id' ] ) )
            {
                $temp->put( 'payment_id', Payment::find( $item->value[ 'payment_id' ] )->ref_id );
            }

            $data->add( $temp );

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

    public function title() : string
    {
        return $this->form->name;
    }
}
