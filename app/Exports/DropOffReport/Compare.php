<?php

namespace App\Exports\DropOffReport;

use App\Models\Motor\Quotation;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class Compare implements FromCollection, WithColumnFormatting, WithEvents, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
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
        // Drop off at Compare Page / Add Ons Page / Policy Holder Page
        $compare = Quotation::with('insurance_motor')
            ->doesntHave('insurance_motor')
            ->where('compare_page', 1)
            ->where('updated_at', '>=', $this->start_time)
            ->where('updated_at', '<=', $this->end_time)
            ->orderBy('id', 'DESC')
            ->get()
            ->unique('vehicle_number')
            ->sortBy('updated_at');

        Log::info("[Cron - Drop-Off Report] There were {$compare->count()} user(s) dropped at Compare / Add Ons / Policy Holder Page");

        return $compare;
    }

    public function title(): string
    {
        return 'Compare, Add Ons, Policy Holder Page';
    }

    public function headings(): array
    {
        return [
            'Access Date & Time',
            'Vehicle Number',
            'Vehicle Details',
            'Expiry Date',
            'ID Number',
            'Postcode',
            'Phone Number',
            'Email Address',
            'Remarks',
            'Referrer'
        ];
    }

    public function map($result): array
    {
        $param = json_decode($result->request_param);
        $vehicle = json_decode($param->h_vehicle);

        if(!empty($vehicle)) {
            $make_model = implode(' ', [$vehicle->make . $vehicle->model]);
        }

        return [
            $result->updated_at,
            $result->vehicle_number,
            $make_model,
            $param->id_number,
            $param->postcode,
            $param->phone_number,
            $result->email_address,
            $result->remarks,
            $result->referrer
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_NUMBER,
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

        $event->sheet->getStyle('A1:G1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $event->sheet->getStyle('A1:G1')->applyFromArray($header_style);

        // Set Border To All Cells & Set Alignment
        $event->sheet->getStyle('A2:' . $event->sheet->getHighestColumn() . $event->sheet->getHighestRow())->applyFromArray($body_style);
    }
}
