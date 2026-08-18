<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Blocks\Generation\BlockScaffolder;
use Illuminate\Console\Command;
use Throwable;

class MakeBlockCommand extends Command
{
    protected $signature = 'make:block
        {name : Название блока в админке}
        {--slug= : Blade slug, например treatment-steps}
        {--type= : Enum case, например TREATMENT_STEPS}';

    protected $description = 'Создаёт BlockType, definition, Blade-шаблон и тест нового блока';

    public function handle(BlockScaffolder $scaffolder): int
    {
        try {
            $changedFiles = $scaffolder->generate(
                (string) $this->argument('name'),
                $this->option('slug') ?: null,
                $this->option('type') ?: null,
                null,
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Блок создан:');

        foreach ($changedFiles as $changedFile) {
            $this->line("- {$changedFile}");
        }

        $this->newLine();
        $this->line('Дальше: заполните formSchema(), Blade-шаблон и тест.');

        return self::SUCCESS;
    }
}
