<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Steps extends Component
{
    public int $currentStep;
    
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(int $currentStep)
    {
        $this->currentStep = $currentStep;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.frontend.motor.steps');
    }
}
