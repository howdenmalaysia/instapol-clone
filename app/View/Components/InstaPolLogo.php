<?php

namespace App\View\Components;

use Illuminate\View\Component;

class instaPolLogo extends Component
{
    // Props
    public $width;
    public $navy;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($width = null, bool $navy = false)
    {
        $this->width = $width;
        $this->navy = $navy;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.instapol-logo');
    }
}
