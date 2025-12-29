<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Clinic;
use App\Enums\PageType;
use App\Models\Doctor;
use App\Models\Page;
use App\Models\Service;
use App\Services\PageService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

class PageController extends Controller
{
    private const CACHE_TTL = 2592000; // 30 дней
    private const DOCTORS_PER_PAGE = 5;

    public function __construct(
        private readonly PageService $pageService
    ) {}

    public function __invoke(?string $category, ?string $handle = null): View|\Illuminate\Http\RedirectResponse
    {
        $page = $this->resolvePage($category, $handle);
        $redirect = $this->maybeRedirectCanonical($page, $category, $handle);
        if ($redirect) {
            return $redirect;
        }

        $viewData = $this->prepareViewData($page);
        $seoData = $this->pageService->getPageSeoData($page);
        
        $viewName = $this->pageService->shouldShowPostsView($page) ? 'posts.show' : 'pages.show';

        return view($viewName, array_merge($viewData, $seoData));
    }

    private function resolvePage(?string $category, ?string $handle): Page
    {
        $cityKey = app(\App\Services\CityService::class)->getCurrentCity()?->slug ?? 'global';

        if ($handle === null) {
            $category = $category ?? '/';
            $cacheKey = "page-{$cityKey}-{$category}";

            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($category) {
                return Page::query()
                    ->where('handle', $category)
                    ->where('active', true)
                    ->with([
                        'blocks' => function ($query) {
                            $query->orderBy('order_column');
                        },
                        'blocks.media',
                        'category'
                    ])
                    ->firstOrFail();
            });
        }

        $cacheKey = "page-{$cityKey}-{$handle}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($handle) {
            return Page::query()
                ->where('handle', $handle)
                ->where('active', true)
                ->with([
                    'blocks' => function ($query) {
                        $query->orderBy('order_column');
                    },
                    'blocks.media',
                    'category'
                ])
                ->firstOrFail();
        });
    }

    private function maybeRedirectCanonical(Page $page, ?string $category, ?string $handle): ?\Illuminate\Http\RedirectResponse
    {
        $canonicalPath = $this->buildCanonicalPath($page);
        $currentPath = $this->buildCurrentPath($category, $handle);

        if ($canonicalPath !== $currentPath) {
            return redirect()->to(url($canonicalPath), 301);
        }

        return null;
    }

    private function buildCanonicalPath(Page $page): string
    {
        if ($page->type === PageType::Posts) {
            if ($page->category) {
                return "/{$page->category->handle}/{$page->handle}";
            }
            return "/stati/{$page->handle}";
        }

        return $page->handle === '/' ? '/' : "/{$page->handle}";
    }

    private function buildCurrentPath(?string $category, ?string $handle): string
    {
        if ($category === null) {
            return $handle ? "/{$handle}" : '/';
        }

        return $handle ? "/{$category}/{$handle}" : "/{$category}";
    }

    private function validatePageAccess(Page $page, ?string $category): void
    {
        if ($category === null || $page->handle === '/') {
            // Для роута {handle?} или главной страницы
            // Страница не должна иметь категорию
            abort_unless($page->category === null, 404);
        } else {
            // Для роута /{category}/{handle?}
            // Страница должна принадлежать указанной категории
            abort_unless(
                $page->category && $page->category->handle === $category,
                404
            );
        }
    }

    private function getDoctorsForPage(Page $page)
    {
        if ($page->type !== PageType::Doctors) {
            return collect([]);
        }

        $cityKey = app(\App\Services\CityService::class)->getCurrentCity()?->slug ?? 'global';

        // Кешируем запрос врачей для страницы
        $cacheKey = "doctors-page-{$cityKey}-" . request('page', 1);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return Doctor::query()
                ->with('media')
                ->paginate(self::DOCTORS_PER_PAGE);
        });
    }

    private function prepareViewData(Page $page): array
    {
        return [
            'page' => $page,
            'category' => $page->category,
            'doctors' => $this->getDoctorsForPage($page),
            'services' => $this->getServicesForPage($page),
        ];
    }

    private function getServicesForPage(Page $page): \Illuminate\Support\Collection|array
    {
        if (!$page->is_price_page) {
            return [];
        }

        return app(\App\Services\ServicePriceService::class)->getServicesWithPrices();
    }
}
