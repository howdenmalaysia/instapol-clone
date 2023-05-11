<?php

namespace App\View\Components;

use Illuminate\View\Component;

class ImageCarousel extends Component
{
    public $id;
    public $interval;
    public $images;
    public $mobile;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(string $id, string $interval = '5000', array $images, array $mobile)
    {
        $this->id = $id;
        $this->interval = $interval;
        $this->images = $images;
        $this->mobile = $mobile;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.frontend.image-carousel');
    }
}
