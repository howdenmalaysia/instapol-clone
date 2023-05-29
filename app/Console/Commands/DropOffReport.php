<?php

namespace App\Console\Commands;

use App\Exports\DropOffReport\DropOffReportExport;
use App\Mail\DropOffReportMail;
use App\Models\Motor\Insurance;
use App\Models\Motor\Quotation;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

class DropOffReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drop-off {start_date?} {end_date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'To generate daily drop-off report to instaPol Team.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Log::info("[Cron - Drop-Off Report] Start Generating Report.");

        try {
            $start_date = Carbon::now()->subDay()->startOfDay()->format('Y-m-d H:i:s');
            $end_date = Carbon::now()->subDay()->endOfDay()->format('Y-m-d H:i:s');
            if(!empty($this->argument('start_date'))) {
                $start_date = Carbon::parse($this->argument('start_date'))->startOfDay()->format('Y-m-d H:i:s');
            }

            if(!empty($this->argument('end_date'))) {
                $start_date = Carbon::parse($this->argument('end_date'))->startOfDay()->format('Y-m-d H:i:s');
            }

            Log::info("[Cron - Drop-Off Report] Handing Over to Exports.");
            $batch_name = Carbon::parse($start_date)->format('Y-m-d_H_i') . '_' . Carbon::parse($end_date)->format('H_i');
            $file = "{$batch_name}_drop_off_report.xlsx";
            $export = new DropOffReportExport($start_date, $end_date);
            $result = $export->store($file);

            if(!$result) {
                throw new Exception('Failed to Genearte Excel File.');
            }

            $range = Carbon::parse($start_date)->format('Y-m-d H:i') . '_' . Carbon::parse($end_date)->format('H:i');

            Mail::to(config('setting.howden.insta_admin'))
                ->cc(config('setting.howden.affinity_team_email'))
                ->bcc(config('setting.howden.it_dev_mail'))
                ->send(new DropOffReportMail($file, $range));

            Log::info("[Cron - Drop-Off Report] Drop-off Report Generated Successfully.");
        } catch (Exception $ex) {
            Log::error("[Cron - Drop-Off Report] An Error Encountered While Generating the Report. [{$ex->getMessage()}] \n" . $ex);
        }
    }
}
