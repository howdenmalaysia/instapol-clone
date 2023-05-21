<?php

namespace App\Exports\DropOffReport;

use App\Models\Motor\Insurance;
use App\Models\Motor\Quotation;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PaymentSummary implements FromCollection, WithColumnFormatting, WithEvents, WithMapping, WithTitle, ShouldAutoSize
{
    use RegistersEventListeners;

    protected $start_time;
    protected $end_time;

    public function __construct(string $start_time, string $end_time)
    {
        $this->start_time = $start_time;
        $this->end_time = $end_time;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // Drop off at Summary Page
        $summary = Insurance::with(['product', 'holder', 'motor', 'address'])
            ->where('updated_at', '>=', $this->start_time)
            ->where('updated_at', '<=', $this->end_time)
            ->whereIn('insurance_status', [Insurance::STATUS_NEW_QUOTATION, Insurance::STATUS_PAYMENT_FAILURE])
            ->get();

        Log::info("[Cron - Drop-Off Report] There were {$summary->count()} user(s) dropped at Payment Summary Page.");

        return $summary;
    }

    public function title(): string
    {
        return 'Payment Summary Page';
    }

    public function map($result): array
    {
        $address = $result->address->address_one;
        if(!empty($result->address->address_two)) {
            $address .= ', ' . implode(', ', [$result->address->address_two, $result->address->postcode, $result->address->city, $result->address->state]);
        } else {
            $address .= ', ' . implode(', ', [$result->address->postcode, $result->address->city, $result->address->state]);
        }

        return [
            $result->updated_at,
            $result->expiry_date,
            $result->insurance_code,
            $result->created_at,
            $result->inception_date,
            $result->amount,
            $result->product->name,
            $result->motor->vehicle_number,
            $result->address->postcode,
            implode(' ', [$result->motor->make, $result->motor->model, $result->motor->variant]),
            $result->motor->manufactured_year,
            $result->motor->market_value,
            $result->holder->name,
            $result->holder->id_number,
            $result->holder->gender === 'F' ? 'Female' : 'Male',
            $result->holder->email_address,
            $result->holder->phone_code . $result->holder->phone_number,
            $address,
            $result->referrer
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_DATE_DATETIME,
            'B' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'D' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'E' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'F' => '"RM"#,##0.00_-',
            'L' => '"RM"#,##0.00_-',
        ];
    }

    public static function afterSheet(AfterSheet $event)
    {
        $header_style = [
            'font' => [
                'bold' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000']
                ]
            ]
        ];

        $body_style = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000']
                ]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => 'center'
            ]
        ];

        $headings = [
            [
                '',
                'Insurance Details',
                '',
                '',
                '',
                '',
                '',
                'Vehicle Details',
                '',
                '',
                '',
                '',
                'Policy Holder Details',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                ''
            ],
            [
                'Access Date & Time',
                'Policy Expiry Date',
                'Insurance Code',
                'Transaction Date',
                'Policy Start Date',
                'Total Payable',
                'Product',
                'Vehicle Number',
                'Postcode',
                'Make & Model',
                'Manufactured Year',
                'Agreed / Market Value',
                'Name',
                'ID Number',
                'Gender',
                'Email Address',
                'Phone Number',
                'Residential Address',
                'Referrer'
            ]
        ];

        $event->sheet->insertNewRowBefore(1, 2);
        foreach ($headings as $row => $_headings) {
            foreach ($_headings as $column => $heading) {
                $event->sheet->setCellValueByColumnAndRow($column + 1, $row + 1, $heading);
            }
        }

        $event->sheet->getDelegate()->mergeCells('B1:F1');
        $event->sheet->getDelegate()->mergeCells('G1:K1');
        $event->sheet->getDelegate()->mergeCells('L1:P1');

        $event->sheet->getStyle('A1:P1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $event->sheet->getStyle('A1:P1')->applyFromArray($header_style);

        $event->sheet->getStyle('A2:P2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $event->sheet->getStyle('A2:P2')->applyFromArray($header_style);

        // Set Border To All Cells & Set Alignment
        $event->sheet->getStyle('A3:' . $event->sheet->getHighestColumn() . $event->sheet->getHighestRow())->applyFromArray($body_style);
    }
}
