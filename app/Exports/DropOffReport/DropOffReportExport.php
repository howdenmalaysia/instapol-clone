<?php

namespace App\Exports\DropOffReport;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DropOffReportExport implements WithMultipleSheets
{
    use Exportable;

    protected $start_time;
    protected $end_time;

    public function __construct(string $start_time, string $end_time)
    {
        $this->start_time = $start_time;
        $this->end_time = $end_time;
    }

    public function sheets(): array
    {
        return [
            'Vehicle Details Page' => new VehicleDetails($this->start_time, $this->end_time),
            'Compare / Add Ons / Policy Holder Page' => new Compare($this->start_time, $this->end_time),
            'Payment Summary Page' => new PaymentSummary($this->start_time, $this->end_time)
        ];
    }
}
