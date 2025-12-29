<?php

namespace App\View\Components\Block;

use App\Models\Block;
use App\Settings\GeneralSettings;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Contacts extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(public GeneralSettings $settings, public Block $block)
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.block.contacts');
    }
}
