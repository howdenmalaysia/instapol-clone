<?php

namespace App\Console\Commands;

use AkkiIo\LaravelGoogleAnalytics\Facades\LaravelGoogleAnalytics as GA;
use AkkiIo\LaravelGoogleAnalytics\LaravelGoogleAnalytics;
use AkkiIo\LaravelGoogleAnalytics\Period;
use App\Mail\TrendReportMail;
use App\Models\CronJobs;
use App\Models\Motor\Insurance;
use App\Models\Motor\Quotation;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon as SupportCarbon;


class TrendReport extends Command
{
    public Collection $trend;
    public Collection $referral_links;
    public string $date_format = 'd M';
    public string $date_time_format = 'Y-m-d H:i:s';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trend';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'To generate the weekly trend report to instaPol team.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->trend = collect([]);
        $this->referral_links = collect([]);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        /**
         * Event Naming Format: <Action>_<ItemGroupName>_<Item>_<Negative>
         * Action:
         *  l - Landing
         *  c - Click
         *  s - Select
         *
         * Item Group Name:
         *  motor - Motor Pages,
         *  cmp - Compare
         *  ao - Add Ons
         *
         * Item:
         *  la - Landing
         *  vd - Vehicle Details
         *  cm - Compare
         *  add - Add Ons
         *  rdt - Road Tax
         *  ph - Policy Holder
         *  su - Summary
         *  suc - Payment Success
         *
         * Negative:
         *  n - De-selected
         */

        $events = [
            'l_motor_la',
            'l_motor_vd',
            'l_motor_cm',
            'c_cmp_buy',
            'c_cmp_cmp',
            'l_motor_ao',
            's_ao_add',
            's_ao_rdt',
            's_ao_rdt_n',
            'motor_use_promo',
            'l_motor_ph',
            'fill_form',
            'l_motor_su',
            'motor_pay',
            'l_motor_suc'
        ];

