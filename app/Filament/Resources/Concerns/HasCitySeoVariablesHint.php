<?php

declare(strict_types=1);

namespace App\Filament\Resources\Concerns;

use App\Support\CitySeoVariables;

trait HasCitySeoVariablesHint
{
    protected static function citySeoVariablesHintText(): string
    {
        return 'Переменные города: ' . implode(', ', CitySeoVariables::placeholders());
    }
}
