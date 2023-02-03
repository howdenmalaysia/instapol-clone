<?php

namespace App\Console\Commands\Settlement;

use App\Exports\EGHLReportExport;
use App\Mail\EGHLSettlementMail;
use App\Models\CronJobs;
use App\Models\EGHLLog;
use App\Models\Motor\Insurance;
use App\Models\Motor\InsuranceMotor;
use App\Models\Motor\Product;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

class EGHLSettlement extends Command
{
    const DATE_FORMAT = 'Y-m-d';
    const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'settlement:eghl {start_date?} {end_date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'To Generate & Send Settlement Report to eGHL';

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
        Log::info("[Cron - eGHL Settlement] Start Generating Report.");

        $start_date = $end_date = Carbon::now()->format(self::DATETIME_FORMAT);
        if(!empty($this->argument('start_date')) && !empty($this->argument('end_date'))) {
            $start_date = Carbon::parse($this->argument('start_date'))->startOfDay()->format(self::DATETIME_FORMAT);
            $end_date = Carbon::parse($this->argument('end_date'))->endOfDay()->format(self::DATETIME_FORMAT);
        } else if(Carbon::now()->englishDayOfWeek === 'Wednesday') {
            $start_date = Carbon::parse('last Friday')->startOfDay()->format(self::DATETIME_FORMAT); // Last Friday 00:00:00
            $end_date = Carbon::now()->subDay()->endOfDay()->format(self::DATETIME_FORMAT); // Yesterday 23:59:59
        } else if (Carbon::now()->englishDayOfWeek === 'Friday') {
            $start_date = Carbon::parse('last Wednesday')->startOfDay()->format(self::DATETIME_FORMAT); // Last Wednesday 00:00:00
            $end_date = Carbon::now()->subDay()->endOfDay()->format(self::DATETIME_FORMAT); // Yesterday 23:59:59
        } else {
            // Throw Error
            $day = Carbon::now()->englishDayOfWeek;
            Log::error("[Cron - eGHL Settlement] Shouldn't run settlement today, {$day}.");
            return;
        }

        try {
            $records = Insurance::with(['product'])
                ->whereBetween('updated_at', [$start_date, $end_date])
                ->whereNull('settlement_on')
                ->where('insurance_status', Insurance::STATUS_PAYMENT_ACCEPTED)
                ->get();
    
            if(empty($records)) {
                $message = 'No Eligible Records Found!';

                Log::error("[Cron - eGHL Settlement] {$message}");

                CronJobs::create([
                    'description' => 'Send Settlement Report to eGHL',
                    'param' => json_encode([
                        'start_date' => $start_date,
                        'end_date' => $end_date
                    ]),
                    'status' => CronJobs::STATUS_FAILED,
                    'error_message' => $message
                ]);
        
                return;
            }
    
            $rows = $total_roadtax = $total_gateway_charges = 0;
            $total_amount = $row_data = [];
            $records->map(function($insurance) use(&$rows, &$total_amount, &$total_roadtax, &$total_gateway_charges) {
                // Roadtax Amount
                $insurance_motor = InsuranceMotor::with([
                        'roadtax'
                    ])
                    ->where('insurance_id', $insurance->id)
                    ->first();

                $roadtax_premium = 0;
                if(!empty($insurance_motor->roadtax)) {
                    $roadtax_premium = floatval($insurance_motor->roadtax->roadtax_renewal_fee) +
                        floatval($insurance_motor->roadtax->myeg_fee) +
                        floatval($insurance_motor->roadtax->e_service_fee) +
                        floatval($insurance_motor->roadtax->service_tax);
                }

                $total_roadtax += $roadtax_premium;

                // Total Payable
                if(array_key_exists($insurance->product_id, $total_amount)) {
                    $total_amount[$insurance->product_id] += floatval($insurance->amount) - $roadtax_premium;
                } else {
                    $total_amount[$insurance->product_id] = floatval($insurance->amount) - $roadtax_premium;
                }

                // Payment Gateway Charges
                $eghl_log = EGHLLog::where('payment_id', 'LIKE', '%' . $insurance->code . '%')
                    ->where('txn_status', 0)
                    ->latest()
                    ->first();
                
                $total_gateway_charges += getGatewayCharges($insurance->amount, $eghl_log->service_id, $eghl_log->payment_method);
    
                $rows++;
            });
            
            $product_ids = array_keys($total_amount);
            $total_commissions = 0;
    
            foreach($product_ids as $product_id) {
                $product = Product::with(['insurance_company'])
                    ->find($product_id);
    
                $email_cc = $product->insurance_company->email_cc;
                if(empty($email_cc)) {
                    $email_cc = implode(',', config('setting.howden.affinity_team_email'));
                } else {
                    $email_cc = implode(',', [$email_cc, implode(',', config('setting.howden.affinity_team_email'))]);
                }
    
                $total_commissions += $total_amount[$product_id] * 0.1;
                
                array_push($row_data, [
                    $total_amount[$product_id] * 0.9,
                    $product->insurance_company->bank_code,
                    $product->insurance_company->bank_account_no,
                    $start_date,
                    $product->insurance_company->name,
                    $product->insurance_company->email_to,
                    $email_cc,
                    'N/A'
                ]);
            }

            $start_date = Carbon::parse($start_date)->format(self::DATE_FORMAT);
    
            // Howden's Comms
            array_push($row_data, [
                $total_commissions + $total_roadtax + $total_gateway_charges,
                config('setting.settlement.howden.bank_code'),
                config('setting.settlement.howden.bank_account_no'),
                $start_date,
                $start_date,
                config('setting.settlement.howden.email_to'),
                config('setting.settlement.howden.email_cc'),
                'N/A'
            ]);
    
            $filename = "eghl_settlement_{$start_date}.xlsx";
            Excel::store(new EGHLReportExport($row_data), $filename);
    
            Mail::to(config('setting.settlement.eghl'))
                ->bcc(config('setting.howden.it_dev_mail'))
                ->send(new EGHLSettlementMail($filename, $start_date));
    
            CronJobs::create([
                'description' => 'Send Settlement Report to eGHL',
                'param' => json_encode([
                    'start_date' => $start_date,
                    'end_date' => $end_date
                ]),
                'status' => CronJobs::STATUS_COMPLETED
            ]);
    
            Insurance::whereIn('id', $records->pluck('id'))
                ->update([
                    'settlement_on' => Carbon::now()->format(self::DATETIME_FORMAT)
                ]);
            
            Log::info("[Cron - eGHL Settlement] {$rows} records processed. [{$start_date} to {$end_date}]");
            $this->info("{$rows} records processed.");
        } catch (Exception $ex) {
            Log::error("[Cron - eGHL Settlement] An Error Encountered. {$ex->getMessage()}");
            CronJobs::create([
                'description' => 'Send Settlement Report to eGHL',
                'param' => json_encode([
                    'start_date' => $start_date,
                    'end_date' => $end_date
                ]),
                'status' => CronJobs::STATUS_FAILED,
                'error_message' => $ex->getMessage()
            ]);
        }

    }
}
