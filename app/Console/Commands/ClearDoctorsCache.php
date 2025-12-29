<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearDoctorsCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-doctors {--pages=20 : Maximum number of doctor pages to clear}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear doctors cache including paginated pages';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $maxPages = (int) $this->option('pages');
        
        // Очистка основного кеша врачей
        Cache::forget('doctors');
        $this->info('Cleared main doctors cache');
        
        // Очистка кеша страниц врачей
        $clearedPages = 0;
        for ($i = 1; $i <= $maxPages; $i++) {
            $key = "doctors-page-{$i}";
            if (Cache::forget($key)) {
                $clearedPages++;
            }
        }
        
        $this->info("Cleared {$clearedPages} doctor page caches");
        $this->info('Doctors cache cleared successfully!');
        
        return self::SUCCESS;
    }
}