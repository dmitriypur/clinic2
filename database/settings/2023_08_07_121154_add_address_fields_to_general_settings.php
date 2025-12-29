<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('general.city', '');
        $this->migrator->add('general.postal_code', '');
    }
};
