<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('general.special_schedule', '');
        $this->migrator->add('general.special_schedule_title', '');
        $this->migrator->add('general.show_special_schedule', false);
    }
};
