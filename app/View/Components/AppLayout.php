<?php

namespace App\View\Components;

use App\Clinic;
use App\Settings\GeneralSettings;
use App\Settings\SeoSettings;
use App\Services\MenuService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use RyanChandler\FilamentNavigation\Models\Navigation;
use SiroDiaz\Redirection\Models\Redirection;

class AppLayout extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public GeneralSettings $settings,
        public SeoSettings     $seoSettings,
        public MenuService     $menuService,
        public ?Navigation     $footerMenu,
        public ?Navigation     $mainMenu,
        public ?string         $title = null,
        public ?string         $description = null,
        public ?string         $image = null,
        public ?bool           $showHeader = true,
        public ?bool           $showFooter = true,
    )
    {

        $this->footerMenu = Cache::remember('footer-menu', 3600, fn() => Navigation::fromHandle('footer-menu'));
        $this->mainMenu = Cache::remember('main-menu', 3600, fn() => Navigation::fromHandle('main'));

        if ($this->mainMenu) {
            $this->mainMenu->items = $this->menuService->prepareItems($this->mainMenu->items);
        }

        if ($this->footerMenu) {
            $this->footerMenu->items = $this->menuService->prepareItems($this->footerMenu->items);
        }

        if (!$this->image) {
            $this->image = url('images/preview.jpg');
        }

    }


    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $city = app(\App\Services\CityService::class)->getCurrentCity();

        // Значения по умолчанию из города
        $phone = $city->phone ?? '';
        $address = $city->address ?? '';
        $schedule = $city->schedule ?? '';
        $socials = $city->social_links ?? [];
        $email = $city->email ?? '';
        $metro = $city->metro ?? '';
        $showSpecialSchedule = $city->show_special_schedule ?? false;
        $specialSchedule = $city->special_schedule ?? '';
        $specialScheduleTitle = $city->special_schedule_title ?? '';

        // Логика UTM
        $utmSource = Session::remember('utm_source', static fn() => request()->query('utm_source'));
        $utmMedium = Session::remember('utm_medium', static fn() => request()->query('utm_medium'));

        if (request()->has('utm_source') && $utmSource !== request()->query('utm_source')) {
            $utmSource = request()->query('utm_source');
            Session::put('utm_source', request()->query('utm_source'));
        }

        if (request()->has('utm_medium') && $utmSource !== request()->query('utm_medium')) {
            $utmMedium = request()->query('utm_medium');
            Session::put('utm_medium', request()->query('utm_medium'));
        }
        $utmSource = strtolower($utmSource ?? '');
        $utmMedium = strtolower($utmMedium ?? '');

        // Применяем правила подмены из города
        if ($city && !empty($city->utm_phones) && $utmSource) {
            foreach ($city->utm_phones as $rule) {
                // Проверяем совпадение по source
                if (strtolower($rule['source'] ?? '') === $utmSource) {
                    $foundPhone = null;

                    // Если есть medium в URL и правила для medium, ищем точное совпадение
                    if ($utmMedium && !empty($rule['medium']) && is_array($rule['medium'])) {
                        foreach ($rule['medium'] as $mediumRule) {
                            if (strtolower($mediumRule['name'] ?? '') === $utmMedium) {
                                $foundPhone = $mediumRule['phone'] ?? null;
                                break;
                            }
                        }
                    }

                    // Если телефон по medium не найден (или medium не задан), берем общий телефон для source
                    if (!$foundPhone) {
                        $foundPhone = $rule['phone'] ?? null;
                    }

                    if ($foundPhone) {
                        $phone = $foundPhone;
                        break;
                    }
                }
            }
        }

        // Делимся переменными со всеми view, которые рендерятся внутри лайаута
        \Illuminate\Support\Facades\View::share('phone', $phone);
        \Illuminate\Support\Facades\View::share('address', $address);
        \Illuminate\Support\Facades\View::share('schedule', $schedule);
        \Illuminate\Support\Facades\View::share('socials', $socials);
        \Illuminate\Support\Facades\View::share('email', $email);
        \Illuminate\Support\Facades\View::share('metro', $metro);
        \Illuminate\Support\Facades\View::share('showSpecialSchedule', $showSpecialSchedule);
        \Illuminate\Support\Facades\View::share('specialSchedule', $specialSchedule);
        \Illuminate\Support\Facades\View::share('specialScheduleTitle', $specialScheduleTitle);
        \Illuminate\Support\Facades\View::share('currentCity', $city);

        return view('layouts.app');
    }

    public function cookieConsentName(): string
    {
        return Str::slug(env('APP_NAME', 'laravel'), '_') . '_cookie_consent';
    }
}
