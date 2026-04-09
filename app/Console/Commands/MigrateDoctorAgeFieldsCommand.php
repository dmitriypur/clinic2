<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Doctor;
use Illuminate\Console\Command;

class MigrateDoctorAgeFieldsCommand extends Command
{
    protected $signature = 'doctors:migrate-age-fields {--dry-run : Только показать изменения без сохранения}';

    protected $description = 'Переносит legacy extra.receives врачей в structured age fields';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $processed = 0;
        $migrated = 0;
        $skippedConfigured = 0;
        $skippedEmpty = 0;
        $skippedUnparsed = 0;

        Doctor::withoutGlobalScopes()
            ->orderBy('id')
            ->chunkById(200, function ($doctors) use ($dryRun, &$processed, &$migrated, &$skippedConfigured, &$skippedEmpty, &$skippedUnparsed): void {
                foreach ($doctors as $doctor) {
                    $processed++;
                    $extra = (array) ($doctor->extra ?? []);

                    if (
                        is_numeric($extra['age_min_months'] ?? null) ||
                        is_numeric($extra['age_max_months'] ?? null) ||
                        filled($extra['receives_text'] ?? null)
                    ) {
                        $skippedConfigured++;
                        continue;
                    }

                    $legacyReceives = trim((string) ($extra['receives'] ?? ''));

                    if ($legacyReceives === '') {
                        $skippedEmpty++;
                        continue;
                    }

                    $range = $this->extractLegacyAgeRange($legacyReceives);

                    if ($range['min_years'] === null && $range['max_years'] === null) {
                        $skippedUnparsed++;
                        continue;
                    }

                    $extra['age_min_months'] = $range['min_years'] !== null ? $range['min_years'] * 12 : null;
                    $extra['age_max_months'] = $range['max_years'] !== null ? $range['max_years'] * 12 : null;
                    $extra['receives_text'] = $legacyReceives;

                    if (!$dryRun) {
                        $doctor->forceFill(['extra' => $extra])->saveQuietly();
                    }

                    $migrated++;
                }
            });

        $this->info('Перенос age fields завершён.');
        $this->line("Обработано: {$processed}");
        $this->line("Перенесено: {$migrated}");
        $this->line("Пропущено, уже настроено: {$skippedConfigured}");
        $this->line("Пропущено, пустой receives: {$skippedEmpty}");
        $this->line("Пропущено, не удалось распарсить: {$skippedUnparsed}");

        if ($dryRun) {
            $this->comment('Dry run: изменения не сохранялись.');
        }

        return self::SUCCESS;
    }

    private function extractLegacyAgeRange(string $value): array
    {
        $matches = [];
        preg_match_all('/(\d+)/', $value, $matches);

        $numbers = collect($matches[1] ?? [])
            ->map(fn($item) => is_numeric($item) ? (int) $item : null)
            ->filter(fn($item) => $item !== null && $item >= 0)
            ->values();

        if ($numbers->isEmpty()) {
            return [
                'min_years' => null,
                'max_years' => null,
            ];
        }

        return [
            'min_years' => $numbers->get(0),
            'max_years' => $numbers->get(1),
        ];
    }
}