        try {
            // Get Visitors
            $analytics = new LaravelGoogleAnalytics();
            $visitors = $analytics->getTotalUsersByDate(Period::create(SupportCarbon::today()->subMonths(2), SupportCarbon::today()->subWeek()->endOfWeek()));

            // Get Referral Link Visitors
            $landing_pages = GA::dateRanges(Period::create(SupportCarbon::today()->subMonths(2), SupportCarbon::today()->subWeek()->endOfWeek()))
                ->dimensions('landingPagePlusQueryString', 'date')
                ->metrics('screenPageViews')
                ->get();

            $date_ranges = $this->getWeekRange();
            $range_start = explode(' - ', array_values($date_ranges)[0])[0];
            $range_end = explode(' - ', array_values(array_reverse($date_ranges))[0])[1];

            $leads = Quotation::with('insurance_motor')
                ->where('updated_at', '>=', Carbon::parse($range_start)->format($this->date_time_format))
                ->where('updated_at', '<=', Carbon::parse($range_end)->endOfDay()->format($this->date_time_format))
                ->where('active', Quotation::ACTIVE)
                ->distinct('email_address', 'vehicle_number')
                ->get();

            $insurance = Insurance::whereIn('insurance_status', [Insurance::STATUS_PAYMENT_ACCEPTED, Insurance::STATUS_POLICY_ISSUED, Insurance::STATUS_POLICY_FAILURE])
                ->where('updated_at', '>=', Carbon::parse($range_start)->format($this->date_time_format))
                ->where('updated_at', '<=', Carbon::parse($range_end)->endOfDay()->format($this->date_time_format))
                ->get();

            foreach($date_ranges as $week => $range) {
                $this->trend[$week] = (object) [
                    'range' => $range,
                    'visitors' => 0,
                    'leads' => 0,
                    'eligible_leads' => 0,
                    'sales' => 0,
                    'lead_generation_rate' => 0,
                    'conversion_rate' => 0
                ];
            }

            // Compile Data
            foreach($visitors as $daily) {
                $access_date = Carbon::parse($daily['date']);

                // Lead of The Day
                $lotd = $this->getOneDayRecord($leads, $access_date);

                // Insurance of The Day
                $iotd = $this->getOneDayRecord($insurance, $access_date);

                $this->trend[$access_date->weekOfYear]->visitors += $daily['totalUsers'];
                $this->trend[$access_date->weekOfYear]->leads += $lotd->count();
                $this->trend[$access_date->weekOfYear]->eligible_leads += $lotd->where('compare_page', 1)->count();
                $this->trend[$access_date->weekOfYear]->sales += $iotd->count();
            }

            // Calculate Rates
            $this->trend->map(function($t) {
                if($t->visitors > 0) {
                    $t->lead_generation_rate = round($t->leads / $t->visitors);
                }

                if($t->eligible_leads > 0) {
                    $t->conversion_rate = round($t->sales / $t->eligible_leads);
                }

                return $t;
            });

            // Compile Landing Pages
            $exclusion = [
                route('frontend.about-us', [], false),
                route('frontend.claims', [], false),
                route('frontend.cookie', [], false),
                route('frontend.privacy', [], false),
                route('frontend.refund', [], false),
                route('frontend.term-of-use', [], false),
                route('motor.vehicle-details', [], false),
                route('motor.compare', [], false),
                route('motor.add-ons', [], false),
                route('motor.policy-holder', [], false),
                route('motor.payment-summary', [], false),
                route('motor.payment-success', [], false),
                route('motor.payment-failed', [], false),
            ];

            foreach($landing_pages->table as $landing) {
                if(in_array($landing['landingPagePlusQueryString'], $exclusion)) {
                    continue;
                }

                if($landing['landingPagePlusQueryString'] === '/') {
                    $page_name = 'DIRECT (instapol.my)';
                } else if(strpos($landing['landingPagePlusQueryString'], '/motor') !== false) {
                    $page_name = 'Motor Page (instapol.my/motor)';
                } else if($landing['landingPagePlusQueryString'] === 'miea-insure.instapol.my') {
                    $page_name = 'MIEA Insure Page (miea-insure.instapol.my)';
                } else if($landing['landingPagePlusQueryString'] === '/bar-council') {
                    $page_name = 'Bar Council';
                } else if($landing['landingPagePlusQueryString'] === '/covid-19') {
                    $page_name = 'COVID-19';
                } else if($landing['landingPagePlusQueryString'] === '/miea') {
                    $page_name = 'MIEA (PI)';
                } else if(strpos($landing['landingPagePlusQueryString'], '/?r=') !== false) {
                    $page_name = str_replace('/?r=', '', $landing['landingPagePlusQueryString']);
                } else if(strpos($landing['landingPagePlusQueryString'], '/?fbclid=') !== false) {
                    $page_name = 'Facebook Clicks';
                } else {
                    $page_name = $landing['landingPagePlusQueryString'];
                }

                $access_date = Carbon::parse($landing['date']);

                if(!empty($this->referral_links[$page_name]->{$access_date->weekOfYear})) {
                    $this->referral_links[$page_name]->{$access_date->weekOfYear} += $landing['screenPageViews'];
                } else {
                    if(!empty($this->referral_links[$page_name])) {
                        $this->referral_links[$page_name]->{$access_date->weekOfYear} = $landing['screenPageViews'];
                    } else {
                        $this->referral_links[$page_name] = (object) [
                            $access_date->weekOfYear => $landing['screenPageViews']
                        ];
                    }
                }
            }

            $full_range = $range_start . ' - ' . $range_end;

            $cc_list = [
                config('setting.howden.it_dev_mail'),
                config('setting.howden.contact_list.jeffery_chan'),
                config('setting.howden.contact_list.phoebie_wong'),
                config('setting.howden.contact_list.cheng_lai_fah')
            ];

            // Send Report
            Mail::to('davidchoy98@gmail.com')
                // ->cc($cc_list)
                ->send(new TrendReportMail($full_range, $this->trend, $this->referral_links, $date_ranges));

            // Create Logs in DB
            CronJobs::create([
                'description' => 'Send Trend Report to instaPol Team.',
                'status' => CronJobs::STATUS_COMPLETED,
                'param' => json_encode([
                    'property_id' => config('laravel-google-analytics.property_id'),
                    'date_range' => $full_range
                ])
            ]);

            Log::info("[Trend Report] Trend Report for {$full_range} was Generated Successfully.");
        } catch(Exception $ex) {
            // Log Error in Server Logs
            Log::error("[Trend Report] Something Went Wrong While Compiling Trend Report. [{$ex->getMessage()}] \n" . $ex);

            // Create Logs in DB
            CronJobs::create([
                'description' => 'Send Trend Report to instaPol Team.',
                'status' => CronJobs::STATUS_FAILED,
                'param' => json_encode([
                    'property_id' => config('laravel-google-analytics.property_id')
                ]),
                'error_message' => $ex->getMessage()
            ]);
        }

        return 0;
    }

    private function getWeekRange(int $weeks = 9)
    {
        $today = Carbon::today();
        $date = $today->copy()->subWeeks($weeks)->startOfWeek()->startOfDay();
        $end_date = $today->copy()->subWeek()->endOfWeek()->startOfDay();

        $dates = [];

        for($i = $date->weekOfYear; $date->lt($end_date); $i++) {
            $start_date = $date->copy();

            while($date->dayOfWeek !== Carbon::SUNDAY && $date->lt($end_date)) {
                $date->addDay();
            }

            $dates[$i] = $start_date->format($this->date_format) . ' - ' . $date->format($this->date_format);
            $date->addDay();
        }

        return $dates;
    }

    private function getOneDayRecord($data, Carbon $date, string $field = 'updated_at')
    {
        return $data->where($field, '>=', $date->format($this->date_time_format))
            ->where($field, '<=', $date->endOfDay()->format($this->date_time_format));
    }
}
