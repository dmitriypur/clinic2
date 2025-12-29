<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('general.site_name', 'Центр детского зрения.');
        $this->migrator->add('general.phone', '');
        $this->migrator->add('general.email', '');
        $this->migrator->add('general.address', '');
        $this->migrator->add('general.coordinates', '');
        $this->migrator->add('general.schedule', '');
        $this->migrator->add('general.yandex_map_api_key', '');
        $this->migrator->add('general.favicon', '');
        $this->migrator->add('general.vk', '');
    }
};
