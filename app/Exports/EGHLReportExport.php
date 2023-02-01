<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class EGHLReportExport implements FromView, WithColumnFormatting, WithEvents, ShouldAutoSize
{
    public $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
    * @return \Illuminate\Contracts\View\View
    */
    public function view(): View
    {
        return view('backend.reports.eghl', ['data' => $this->data]);
    }

    public function columnFormats(): array
    {
        return [
            'A' => '#,##0.00_-',
            'C' => NumberFormat::FORMAT_NUMBER
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $event->sheet->getDelegate()->getStyle('A2:A' . $event->sheet->getHighestRow())->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            }
        ];
    }
}
