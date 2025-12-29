<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\YmlFeedService;
use Illuminate\Console\Command;

class GenerateYmlFeedCommand extends Command
{
    protected $signature = 'yml-feed:generate {--save : Сохранить файл в public папку}';
    protected $description = 'Генерирует YML фид врачей для Яндекса';

    public function handle(YmlFeedService $ymlFeedService): int
    {
        $this->info('Генерация YML фида врачей...');
        
        try {
            // Увеличиваем лимит времени выполнения
            set_time_limit(120);
            
            $feedContent = $ymlFeedService->generateDoctorsFeed();
            
            if ($this->option('save')) {
                $filename = $ymlFeedService->saveFeedToFile($feedContent);
                $this->info("Фид сохранен в файл: {$filename}");
            } else {
                $this->info('Содержимое фида:');
                $this->line($feedContent);
            }
            
            $this->info('Фид успешно сгенерирован!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Ошибка при генерации фида: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
