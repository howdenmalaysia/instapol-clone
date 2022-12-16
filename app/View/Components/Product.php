<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Product extends Component
{
    public $imagePath;
    public $name;
    public $alt;
    public $height;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(string $imagePath, string $name, string $alt = null, int $height = 50)
    {
        $this->imagePath = $imagePath;
        $this->name = $name;
        $this->alt = $alt ?? $name;
        $this->height = $height;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.frontend.product');
    }
}
