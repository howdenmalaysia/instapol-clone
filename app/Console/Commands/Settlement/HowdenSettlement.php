<?php

namespace App\Console\Commands\Settlement;

use App\Exports\HowdenReportExport;
use App\Mail\HowdenSettlementMail;
use App\Models\CronJobs;
use App\Models\EGHLLog;
use App\Models\Motor\Insurance;
use App\Models\Motor\InsuranceMotor;
use App\Models\Motor\Product;
use App\Models\Promotion;
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
    const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'settlement:howden {start_date?} {end_date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'To Generate & Send Settlement Report to Howden Internal';

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
        Log::info("[Cron - Howden Internal Settlement] Start Generating Report.");

        // Determine if it's morning
        $isMorning = Carbon::now()->hour < 12;

        if ($isMorning) {
            $start_date = Carbon::now()->subDay()->startOfDay()->format(self::DATETIME_FORMAT);
            $end_date = Carbon::now()->subDay()->endOfDay()->format(self::DATETIME_FORMAT);
        } else {
            // Adjust the logic for non-morning time if needed
            $start_date = Carbon::now()->startOfDay()->format(self::DATETIME_FORMAT);
            $end_date = Carbon::now()->endOfDay()->format(self::DATETIME_FORMAT);
        }
        // $start_date = Carbon::now()->subDay()->startOfDay()->format(self::DATETIME_FORMAT);
        // $end_date = Carbon::now()->subDay()->endOfDay()->format(self::DATETIME_FORMAT);
        if(!empty($this->argument('start_date')) && !empty($this->argument('end_date'))) {
            $start_date = Carbon::parse($this->argument('start_date'))->startOfDay()->format(self::DATETIME_FORMAT);
            $end_date = Carbon::parse($this->argument('end_date'))->endOfDay()->format(self::DATETIME_FORMAT);
        }

        try {
            $records = Insurance::with([
                    'product',
                    'holder',
                    'promo',
                    'premium',
                    'address'
                ])
                ->where(function($query) use($start_date, $end_date) {
                    $query->whereBetween('created_at', [$start_date, $end_date])
                        ->orWhereBetween('updated_at', [$start_date, $end_date]);
                })
                ->whereNull('settlement_on')
                ->whereIn('insurance_status', [Insurance::STATUS_PAYMENT_ACCEPTED, Insurance::STATUS_POLICY_ISSUED])
                ->get()
                ->groupBy('product_id');

            if(empty($records)) {
                throw new Exception('No Eligible Records Found!');
            }

            $rows = $total_commission = $total_eservice_fee = $total_sst = $total_roadtax_premium = $total_discount = $total_payment_gateway_charges = $total_premium = $total_outstanding = 0;
            $row_data = $details = [];

            $records->each(function($insurances, $product_id) use(
                $start_date,
                &$rows,
                &$row_data,
                &$total_commission,
                &$total_eservice_fee,
                &$total_sst,
                &$total_roadtax_premium,
                &$total_discount,
                &$total_payment_gateway_charges,
                &$total_premium,
                &$details)
            {
                $insurer_net_transfer = 0;
                $product = Product::with(['insurance_company'])
                    ->findOrFail($product_id);

                $insurances->map(function($insurance) use(
                    $start_date,
                    $product,
                    &$rows,
                    &$row_data,
                    &$total_commission,
                    &$total_eservice_fee,
                    &$total_sst,
                    &$total_roadtax_premium,
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
                        $discount_amount = $insurance->promo->discount_amount;
                    }

                    $roadtax_premium = 0;
                    $physical = false;
                    if(!empty($insurance_motor->roadtax)) {
                        $roadtax_premium = floatval($insurance_motor->roadtax->roadtax_renewal_fee) +
                            floatval($insurance_motor->roadtax->myeg_fee) +
                            floatval($insurance_motor->roadtax->e_service_fee) +
                            floatval($insurance_motor->roadtax->service_tax);

                        $total_eservice_fee += $insurance_motor->roadtax->e_service_fee;
                        $total_sst += $insurance_motor->roadtax->service_tax;
                        //1.08 is 8% SST
                        $physical = $insurance_motor->roadtax->myeg_fee - formatNumber(2.75 * 1.08) > 0;

                        $delivery_address = formatAddress([
                            $insurance_motor->roadtax->recipient_address_one,
                            $insurance_motor->roadtax->recipient_address_two,
                            $insurance_motor->roadtax->recipient_city,
                            $insurance_motor->roadtax->recipient_postcode,
                            $insurance_motor->roadtax->recipient_state,
                        ]);
                    }

                    if(!empty($discount_amount) && $insurance->promo->promotion->discount_target === Promotion::DT_ROADTAX) {
                        $roadtax_premium -= $discount_amount;
                        $total_discount += $discount_amount;
                    }

                    $total_roadtax_premium += $roadtax_premium;

                    $eghl_log = EGHLLog::where('payment_id', 'LIKE', '%' . $insurance->insurance_code . '%')
                        ->where('txn_status', 0)
                        ->latest()
                        ->first();

                    $commission = $insurance->premium->gross_premium * 0.1;
                    $net_premium = $insurance->premium->gross_premium + $insurance->premium->service_tax_amount + $insurance->premium->stamp_duty - $commission;
                    $total_premium += $net_premium;
                    $insurer_net_transfer += $net_premium;

                    $gateway_charges = getGatewayCharges($insurance->amount, $eghl_log->service_id, $eghl_log->payment_method);
                    $total_payment_gateway_charges += $gateway_charges;
                    $total_commission += $commission;

                    $address = formatAddress([
                        $insurance->address->address_one,
                        $insurance->address->address_two,
                        $insurance->address->city,
                        $insurance->address->postcode,
                        $insurance->address->state,
                    ]);

                    if(array_key_exists($product->id, $row_data)) {
                        array_push($row_data[$product->id], [
                            $start_date,
                            $insurance->insurance_code,
                            $product->name,
                            $insurance->updated_at->format(self::DATETIME_FORMAT),
                            $insurance->inception_date,
                            $insurance->policy_number ?? $insurance->cover_note_number ?? $insurance->contract_number,
                            $insurance_motor->vehicle_number,
                            $insurance->holder->name,
                            $insurance->holder->id_number,
                            $insurance->holder->phone_code . $insurance->holder->phone_number,
                            $insurance->holder->email_address,
                            $address,
                            $insurance->premium->gross_premium,
                            $insurance->premium->service_tax_amount,
                            $insurance->premium->stamp_duty,
                            number_format($insurance->amount - $roadtax_premium, 2),
                            number_format($net_premium, 2),
                            $commission,
                            !empty($insurance->promo) && $insurance->promo->promotion->discount_target === Promotion::DT_TOTALPAYABLE ? $discount_amount : '',
                            !empty($insurance->promo) && $insurance->promo->promotion->discount_target === Promotion::DT_GROSS_PREMIUM ? $discount_amount : '',
                            !empty($insurance->promo) && $insurance->promo->promotion->discount_target === Promotion::DT_ROADTAX ? $discount_amount : '',
                            empty($insurance_motor->roadtax->roadtax_renewal_fee) ? '-' : ($physical ? 'Physical' : 'Digital'),
                            $delivery_address ?? '-',
                            $insurance_motor->roadtax->roadtax_renewal_fee ?? '',
                            $insurance_motor->roadtax->myeg_fee ?? '',
                            $insurance_motor->roadtax->e_service_fee ?? '',
                            $insurance_motor->roadtax->service_tax ?? '',
                            $roadtax_premium,
                            number_format($insurance->amount, 2),
                            $eghl_log->payment_method === 'DD' ? $gateway_charges : '',
                            $eghl_log->payment_method === 'CC' ? $gateway_charges : '',
                            $eghl_log->payment_method === 'WA' ? $gateway_charges : '',
                            'N/A',
                            number_format($net_premium, 2),
                            number_format($commission + $roadtax_premium - $gateway_charges, 2),
                            $insurance->referrer,
                            Str::afterLast($insurance->holder->email_address, '@'),
                            !empty($insurance->promo) ? $insurance->promo->promotion->code : ''
                        ]);
                    } else {
                        $row_data[$product->id][] = [
                            $start_date,
                            $insurance->insurance_code,
                            $product->name,
                            $insurance->updated_at->format(self::DATETIME_FORMAT),
                            $insurance->inception_date,
                            $insurance->policy_number ?? $insurance->cover_note_number ?? $insurance->contract_number,
                            $insurance_motor->vehicle_number,
                            $insurance->holder->name,
                            $insurance->holder->id_number,
                            $insurance->holder->phone_code . $insurance->holder->phone_number,
                            $insurance->holder->email_address,
                            $address,
                            $insurance->premium->gross_premium,
                            $insurance->premium->service_tax_amount,
                            $insurance->premium->stamp_duty,
                            number_format($insurance->amount - $roadtax_premium, 2),
                            number_format($net_premium, 2),
                            $commission,
                            !empty($insurance->promo) && $insurance->promo->promotion->discount_target === Promotion::DT_TOTALPAYABLE ? $discount_amount : '',
                            !empty($insurance->promo) && $insurance->promo->promotion->discount_target === Promotion::DT_GROSS_PREMIUM ? $discount_amount : '',
                            !empty($insurance->promo) && $insurance->promo->promotion->discount_target === Promotion::DT_ROADTAX ? $discount_amount : '',
                            empty($insurance_motor->roadtax->roadtax_renewal_fee) ? '-' : ($physical ? 'Physical' : 'Digital'),
                            $delivery_address ?? '-',
                            $insurance_motor->roadtax->roadtax_renewal_fee ?? '',
                            $insurance_motor->roadtax->myeg_fee ?? '',
                            $insurance_motor->roadtax->e_service_fee ?? '',
                            $insurance_motor->roadtax->service_tax ?? '',
                            $roadtax_premium,
                            number_format($insurance->amount, 2),
                            $eghl_log->payment_method === 'DD' ? $gateway_charges : '',
                            $eghl_log->payment_method === 'CC' ? $gateway_charges : '',
                            $eghl_log->payment_method === 'WA' ? $gateway_charges : '',
                            'N/A',
                            number_format($net_premium, 2),
                            number_format($commission + $roadtax_premium - $gateway_charges, 2),
                            $insurance->referrer,
                            Str::afterLast($insurance->holder->email_address, '@'),
                            !empty($insurance->promo) ? $insurance->promo->promotion->code : ''
                        ];
                    }

                    $rows++;
                });

                array_push($details, [
                    $product->insurance_company->name,
                    $insurances->count(),
                    number_format($insurer_net_transfer, 2)
                ]);
            });

            $start = Carbon::parse($start_date)->format(self::DATE_FORMAT);

            $filenames = [];
            foreach($row_data as $product_id => $values) {
                $product = Product::with(['insurance_company'])
                    ->findOrFail($product_id);

                $insurer_name = Str::snake(ucwords($product->insurance_company->name));
                $filename = "{$insurer_name}{$product->insurance_company->id}_settlement_{$start}.xlsx";
                array_push($filenames, $filename);
                Excel::store(new HowdenReportExport($values), $filename);
            }

            $data = [
                'start_date' => $start,
                'end_date' => Carbon::parse($end_date)->format(self::DATE_FORMAT),
                'total_commission' => $total_commission,
                'total_eservice_fee' => $total_eservice_fee,
                'total_sst' => $total_sst,
                'total_roadtax_premium' => $total_roadtax_premium,
                'total_discount' => $total_discount,
                'total_payment_gateway_charges' => $total_payment_gateway_charges,
                'net_transfer_amount_insurer' => $total_premium,
                'net_transfer_amount' => $total_commission + $total_roadtax_premium - $total_payment_gateway_charges,
                'total_outstanding' => $total_outstanding,
                'details' => $details
            ];

            Mail::to(config('setting.settlement.howden.email_to'))
                ->cc(config('setting.settlement.howden.email_cc'))
                ->bcc(config('setting.howden.it_dev_mail'))
                ->send(new HowdenSettlementMail($filenames, $data));

            Log::info("[Cron - Howden Internal Settlement] {$rows} records processed. [{$start_date} to {$end_date}]");

            CronJobs::updateOrCreate([
                'description' => 'Send Settlement Report to Insurers',
                'status' => CronJobs::STATUS_COMPLETED,
                'param' => json_encode([
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                ]),
            ]);

            $this->info("{$rows} records processed");
        } catch (Exception $ex) {
            CronJobs::updateOrCreate([
                'description' => 'Send Settlement Report to Insurers',
                'status' => CronJobs::STATUS_FAILED,
                'param' => json_encode([
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                ]),
                'error_message' => $ex->getMessage()
            ]);

            Log::error("[Cron - Howden Internal Settlement] An Error Encountered. [{$ex->getMessage()}] \n" . $ex);
        }
    }
}
