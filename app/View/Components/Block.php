<?php

namespace App\View\Components;

use App\Enums\BlockBackgroundType;
use App\Enums\BlockType;
use App\Models\Block as PageBlock;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Block extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public PageBlock $block,
        public string    $breadcrumbsTitle,
        public string    $pageTitle,
        public string    $pageDescription,
    )
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.block');
    }

    public function subdued(): bool
    {
        return data_get($this->block->settings, 'background') === strval(BlockBackgroundType::SUBDUED->value);
    }


    public function className(): string
    {
        $bg = '';
        switch (data_get($this->block->settings, 'background')) {
            case strval(BlockBackgroundType::SUBDUED->value):
                $bg = 'bg-surface-subdued';
                break;
            case strval(BlockBackgroundType::SURFACE->value):
                $bg = 'bg-surface';
                break;
            case strval(BlockBackgroundType::BEIGE->value):
                $bg = 'bg-action-primary-light';
                break;
        }

        return implode(' ', array_filter([
            $bg,
            data_get($this->block->settings, 'remove_top_padding') ? null : 'pt-5 md:pt-10',
            data_get($this->block->settings, 'remove_bottom_padding') ? null : 'pb-5 md:pb-10',
        ]));
    }

    public function headingMarginClassName(): string
    {
        return $this->block->type === BlockType::TAGS ? 'lg:mb-8' : 'lg:mb-16';
    }

}
