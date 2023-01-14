<?php

namespace App\View\Components;

use Illuminate\View\Component;

class PricingCard extends Component
{
    public string $insurerLogo;
    public string $insurerName;
    public string $basicPremium;
    public string $ncdAmount;
    public string $totalBenefitAmount;
    public string $grossPremium;
    public string $sstAmount;
    public string $stampDuty;
    public string $totalPayable;
    public string $roadtaxTotal;
    public bool $promo;

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
        float $totalPayable,
        float $roadtaxTotal,
        bool $promo)
    {
        $this->insurerLogo = $insurerLogo;
        $this->insurerName = $insurerName;
        $this->basicPremium = number_format($basicPremium, 2);
        $this->ncdAmount = number_format($ncdAmount, 2);
        $this->totalBenefitAmount = number_format($totalBenefitAmount, 2);
        $this->grossPremium = number_format($grossPremium, 2);
        $this->sstAmount = number_format($sstAmount, 2);
        $this->stampDuty = number_format($stampDuty, 2);
        $this->totalPayable = number_format($totalPayable, 2);
        $this->roadtaxTotal = number_format($roadtaxTotal, 2);
        $this->promo = $promo;
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
