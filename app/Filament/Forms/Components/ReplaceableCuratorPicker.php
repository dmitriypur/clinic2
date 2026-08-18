<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use Awcodes\Curator\Components\Forms\CuratorPicker;

class ReplaceableCuratorPicker extends CuratorPicker
{
    protected string $view = 'filament.forms.components.replaceable-curator-picker';
}
