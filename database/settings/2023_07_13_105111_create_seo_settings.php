<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('seo.robots_txt', '');
        $this->migrator->add('seo.scripts', []);
        $this->migrator->add('seo.header_scripts', []);
    }
};
