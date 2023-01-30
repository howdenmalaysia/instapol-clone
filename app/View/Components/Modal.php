<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Support\Str;

class Modal extends Component
{
    public string $maxWidth;
    public string $id;
    public bool $backdropStatic;
    public string $headerClass;
    public bool $notClosable;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(string $maxWidth, string $id, string $headerClass = '', bool $backdropStatic = false, bool $notClosable = false)
    {
        $this->maxWidth = $this->getMaxWith($maxWidth);
        $this->id = $id ?? Str::uuid();
        $this->backdropStatic = $backdropStatic;
        $this->headerClass = $headerClass;
        $this->notClosable = $notClosable;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.modal');
    }

    private function getMaxWith($maxWidth)
    {
        switch($maxWidth) {
            case 'sm': {
                return 'modal-sm';
            }
            case 'md': {
                return '';
            }
            case 'lg': {
                return 'modal-lg';
            }
            case 'xl': {
                return 'modal-xl';
            }
        }
    }
}
