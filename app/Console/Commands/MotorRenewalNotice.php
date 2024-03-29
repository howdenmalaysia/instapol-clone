<?php

namespace App\Console\Commands;

use App\Mail\MotorRenewalNotice as RenewalNoticeMail;
use App\Models\CronJobs;
use App\Models\Motor\Insurance;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MotorRenewalNotice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'renewal:motor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'To send the renewal notice to the customer starting from T+4, T+2, T+1, Tweeks basis.';

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
        $first = Carbon::now()->addMonth()->format('Y-m-d');
        $second = Carbon::now()->addWeeks(2)->format('Y-m-d');
        $third = Carbon::now()->addWeek()->format('Y-m-d');
        $rows = 0;

        $log = CronJobs::create([
            'description' => 'Send Motor Renewal Notice',
            'param' => json_encode([
                'first' => $first,
                'second' => $second,
                'third' => $third
            ])
        ]);

        try {
            $insurance = Insurance::with(['holder', 'motor', 'address'])
                ->where(function($query) use($first, $second, $third) {
                    $query->where('expiry_date', $first);
                    $query->orWhere('expiry_date', $second);
                    $query->orWhere('expiry_date', $third);
                })
                ->get();

            if(count($insurance) > 0) {
                Log::info("[API/1st] Renewal Request: " . json_encode($insurance));
                $insurance->map(function($_ins) use($rows) {
                    // Generate query strings
                    Log::info("[API/2nd] Renewal Request: " . json_encode($_ins));
                    $details = 'vehicle_no=' . $_ins->motor->vehicle_number . '&postcode=' . $_ins->address->postcode .
                    '&email=' . $_ins->holder->email_address . '&phone_no=0' . $_ins->holder->phone_number . '&id_number=' . $_ins->holder->id_number .
                    '&id_type=' . $_ins->holder->id_type_id;

                    $tag = '';
                    $iv = strtotime('now');

                    $query_param = '?p=' . base64_encode(openssl_encrypt($details, 'aes-256-gcm', 'Fr0mR3n3w@lN0TiC3', 0, $iv, $tag)) . '&t=' . base64_encode($iv . '::' . $tag);

                    $data = [
                        'name'  => $_ins->holder->name ?? 'Sir / Madam',
                        'vehicle_number' => $_ins->motor->vehicle_number,
                        'email_address' => $_ins->holder->email_address,
                        'expiry_date' => $_ins->expiry_date,
                        'url' => route('motor.index') . $query_param
                    ];

                    Mail::to($_ins->holder->email_address)
                        ->cc(array_merge(config('setting.howden.affinity_team_email'), config('setting.howden.email_cc_list')))
                        ->bcc(config('setting.howden.it_dev_mail'))
                        ->send(new RenewalNoticeMail($data));

                    $rows++;
                });

                $_log = json_decode($log->param);
                $_log->message = "{$rows} insurance records processed.";

                CronJobs::where('id', $log->id)
                        ->update([
                            'status' => CronJobs::STATUS_COMPLETED,
                            'param' => json_encode($_log)
                        ]);
            } else {
                Log::info("[Motor Renewal Notice] None of the insurance records expires in [{$first}, {$second}, {$third}]");

                $_log = json_decode($log->param);
                $_log->message = "None of the insurance records expires in [{$first}, {$second}, {$third}]";

                CronJobs::where('id', $log->id)
                    ->update([
                        'status' => CronJobs::STATUS_COMPLETED,
                        'param' => json_encode($_log)
                    ]);
            }
        } catch (Exception $ex) {
            Log::error("[Motor Renewal Notice] An Error Encountered. [{$ex->getMessage()}] \n" . $ex);

            CronJobs::where('id', $log->id)
                ->update([
                    'status' => CronJobs::STATUS_FAILED,
                    'error_message' => $ex->getMessage()
                ]);
        }
    }
}
