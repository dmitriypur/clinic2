<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('seo.logo_alt', config('app.name'));
        $this->migrator->add('seo.logo_title', config('app.name'));
    }
};
