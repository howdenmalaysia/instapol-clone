<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class InsurerReportExport implements FromView, WithColumnFormatting, ShouldAutoSize
{
    public $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('backend.reports.insurer', ['data' => $this->data]);
    }

    public function columnFormats(): array
    {
        return [
            'B' => 'yyyy-mm-dd hh:mm:ss',
            'C' => NumberFormat::FORMAT_DATE_YYYYMMDD,
            'G' => '#,##0.00_-',
            'H' => '#,##0.00_-',
            'I' => '#,##0.00_-',
            'J' => '#,##0.00_-',
            'K' => '#,##0.00_-',
            'L' => '#,##0.00_-',
        ];
    }
}
