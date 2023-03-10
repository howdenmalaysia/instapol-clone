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

        $start_time = $end_time = Carbon::now()->format('HH');
        if(!empty($this->argument('start_hour')) && !empty($this->argument('end_hour'))) {
            $start_time = $this->argument('start_hour') . ':00:00';
            $end_time = $this->argument('end_hour') . ':59:59';
        }

        // Drop Off Pages
        $landing = $vehicle_details = $compare = $add_ons = $policy_holder = $summary = [];

        $quotations = Quotation::where('created_at', '>=', $start_time)
            ->where('created_at', '<=', $end_time)
            ->get();

        // i. Drop off at Vehicle Details Page
        $quotations->map(function($quote) use(&$vehicle_details) {
            if($quote->compare_page === 0) {
                array_push($vehicle_details, $quote);
            }
        });

        
    }
}
