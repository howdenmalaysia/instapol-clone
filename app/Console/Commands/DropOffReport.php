<?php

namespace App\Console\Commands;

use App\Models\Motor\Quotation;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DropOffReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drop-off {start_hour?} {end_hour?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'To generate hourly drop-off report to instaPol Team.';

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
            $date = Carbon::now()->format('Y-m-d');
            $date = '2023-05-09';
            $start_time = $end_time = implode(' ', [$date, Carbon::now()->startOfHour()->format('H:i')]);
            if(!empty($this->argument('start_hour')) && !empty($this->argument('end_hour'))) {
                $start_time = $date . ' ' . $this->argument('start_hour') . ':00:00';
                $end_time = $date . ' ' . $this->argument('end_hour') . ':59:59';
            }

            if(!empty($this->argument('date'))) {
                $date = Carbon::parse($this->argument('date'))->format('Y-m-d');
            }

            Log::info("[Cron - Drop-Off Report] Handing Over to Exports.");
            $batch_name = Carbon::parse($start_time)->format('Y-m-d_H_i') . '_' . Carbon::parse($end_time)->format('H_i');
            $file = "{$batch_name}_drop_off_report.xlsx";
            $export = new DropOffReportExport($start_time, $end_time);
            $result = $export->store($file);

            if(!$result) {
                throw new Exception('Failed to Genearte Excel File.');
            }

            $range = Carbon::parse($start_time)->format('Y-m-d H:i') . '_' . Carbon::parse($end_time)->format('H:i');

            Mail::to(config('setting.howden.affinity_team_email'))
                ->bcc(config('setting.howden.it_dev_mail'))
                ->send(new DropOffReportMail($file, $range));

            Log::info("[Cron - Drop-Off Report] Drop-off Report Generated Successfully.");
        } catch (Exception $ex) {
            Log::error("[Cron - Drop-Off Report] An Error Encountered While Generating the Report. [{$ex->getMessage()}] \n" . $ex);
        }
    }
}
