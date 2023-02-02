<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class InsurerReportExport implements FromView, WithColumnFormatting, ShouldAutoSize
{
    public $data;

    public function __construct(array $data)
    {
        $this->data = $data;    
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View
    {
        return view('backend.reports.insurer', ['data' => $this->data]);
    }

    public function columnFormats(): array
    {
        return [
            'I' => '#,##0.00_-',
            'J' => '#,##0.00_-',
            'K' => '#,##0.00_-',
            'L' => '#,##0.00_-',
            'M' => '#,##0.00_-',
            'N' => '#,##0.00_-',
            'O' => '#,##0.00_-',
            'P' => '#,##0.00_-',
            'Q' => '#,##0.00_-',
            'R' => '#,##0.00_-',
            'S' => '#,##0.00_-',
            'T' => '#,##0.00_-',
            'U' => '#,##0.00_-',
            'V' => '#,##0.00_-',
            'W' => '#,##0.00_-',
            'X' => '#,##0.00_-',
            'Y' => '#,##0.00_-',
            'AA' => '#,##0.00_-',
            'AB' => '#,##0.00_-',
        ];
    }
}