<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Benefits extends Component
{
    public $imagePath;
    public $title;
    public $description;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(string $imagePath, string $title, string $description)
    {
        $this->imagePath = $imagePath;
        $this->title = $title;
        $this->description = $description;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.frontend.benefits');
    }
}
