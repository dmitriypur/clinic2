<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use RyanChandler\FilamentNavigation\Models\Navigation;

class NavigationObserver
{
    public function saved(Navigation $navigation): void
    {
        $this->clearCache($navigation);
    }

    public function deleted(Navigation $navigation): void
    {
        $this->clearCache($navigation);
    }

    private function clearCache(Navigation $navigation): void
    {
        // Сбрасываем кэш по handle меню (например, 'main-menu' или 'footer-menu')
        if ($navigation->handle) {
            Cache::forget($navigation->handle);
            Cache::forget("navigation-{$navigation->handle}");
        }
        
        // На всякий случай сбрасываем известные ключи
        Cache::forget('main-menu');
        Cache::forget('footer-menu');
    }
}