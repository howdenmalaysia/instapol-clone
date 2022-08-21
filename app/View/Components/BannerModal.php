<?php

namespace App\View\Components;

use Illuminate\View\Component;

class BannerModal extends Component
{
    public $static;
    public $image;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(bool $static = false, string $image)
    {
        $this->static = $static;
        $this->image = $image;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.frontend.banner-modal');
    }
}
