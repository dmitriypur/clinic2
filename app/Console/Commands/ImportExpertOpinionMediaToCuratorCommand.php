<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\BlockType;
use App\Models\Block;
use App\Models\CuratorMedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ImportExpertOpinionMediaToCuratorCommand extends Command
{
    protected $signature = 'expert-opinion:import-to-curator
                            {--dry-run : Только показать количество блоков без сохранения}';

    protected $description = 'Копирует старые Spatie-фото блоков «Мнение эксперта» в Curator';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $found = 0;
        $imported = 0;
        $failed = 0;

        Block::query()
            ->withoutGlobalScopes()
            ->where('type', BlockType::EXPERT_OPINION->value)
            ->orderBy('id')
            ->chunkById(100, function ($blocks) use ($dryRun, &$found, &$imported, &$failed): void {
                foreach ($blocks as $block) {
                    if ($block->expert_opinion_image) {
                        continue;
                    }

                    $legacyMedia = $block->getFirstMedia('default');

                    if (! $legacyMedia) {
                        continue;
                    }

                    $found++;

                    if ($dryRun) {
                        continue;
                    }

                    try {
                        $contents = Storage::disk($legacyMedia->disk)
                            ->get($legacyMedia->getPathRelativeToRoot());
                        $checksum = hash('sha256', $contents);
                        $extension = strtolower(
                            pathinfo($legacyMedia->file_name, PATHINFO_EXTENSION) ?: 'jpg'
                        );
                        $path = "expert-opinions/{$checksum}.{$extension}";
                        $disk = (string) config('curator.disk', 'public');
                        $dimensions = @getimagesizefromstring($contents) ?: [null, null];

                        if (! Storage::disk($disk)->exists($path) && ! Storage::disk($disk)->put($path, $contents, 'public')) {
                            throw new RuntimeException("Не удалось записать файл {$path}");
                        }

                        Storage::disk($disk)->setVisibility($path, 'public');

                        $curatorMedia = CuratorMedia::query()->firstOrCreate(
                            ['checksum' => $checksum],
                            [
                                'disk' => $disk,
                                'directory' => 'expert-opinions',
                                'visibility' => 'public',
                                'name' => pathinfo($legacyMedia->file_name, PATHINFO_FILENAME),
                                'path' => $path,
                                'width' => $dimensions[0],
                                'height' => $dimensions[1],
                                'size' => strlen($contents),
                                'type' => $legacyMedia->mime_type,
                                'ext' => $extension,
                                'alt' => $block->title,
                                'title' => $block->title,
                            ]
                        );

                        $payload = (array) $block->payload;
                        $payload['curator_image_id'] = $curatorMedia->getKey();
                        $block->forceFill(['payload' => $payload])->saveQuietly();
                        $imported++;
                    } catch (Throwable $exception) {
                        report($exception);
                        $this->error("Блок #{$block->getKey()}: {$exception->getMessage()}");
                        $failed++;
                    }
                }
            });

        $this->line("Найдено старых фотографий: {$found}");

        if ($dryRun) {
            $this->comment('Dry run: файлы и база данных не изменялись.');

            return self::SUCCESS;
        }

        $this->line("Перенесено: {$imported}");
        $this->line("Ошибок: {$failed}");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
