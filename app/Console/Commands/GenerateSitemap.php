<?php

namespace App\Console\Commands;

use App\Settings\SeoSettings;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use App\Services\CityService;
use App\Models\City;

class GenerateSitemap extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the sitemap.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(SeoSettings $seoSettings, CityService $cityService)
    {
        $this->info("Начало генерации карт сайта...");

        try {
            // Получаем все активные города
            $cities = $cityService->getActiveCities();

            // Перебираем каждый город и создаем для него отдельный файл
            foreach ($cities as $city) {
                // Устанавливаем текущий город для правильной работы getUrl() в моделях
                $cityService->setCurrentCity($city);
                
                if ($city->is_default) {
                    $filename = 'sitemap.xml';
                    $prefix = '';
                } else {
                    $filename = "sitemap-{$city->slug}.xml";
                    $prefix = '/' . $city->slug;
                }

                $this->info("Генерация {$filename} для города {$city->name}...");

                // Создаем пустую карту сайта (без краулинга, только ручное добавление)
                // Это гарантирует, что мы добавим только ссылки, относящиеся к этому городу
                $sitemap = Sitemap::create();

                $this->addStaticPages($sitemap, $prefix);
                $this->addDynamicPages($sitemap, $city, $prefix);

                $sitemap->writeToFile(public_path($filename));
            }

            $this->info("Все карты сайта успешно сгенерированы.");

        } catch (\Exception $e) {
            $this->error("Ошибка при генерации карты сайта: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function addStaticPages($sitemap, $prefix)
    {
        // Добавляем основные страницы сайта
        $pages = [
            '',
            '/about',
            '/services',
            '/doctors',
            '/contacts',
            '/reviews',
            '/promotions'
        ];

        foreach ($pages as $page) {
            $url = config('app.url') . $prefix . $page;
            // Убираем двойные слеши, если есть, но сохраняем протокол
            if ($prefix === '' && $page === '') {
                 // Главная страница default города
            } elseif ($prefix !== '' && $page === '') {
                 // Главная страница города (например /msk)
                 // config('app.url') уже без слеша на конце обычно, но проверим
                 $url = rtrim(config('app.url'), '/') . $prefix;
            }

            $sitemap->add(
                Url::create($url)
                    ->setLastModificationDate(now())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                    ->setPriority(0.8)
            );
        }
    }

    private function addDynamicPages($sitemap, City $city, $prefix)
    {
        // Добавляем страницы врачей
        // Если у врача нет привязки к городам (глобальный) или привязан к текущему
        $doctors = \App\Models\Doctor::where(function ($query) use ($city) {
            $query->whereHas('cities', function ($q) use ($city) {
                $q->where('cities.id', $city->id);
            })->orDoesntHave('cities');
        })->get();

        foreach ($doctors as $doctor) {
            $url = rtrim(config('app.url'), '/') . $prefix . '/doctors/' . ($doctor->handle ?? $doctor->id);
            
            $sitemap->add(
                Url::create($url)
                    ->setLastModificationDate($doctor->updated_at)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                    ->setPriority(0.6)
            );
        }

        // Добавляем страницы из модели Page
        $pages = \App\Models\Page::with('category')
            ->where('active', true)
            ->where(function ($query) use ($city) {
                $query->whereHas('cities', function ($q) use ($city) {
                    $q->where('cities.id', $city->id);
                })->orDoesntHave('cities');
            })
            ->get();

        foreach ($pages as $page) {
            // Для корректной генерации URL в консоли нам нужно явно передать префикс
            // так как метод getUrl() может не всегда корректно определять контекст в консоли
            // или кэшировать состояние
            
            $url = $this->generatePageUrl($page, $prefix);
            
            // Проверяем, что URL корректный
            if ($url && !empty(trim($url)) && $url !== '//' && filter_var($url, FILTER_VALIDATE_URL)) {
                $sitemap->add(
                    Url::create($url)
                        ->setLastModificationDate($page->updated_at)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                        ->setPriority(0.5)
                );
            }
        }
    }

    private function generatePageUrl($page, $prefix)
    {
        // Убираем слеш в начале префикса, если он есть, чтобы избежать двойных слешей при склейке
        // но нам нужен префикс вида /city, поэтому лучше контролировать это
        
        $path = '';
        if ($page->category) {
            $path = "{$page->category->handle}/{$page->handle}";
        } else {
            $path = "{$page->handle}";
        }
        
        return rtrim(config('app.url'), '/') . $prefix . '/' . $path;
    }
}
