<?php

namespace Tests\Feature;

use Tests\TestCase;

class MobileNavigationViewTest extends TestCase
{
    /** @test */
    public function bottom_navigation_renders_city_links_booking_action_and_service_children(): void
    {
        $items = [
            'services' => $this->item('Услуги', '/kirov/services', [
                $this->item('Подбор ночных линз', '/kirov/podbor-nochnyh-linz-rebenku'),
            ], true, 'services'),
            'doctors' => $this->item('Специалисты', '/kirov/doctors', [], false, 'doctors', 'Врачи'),
            'prices' => $this->item('Цены', '/kirov/uslugi-i-ceny', [], false, 'prices'),
            'contacts' => $this->item('Контакты', '/kirov/kontakty', [], false, 'contacts'),
        ];

        $view = $this->view('components.mobile-bottom-navigation', compact('items'));

        $view->assertSee('aria-label="Основная мобильная навигация"', false);
        $view->assertSee('href="/kirov/doctors"', false);
        $view->assertSee('href="/kirov/uslugi-i-ceny"', false);
        $view->assertSee('href="/kirov/kontakty"', false);
        $view->assertSee('Подбор ночных линз');
        $view->assertSee('href="/kirov/podbor-nochnyh-linz-rebenku"', false);
        $view->assertSee('Записаться');
        $view->assertSee('@click="openBookingWidget"', false);
        $view->assertSee('mobile-bottom-navigation__booking', false);
        $view->assertSee('lg:hidden', false);
        $view->assertSee('role="dialog"', false);
        $view->assertSee('aria-modal="true"', false);
        $view->assertSee('aria-current="page"', false);
    }

    /** @test */
    public function incomplete_city_menu_keeps_all_five_layout_slots(): void
    {
        $items = [
            'services' => $this->item('Услуги', '/kirov/services', [], false, 'services'),
            'doctors' => $this->item('Специалисты', '/kirov/doctors', [], false, 'doctors', 'Врачи'),
        ];

        $view = $this->view('components.mobile-bottom-navigation', compact('items'));

        $view->assertSee('data-mobile-nav-slot="prices-placeholder"', false);
        $view->assertSee('data-mobile-nav-slot="contacts-placeholder"', false);
        $view->assertSee('Записаться');
    }

    /** @test */
    public function empty_menu_still_renders_the_central_booking_action(): void
    {
        $view = $this->view('components.mobile-bottom-navigation', ['items' => []]);

        $view->assertSee('data-mobile-nav-slot="services-placeholder"', false);
        $view->assertSee('data-mobile-nav-slot="doctors-placeholder"', false);
        $view->assertSee('data-mobile-nav-slot="prices-placeholder"', false);
        $view->assertSee('data-mobile-nav-slot="contacts-placeholder"', false);
        $view->assertSee('@click="openBookingWidget"', false);
    }

    /** @test */
    public function booking_button_disables_the_global_wide_pseudo_element(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression(
            '/\.mobile-bottom-navigation__booking::before\s*\{\s*display:\s*none;\s*\}/',
            $css,
        );
    }

    /** @test */
    public function transferred_item_stays_visible_on_desktop_and_mobile_label_does_not_replace_desktop_label(): void
    {
        $transferred = $this->item('Специалисты', '/doctors', [], false, 'doctors', 'Врачи');
        $about = $this->item('О нас', '/o-klinike', [], false, null, 'О клинике');

        $transferredView = $this->view('components.mega-menu.item', [
            'item' => $transferred,
            'menuIndex' => 0,
        ]);
        $aboutView = $this->view('components.mega-menu.item', [
            'item' => $about,
            'menuIndex' => 1,
        ]);

        $transferredView->assertSee('hidden lg:block', false);
        $transferredView->assertSee('Специалисты');
        $aboutView->assertSee('О клинике');
        $aboutView->assertSee('О нас');
    }

    private function item(
        string $label,
        string $url,
        array $children = [],
        bool $active = false,
        ?string $slot = null,
        ?string $mobileLabel = null,
    ): array {
        return array_filter([
            'label' => $label,
            'type' => 'page',
            'data' => [
                'url' => $url,
                'target' => null,
            ],
            'children' => $children,
            'active' => $active,
            'is_simple' => true,
            'is_doctor_grid' => false,
            'mobile_navigation_slot' => $slot,
            'mobile_label' => $mobileLabel,
        ], static fn ($value) => $value !== null);
    }
}
