<?php

namespace App\View\Components;

use Illuminate\View\Component;

class PricingCard extends Component
{
    public string $insurerLogo;
    public string $insurerName;
    public float $basicPremium;
    public float $ncdAmount;
    public float $totalBenefitAmount;
    public float $grossPremium;
    public float $sstAmount;
    public float $stampDuty;
    public float $totalPayable;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
        string $insurerLogo,
        string $insurerName,
        float $basicPremium,
        float $ncdAmount,
        float $totalBenefitAmount,
        float $grossPremium,
        float $sstAmount,
        float $stampDuty,
        float $totalPayable)
    {
        $this->insurerLogo = $insurerLogo;
        $this->insurerName = $insurerName;
        $this->basicPremium = number_format(floatval($basicPremium), 2);
        $this->ncdAmount = number_format(floatval($ncdAmount), 2);
        $this->totalBenefitAmount = number_format(floatval($totalBenefitAmount), 2);
        $this->grossPremium = number_format(floatval($grossPremium), 2);
        $this->sstAmount = number_format(floatval($sstAmount), 2);
        $this->stampDuty = number_format(floatval($stampDuty), 2);
        $this->totalPayable = number_format(floatval($totalPayable), 2);
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.frontend.motor.pricing-card');
    }
}
