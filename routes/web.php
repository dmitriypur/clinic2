<?php

use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\FormRobotsTxtController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Profile\BonusesController;
use App\Http\Controllers\Profile\HistoryController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\RobotsTxtController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapHtmlController;
use App\Http\Controllers\YmlFeedController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

// --- Global Routes (No City Context needed or Global Context) ---

Route::get('/clear-price', function (){
    Cache::forget('prices');
    Cache::forget('services_with_media_and_prices');
    Cache::forget('active_promotions');
    return 'Good boy';
});

Route::view('form', 'form');

Route::domain('form.' . parse_url(config('app.url'), PHP_URL_HOST))->group(function () {
    Route::get('robots.txt', \App\Http\Controllers\FormRobotsTxtController::class);
    Route::get('/', [FormController::class, 'show'])->name('form.show');
    Route::post('/', [FormController::class, 'store']);
});

Route::get('robots.txt', RobotsTxtController::class);

Route::post('login', [LoginController::class, 'login'])->name('login');
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('/', [ProfileController::class, 'update'])->name('profile.update');
        Route::get('bonuses', BonusesController::class)->name('profile.bonuses');
        Route::get('history', HistoryController::class)->name('profile.history');
        Route::get('notifications', [ProfileController::class, 'show'])->name('profile.notifications');
    });
});

// YML Feed Routes
Route::middleware('auth')->group(function () {
    Route::post('/admin/yml-feed/generate', [YmlFeedController::class, 'generateDoctorsFeed'])->name('yml-feed.generate');
});
Route::get('/admin/yml-feed/download/{filename}', [YmlFeedController::class, 'downloadFeed'])->name('yml-feed.download');
Route::get('/yml-feed/doctors', [YmlFeedController::class, 'showDoctorsFeed'])->name('yml-feed.show');

// --- Content Routes (Multicity) ---

$contentRoutes = function () {
    Route::get('/search', [SearchController::class, 'search'])->name('search');
    Route::get('/live-search', [SearchController::class, 'liveSearch'])->name('live.search');

    Route::get('/reviews', ReviewController::class)->name('review.index');
    Route::get('/stati', PostController::class)->name('stati.index');
    Route::get('/directory', PostController::class)->name('directory.index');
    
    Route::get('/tags', function (){
        return redirect()->route('stati.index');
    });
    Route::get('/tags/{handle?}', PostController::class)->name('tag.index');
    
    Route::get('/sitemap.xml', function (\Illuminate\Http\Request $request) {
        // Пытаемся получить параметр city из маршрута
        $city = $request->route('city');

        // Если параметр не пришел через route(), пробуем распарсить URL вручную
        // Это костыль, но он поможет, если маршрутизатор Laravel по какой-то причине не пробрасывает параметр
        if (!$city) {
            $segments = $request->segments();
            // URL вида /kirov/sitemap.xml -> segments: ['kirov', 'sitemap.xml']
            if (count($segments) >= 2 && $segments[count($segments) - 1] === 'sitemap.xml') {
                $city = $segments[count($segments) - 2];
                // Проверяем, является ли этот сегмент допустимым слагом города
                // (чтобы не спутать с другими префиксами, если они есть)
                // Но у нас группа маршрутов ограничена where(['city' => $citySlugs]), так что это должно быть безопасно
            }
        }
        
        // Если мы в глобальной группе, $city будет null (или false), и сработает первый маршрут, 
        // но так как они имеют одинаковый URI, второй перезаписывает первый внутри группы.
        // Поэтому нам нужно обрабатывать оба случая здесь.
        
        if (!$city) {
             $path = public_path('sitemap.xml');
             if (!file_exists($path)) {
                 // Если глобального sitemap.xml нет, отдаем 404
                 abort(404, 'Global Sitemap not found');
             }
             return response()->file($path);
        }

        $filename = "sitemap-{$city}.xml";
        $path = public_path($filename);
        if (!file_exists($path)) {
            $cityExists = \App\Models\City::where('slug', $city)->exists();
            if (!$cityExists) {
                abort(404);
            }
            // Если файл специфичной карты не найден, отдаем глобальную (как запасной вариант)
            // ИЛИ 404, если мы хотим быть строгими.
            // Для целей отладки лучше пока отдавать 404 с сообщением
            abort(404, "Sitemap for city '{$city}' not found");
        }
        return response()->file($path);
    });

    Route::get('sitemap.html', SitemapHtmlController::class);

    Route::view('call-request', 'call-request');
    Route::get('/doctors/{handle}', DoctorController::class)->name('doctor.show');

    // Page Controller (Must be last)
    Route::get('/{category}/{handle?}', PageController::class)->name('posts.show');
    Route::get('{handle?}', PageController::class)->name('pages.show');
};

// 1. City Context Routes (e.g. /spb/services)
// Get active cities slugs for routing constraint
try {
    $citySlugs = \Illuminate\Support\Facades\Cache::remember('route_city_slugs', 3600, function () {
        return \App\Models\City::where('active', true)->pluck('slug')->implode('|');
    });
} catch (\Exception $e) {
    $citySlugs = 'spb'; // Fallback
}

if (empty($citySlugs)) {
     $citySlugs = 'nowhere';
}

Route::prefix('{city}')
    ->where(['city' => $citySlugs]) // Dynamic slug validation
    ->group($contentRoutes);

// 2. Default Context Routes (e.g. /services)
Route::group([], $contentRoutes);
