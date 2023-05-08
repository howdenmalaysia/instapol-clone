<?php

namespace App\Console\Commands\Settlement;

use App\Exports\InsurerReportExport;
use App\Mail\InsurerSettlementMail;
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
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class InsurerSettlement extends Command
{
    const DATE_FORMAT = 'Y-m-d';
    const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'settlement:insurers {start_date?} {end_date?} {frequency?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'To Generate & Send Settlement Report to Insurers';

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
        Log::info("[Cron - Insurer Settlement] Start Generating Reports.");

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
            Log::error("[Cron - Insurer Settlement] Shouldn't run settlement today, {$day}.");
            return;
        }

        try {
            $records = Insurance::with([
                    'product',
                    'holder',
                    'promo',
                    'premium'
                ])
                ->where(function($query) use($start_date, $end_date) {
                    $query->whereBetween('created_at', [$start_date, $end_date])
                        ->orWhereBetween('updated_at', [$start_date, $end_date]);
                })
                ->whereIn('insurance_status', [Insurance::STATUS_PAYMENT_ACCEPTED, Insurance::STATUS_POLICY_ISSUED])
                ->get()
                ->groupBy('product_id');

            if(empty($records)) {
                throw new Exception('No Eligible Records Found!');
            }

            $rows = 0;

            $records->each(function($insurances, $product_id) use($start_date, &$rows) {
                $total_commission = $total_eservice_fee = $total_sst = $total_payment_gateway_charges = $total_premium = $total_outstanding = $insurer_net_transfer = 0;
                $row_data = [];

                $product = Product::with(['insurance_company'])
                    ->findOrFail($product_id);

                $insurances->map(function($insurance) use(
                    $product,
                    &$rows,
                    &$row_data,
                    &$total_commission,
                    &$total_eservice_fee,
                    &$total_sst,
                    &$total_discount,
                    &$total_payment_gateway_charges,
                    &$total_premium,
                    &$insurer_net_transfer)
                {
                    $insurance_motor = InsuranceMotor::with([
                            'roadtax'
                        ])
                        ->where('insurance_id', $insurance->id)
                        ->first();

                    $discount_amount = 0;
                    if(!empty($insurance->promo)) {
                        $discount_amount = $insurance->promo->discount_amoumt;
                        $total_discount += $discount_amount;
                    }

                    if(!empty($insurance_motor->roadtax)) {
                        $total_eservice_fee += $insurance_motor->roadtax->e_service_fee;
                    }

                    $eghl_log = EGHLLog::where('payment_id', 'LIKE', '%' . $insurance->code . '%')
                        ->where('txn_status', 0)
                        ->latest()
                        ->first();

                    if($eghl_log->service_id === 'CBI') {
                        $total_payment_gateway_charges += number_format($insurance->amount * 0.015, 2);
                    } else if($eghl_log->service_id === 'CBH') {
                        $total_payment_gateway_charges += number_format($insurance->amount * 0.018, 2);
                    }

                    $payable = $insurance->premium->gross_premium + $insurance->premium->service_tax_amount + $insurance->premium->stamp_duty;
                    $commission = $insurance->premium->gross_premium * 0.1;
                    $net_premium = $insurance->premium->gross_premium - $commission;
                    $total_transfer = $insurance->premium->service_tax_amount + $insurance->premium->stamp_duty + $net_premium;
                    $total_commission += $commission;
                    $total_sst += $insurance->premium->service_tax_amount;
                    $total_premium += $payable;
                    $insurer_net_transfer += $payable;

                    if(array_key_exists($product->id, $row_data)) {
                        array_push($row_data[$product->id], [
                            $insurance->id,
                            $insurance->created_at->format(self::DATETIME_FORMAT),
                            $insurance->inception_date,
                            $insurance->policy_number ?? $insurance->cover_note_number ?? $insurance->contract_number,
                            $insurance_motor->vehicle_number,
                            $insurance->holder->name,
                            $insurance->premium->gross_premium,
                            $insurance->premium->service_tax_amount,
                            $insurance->premium->stamp_duty,
                            number_format($payable, 2),
                            number_format($net_premium, 2),
                            $total_transfer
                        ]);
                    } else {
                        $row_data[$product->id][] = [
                            $insurance->id,
                            $insurance->created_at->format(self::DATETIME_FORMAT),
                            $insurance->inception_date,
                            $insurance->policy_number ?? $insurance->cover_note_number ?? $insurance->contract_number,
                            $insurance_motor->vehicle_number,
                            $insurance->holder->name,
                            $insurance->premium->gross_premium,
                            $insurance->premium->service_tax_amount,
                            $insurance->premium->stamp_duty,
                            number_format($payable, 2),
                            number_format($net_premium, 2),
                            $total_transfer
                        ];
                    }

                    $rows++;
                });

                $start_date = Carbon::parse($start_date)->format(self::DATE_FORMAT);

                $filenames = [];
                foreach($row_data as $product_id => $values) {
                    $product = Product::with(['insurance_company'])
                        ->findOrFail($product_id);

                    $insurer_name = Str::snake(ucwords($product->insurance_company->name));

                    $filename = "{$insurer_name}{$product->insurance_company->id}_settlement_{$start_date}.xlsx";
                    array_push($filenames, $filename);
                    Excel::store(new InsurerReportExport($values), $filename);
                }

                $data = [
                    'insurer_name' => $product->insurance_company->name,
                    'start_date' => $start_date,
                    'total_commission' => $total_commission,
                    'total_eservice_fee' => $total_eservice_fee,
                    'total_sst' => $total_sst,
                    'total_discount' => $total_discount,
                    'total_payment_gateway_charges' => $total_payment_gateway_charges,
                    'net_transfer_amount_insurer' => $total_premium - $total_commission,
                    'net_transfer_amount' => $total_commission,
                    'total_outstanding' => $total_outstanding,
                    'details' => [[
                        $product->insurance_company->name,
                        $insurances->count(),
                        number_format($insurer_net_transfer, 2)
                    ]]
                ];

                Log::info("[Cron - Insurer Settlement] Sending Settlement Report to {$product->insurance_company->name} [{$product->insurance_company->email_to},{$product->insurance_company->email_cc}]");

                Mail::to(explode(',', $product->insurance_company->email_to))
                    ->cc(array_merge(explode(',', $product->insurance_company->email_cc), config('setting.howden.affinity_team_email')))
                    ->bcc(config('setting.howden.it_dev_mail'))
                    ->send(new InsurerSettlementMail($filenames, $data));

                Log::info("[Cron - Insurer Settlement] Report to {$product->insurance_company->name} sent successfully.");
            });

            Log::info("[Cron - Insurer Settlement] {$rows} records processed. [{$start_date} to {$end_date}]");

            CronJobs::create([
                'description' => 'Send Settlement Report to Insurers',
                'param' => json_encode([
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'frequency' => $this->argument('frequency')
                ]),
                'status' => CronJobs::STATUS_COMPLETED
            ]);

            $this->info("{$rows} records processed");
        } catch (Exception $ex) {
            CronJobs::updateOrCreate([
                'description' => 'Send Settlement Report to Insurers',
                'param' => json_encode([
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'frequency' => $this->argument('frequency')
                ]),
                'status' => CronJobs::STATUS_FAILED
            ]);

            Log::error("[Cron - Insurer Settlement] An Error Encountered. [{$ex->getMessage()}] \n" . $ex);
        }

    }
}
