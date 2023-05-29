<?php

namespace App\Exports\DropOffReport;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DropOffReportExport implements WithMultipleSheets
{
    use Exportable;

    protected $start;
    protected $end;

    public function __construct(string $start, string $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    public function sheets(): array
    {
        return [
            'Vehicle Details Page' => new VehicleDetails($this->start, $this->end),
            'Compare / Add Ons / Policy Holder Page' => new Compare($this->start, $this->end),
            'Payment Summary Page' => new PaymentSummary($this->start, $this->end)
        ];
    }
}
