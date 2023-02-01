<?php

namespace App\Console\Commands;

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

class HowdenSettlement extends Command
{
    const DATE_FORMAT = 'Y-m-d';

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
        $start_date = $end_date = Carbon::now()->format(self::DATE_FORMAT);
        if($this->argument('start_date')) {
            $start_date = Carbon::parse($this->argument('start_date'))->format(self::DATE_FORMAT);
        }

        if($this->argument('end_date')) {
            $end_date = Carbon::parse($this->argument('end_date'))->format(self::DATE_FORMAT);
        }

        try {
            $records = Insurance::with([
                    'product',
                    'holder',
                    'promo',
                    'premium'
                ])
                ->whereBetween('updated_at', [$start_date, $end_date])
                ->whereNull('settlement_on')
                ->where('insurance_status', Insurance::STATUS_PAYMENT_ACCEPTED)
                ->get()
                ->groupBy('product_id');
    
            if(empty($records)) {
                CronJobs::create([
                    'description' => 'Send Settlement Report to Insurers',
                    'param' => json_encode([
                        'start_date' => $start_date,
                        'end_date' => $end_date
                    ]),
                    'status' => CronJobs::STATUS_FAILED,
                    'error_message' => 'No Eligible Records Found!'
                ]);
        
            }
    
            $rows = $total_commission = $total_eservice_fee = $total_sst = $total_payment_gateway_charges =
            $total_premium = $net_transfer_amount_insurer = $net_tranfer_amount = $total_outstanding = 0;
            $row_data = $details = [];

            $records->each(function($insurances, $product_id) use(
                &$rows,
                &$row_data,
                $start_date,
                &$total_commission,
                &$total_eservice_fee,
                &$total_sst,
                &$total_discount,
                &$total_payment_gateway_charges,
                &$total_premium)
            {
                $insurer_net_transfer = 0;
                $product = Product::with(['insurance_company'])
                    ->findOrFail($product_id);
    
                $insurances->map(function($insurance) use(
                    $product,
                    &$rows,
                    &$row_data,
                    $start_date,
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
                    $discount_target = '';
                    if(!empty($insurance->promo)) {
                        $discount_amount = $insurance->promo->discount_amoumt;
                        $total_discount += $discount_amount;
                    }
    
                    $roadtax_premium = 0;
                    if(!empty($insurance_motor->roadtax)) {
                        $roadtax_premium = floatval($insurance_motor->roadtax->roadtax_renewal_fee) +
                            floatval($insurance_motor->roadtax->myeg_fee) +
                            floatval($insurance_motor->roadtax->e_service_fee) +
                            floatval($insurance_motor->roadtax->service_tax);

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

                    $payable = $insurance->amount - $roadtax_premium;
                    $total_commission += $payable * 0.1;
                    $total_sst += $insurance->premium->service_tax_amount;
                    $total_premium += $payable;
                    $insurer_net_transfer += $payable;

                    if(array_key_exists($product->id, $row_data)) {
                        array_push($row_data[$product->id], [
                            $start_date,
                            $insurance->id,
                            $product->insurance_company->name,
                            $insurance->updated_at->format(self::DATE_FORMAT),
                            $insurance->inception_date,
                            $insurance->policy_number,
                            $insurance_motor->vehicle_number,
                            $insurance->holder->name,
                            $insurance->premium->gross_premium,
                            $insurance->premium->service_tax_amount,
                            $insurance->premium->stamp_duty,
                            number_format($insurance->amount - floatval($roadtax_premium), 2),
                            $insurance->premium->net_premium,
                            $insurance->amount * 0.1,
                            $discount_target === 'total_payable' ? $discount_amount : '',
                            $discount_target === 'gross_premium' ? $discount_amount : '',
                            $discount_target === 'roadtax' ? $discount_amount : '',
                            $insurance_motor->roadtax->roadtax_renewal_fee ?? '',
                            $insurance_motor->roadtax->myeg_fee ?? '',
                            '',
                            $insurance_motor->roadtax->e_service_fee ?? '',
                            $insurance_motor->roadtax->service_tax ?? '',
                            $insurance->amount,
                            $eghl_log->service_id === 'CBI' ? number_format($insurance->amount * 0.015, 2) : '',
                            $eghl_log->service_id === 'CBH' ? number_format($insurance->amount * 0.018, 2) : '',
                            'N/A',
                            ($insurance->amount - $roadtax_premium) * 0.9,
                            ($insurance->amount - $roadtax_premium) * 0.1 + $roadtax_premium,
                            $insurance->referrer,
                            Str::afterLast($insurance->holder->email_address, '@'),
                            !empty($insurance->promo) ? $insurance->promo->promo->code : ''
                        ]);
                    } else {
                        $row_data[$product->id][] = [
                            $start_date,
                            $insurance->id,
                            $product->insurance_company->name,
                            $insurance->updated_at->format(self::DATE_FORMAT),
                            $insurance->inception_date,
                            $insurance->policy_number,
                            $insurance_motor->vehicle_number,
                            $insurance->holder->name,
                            $insurance->premium->gross_premium,
                            $insurance->premium->service_tax_amount,
                            $insurance->premium->stamp_duty,
                            number_format($insurance->amount - floatval($roadtax_premium), 2),
                            $insurance->premium->net_premium,
                            $insurance->amount * 0.1,
                            $discount_target === 'total_payable' ? $discount_amount : '',
                            $discount_target === 'gross_premium' ? $discount_amount : '',
                            $discount_target === 'roadtax' ? $discount_amount : '',
                            $insurance_motor->roadtax->roadtax_renewal_fee ?? '',
                            $insurance_motor->roadtax->myeg_fee ?? '',
                            '',
                            $insurance_motor->roadtax->e_service_fee ?? '',
                            $insurance_motor->roadtax->service_tax ?? '',
                            $insurance->amount,
                            $eghl_log->service_id === 'CBI' ? number_format($insurance->amount * 0.015, 2) : '',
                            $eghl_log->service_id === 'CBH' ? number_format($insurance->amount * 0.018, 2) : '',
                            'N/A',
                            ($insurance->amount - $roadtax_premium) * 0.9,
                            ($insurance->amount - $roadtax_premium) * 0.1 + $roadtax_premium,
                            $insurance->referrer,
                            Str::afterLast($insurance->holder->email_address, '@'),
                            !empty($insurance->promo) ? $insurance->promo->promo->code : ''
                        ];
                    }
    
                    $rows++;
                });

                array_push($details, [
                    $product->insurance_company->name,
                    $insurances->count(),
                    $insurer_net_transfer
                ]);
            });

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
                'start_date' => $start_date,
                'total_commission' => $total_commission,
                'total_eservice_fee' => $total_eservice_fee,
                'total_sst' => $total_sst,
                'total_discount' => $total_discount,
                'total_payment_gateway_charges' => $total_payment_gateway_charges,
                'net_transfer_amount_insurer' => $total_premium - $total_commission,
                'net_transfer_amount' => $total_commission,
                'total_outstanding' => $total_outstanding,
                'details_per_insurer' => $details
            ];

            Mail::to(config('setting.settlement.insurer'))
                ->bcc(config('setting.howden.it_dev_mail'))
                ->send(new InsurerSettlementMail($filenames, $data));

            Log::info("[Settlement - Howden Internal] {$rows} records processed.");

            $this->info("{$rows} records processed");
        } catch (Exception $ex) {
            CronJobs::updateOrCreate([
                'description' => 'Send Settlement Report to Insurers',
                'param' => (object) [
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'frequency' => $this->argument('frequency')
                ]
            ]);

            Log::error("[Settlement - Howden Internal] An Error Encountered. {$ex->getMessage()}");
        }

    }
}
