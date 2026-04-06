<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\City;
use App\Services\YmlFeedService;
use Illuminate\Console\Command;

class GenerateYmlFeedCommand extends Command
{
    protected $signature = 'yml-feed:generate {--save : Сохранить файлы в public папку} {--city= : Сгенерировать фид только для указанного slug города}';
    protected $description = 'Генерирует YML фид врачей для Яндекса';

    public function handle(YmlFeedService $ymlFeedService): int
    {
        try {
            set_time_limit(120);

            $citySlug = $this->option('city');
            $targetCity = null;

            if (is_string($citySlug) && trim($citySlug) !== '') {
                $targetCity = City::query()
                    ->where('slug', trim($citySlug))
                    ->where('active', true)
                    ->first();

                if (!$targetCity) {
                    $this->error("Активный город со slug '{$citySlug}' не найден.");
                    return Command::FAILURE;
                }
            }

            if ($targetCity) {
                $this->info("Генерация YML фида врачей для города {$targetCity->name}...");
                $feedContent = $ymlFeedService->generateDoctorsFeed($targetCity);

                if ($this->option('save')) {
                    $filename = $ymlFeedService->saveFeedToFile($feedContent, $targetCity);
                    $this->info("Фид сохранен в файл: {$filename}");
                } else {
                    $this->line($feedContent);
                }

                $this->info('Фид успешно сгенерирован!');
                return Command::SUCCESS;
            }

            $this->info('Генерация YML фидов врачей для всех активных городов...');
            $feeds = $ymlFeedService->generateDoctorsFeedsForActiveCities();

            if ($this->option('save')) {
                $savedFeeds = $ymlFeedService->saveFeedsToFiles($feeds);
                foreach ($savedFeeds as $feed) {
                    $this->info("{$feed['city_name']} ({$feed['city_slug']}): {$feed['filename']}");
                }
            } else {
                foreach ($feeds as $index => $feed) {
                    if ($index > 0) {
                        $this->newLine();
                    }

                    $this->info("=== {$feed['city_name']} ({$feed['city_slug']}) ===");
                    $this->line($feed['content']);
                }
            }

            $this->info('Фиды успешно сгенерированы!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Ошибка при генерации фида: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
