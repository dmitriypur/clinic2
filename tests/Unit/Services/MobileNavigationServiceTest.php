<?php

namespace Tests\Unit\Services;

use App\Services\MobileNavigationService;
use PHPUnit\Framework\TestCase;

class MobileNavigationServiceTest extends TestCase
{
    /** @test */
    public function it_marks_mobile_destinations_without_removing_desktop_items(): void
    {
        $items = [
            $this->item('Цены', 'uslugi-i-ceny'),
            $this->item('Услуги', '/services', [
                $this->item('Подбор ночных линз', 'podbor-nochnyh-linz-rebenku'),
            ]),
            $this->item('Специалисты', 'doctors'),
            $this->item('Отзывы', 'reviews'),
            $this->item('О нас', 'o-klinike'),
            $this->item('Контакты', 'kontakty'),
        ];

        $result = (new MobileNavigationService())->markItems($items);

        $this->assertCount(6, $result);
        $this->assertSame('prices', $result[0]['mobile_navigation_slot']);
        $this->assertSame('services', $result[1]['mobile_navigation_slot']);
        $this->assertSame('doctors', $result[2]['mobile_navigation_slot']);
        $this->assertArrayNotHasKey('mobile_navigation_slot', $result[3]);
        $this->assertSame('О клинике', $result[4]['mobile_label']);
        $this->assertSame('contacts', $result[5]['mobile_navigation_slot']);
        $this->assertSame('Врачи', $result[2]['mobile_label']);
        $this->assertSame('Подбор ночных линз', $result[1]['children'][0]['label']);
    }

    /** @test */
    public function it_builds_the_bottom_menu_in_design_order_and_preserves_city_urls(): void
    {
        $items = (new MobileNavigationService())->markItems([
            $this->item('Контакты', '/kirov/kontakty'),
            $this->item('Цены', '/kirov/uslugi-i-ceny'),
            $this->item('Специалисты', '/kirov/doctors'),
            $this->item('Услуги', '/kirov/services', [
                $this->item('Лечение косоглазия', '/kirov/lecenie-kosoglaziia'),
            ]),
        ], 'kirov');

        $result = (new MobileNavigationService())->bottomItems($items);

        $this->assertSame(['services', 'doctors', 'prices', 'contacts'], array_keys($result));
        $this->assertSame('/kirov/services', $result['services']['data']['url']);
        $this->assertSame('/kirov/lecenie-kosoglaziia', $result['services']['children'][0]['data']['url']);
        $this->assertSame('Врачи', $result['doctors']['mobile_label']);
    }

    /** @test */
    public function it_does_not_treat_an_external_url_as_a_mobile_destination(): void
    {
        $service = new MobileNavigationService();
        $result = $service->markItems([
            $this->item('Наши услуги', 'https://zrenie.clinic/services'),
            $this->item('Чужие услуги', 'https://example.com/services'),
        ], null, 'zrenie.clinic');

        $this->assertSame('services', $result[0]['mobile_navigation_slot']);
        $this->assertArrayNotHasKey('mobile_navigation_slot', $result[1]);
    }

    private function item(string $label, string $url, array $children = []): array
    {
        return [
            'label' => $label,
            'type' => 'page',
            'data' => [
                'url' => $url,
                'target' => null,
            ],
            'children' => $children,
        ];
    }
}
