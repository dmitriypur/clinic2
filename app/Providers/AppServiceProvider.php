<?php

namespace App\Providers;

use App\Clinic;
use App\Models\Block;
use App\Models\Page;
use App\Observers\BlockObserver;
use App\Observers\PageObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use RyanChandler\FilamentNavigation\Filament\Resources\NavigationResource;
use RyanChandler\FilamentNavigation\Models\Navigation;

use Illuminate\Support\Facades\View;
use App\Services\ServicePriceService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\CityService::class);
        $this->app->singleton(ServicePriceService::class);

        if (!class_exists('Clinic')) {
            class_alias('App\Clinic', 'Clinic');
        }

        $this->registerServices();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        NavigationResource::label('Меню');
        NavigationResource::pluralLabel('Меню');
        NavigationResource::navigationSort('6');

        Clinic::setHttp();

        Model::preventLazyLoading(!app()->isProduction());
        
        // Регистрируем обсерверы
        Page::observe(PageObserver::class);
        Block::observe(BlockObserver::class);
        Navigation::observe(\App\Observers\NavigationObserver::class);

        View::composer('components.block.full-price-list', function ($view) {
            $view->with('services', app(ServicePriceService::class)->getServicesWithPrices());
        });
    }

    protected function registerServices()
    {
        $services = [
            'Contracts\Services\PhoneService' => 'Services\PhoneService',
            'Contracts\Services\SmsService' => 'Services\SmsAeroService',
            'Contracts\Services\InitialFrontendState' => 'Services\InitialFrontendState',
            'Contracts\Services\ScheduleService' => 'Services\ScheduleService',
            'Contracts\Services\Schema\Schema' => 'Services\Schema\Schema',
        ];

        foreach ($services as $key => $value) {
            $this->app->singleton('App\\' . $key, 'App\\' . $value);
        }
    }
}
