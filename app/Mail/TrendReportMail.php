<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class TrendReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $full_range;
    public Collection $trend;
    public Collection $referral_links;
    public array $ranges;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $date_range, Collection $data, Collection $referral_links, array $date_ranges)
    {
        $this->full_range = $date_range;
        $this->trend = $data;
        $this->referral_links = $referral_links;
        $this->ranges = $date_ranges;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject((app()->environment('local', 'development') ? '[Dev] ' : '') . 'instaPol Weekly Report Trend View For ' . $this->full_range)
            ->view('backend.emails.trend_report')
            ->with([
                'data' => $this->trend,
                'referrals' => $this->referral_links,
                'full_range' => $this->full_range,
                'ranges' => $this->ranges
            ]);
    }
}
