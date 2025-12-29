<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('seo.image_alt_template', '');
        $this->migrator->add('seo.image_title_template', '');
    }
};
