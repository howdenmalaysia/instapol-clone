<?php

namespace App\View\Components;

use Illuminate\View\Component;

class PricingCard extends Component
{
    public string $insurerLogo;
    public string $insurerName;
    public object $data;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(string $insurerLogo, string $insurerName, object $data)
    {
        $this->insurerLogo = $insurerLogo;
        $this->insurerName = $insurerName;
        $this->data = $data;
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
