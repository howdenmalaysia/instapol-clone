<?php

namespace App\View\Components;

use Illuminate\View\Component;

class DescriptionWithNumbering extends Component
{
    public $number;
    public $title;
    public $description;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(int $number, string $title, string $description)
    {
        $this->number = $number;
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
        return view('components.frontend.description-with-numbering');
    }
}
