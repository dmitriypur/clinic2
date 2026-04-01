<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Support\Concerns\HasExtraAlpineAttributes;

class UtmTrackerManager extends Field
{
    use HasExtraAlpineAttributes;

    protected string $view = 'filament.forms.components.utm-tracker-manager';

    protected function getTrackingBaseUrl(): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $record = $this->getRecord();

        if (! $record || ! filled($record->slug) || $record->is_default) {
            return $baseUrl;
        }

        return $baseUrl . '/' . ltrim((string) $record->slug, '/');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([
            'phones' => [],
            'sources' => [],
            'mediums' => [],
        ]);

        $this->viewData(fn (): array => [
            'trackingBaseUrl' => $this->getTrackingBaseUrl(),
        ]);
    }
}
