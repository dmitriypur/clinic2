<?php

namespace Tests\Unit;

use App\Models\City;
use App\Services\BookingBranchEnrichmentService;
use App\Support\BookingPriceForm;
use App\Support\BookingPriceSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingBranchEnrichmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduling_first_local_price_keeps_api_price_until_start_but_not_after_expiry(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 5)->startOfDay());
        $original = [['external_id' => 'branch-1', 'price' => '']];
        $data = BookingPriceForm::hydrateCity(['branches' => $original]);
        $data['branches'][0]['_booking_price_editor']['price']['amount'] = '3000';
        $data['branches'][0]['_booking_price_editor']['price']['starts_on'] = '2026-11-01';
        $data['branches'][0]['_booking_price_editor']['price']['ends_on'] = '2026-11-30';
        $saved = BookingPriceForm::saveCity($data, $original);
        $city = new City(['name' => 'Москва', 'branches' => $saved['branches']]);
        $result = app(BookingBranchEnrichmentService::class)->enrichBranches([
            ['external_id' => 'branch-1', 'price' => '2500'],
        ], $city)[0];

        $this->assertSame('2500', BookingPriceSchedule::price($result, 'price', '2026-10-31'));
        $this->assertSame('3000', BookingPriceSchedule::price($result, 'price', '2026-11-01'));
        $this->assertNull(BookingPriceSchedule::price($result, 'price', '2026-12-01'));

        $cleared = BookingPriceSchedule::hydrate($saved['branches'][0]);
        $cleared['_booking_price_editor']['price']['action'] = 'clear';
        $cleared['_booking_price_editor']['price']['amount'] = null;
        $city->branches = [BookingPriceSchedule::save($cleared, $saved['branches'][0])];
        $result = app(BookingBranchEnrichmentService::class)->enrichBranches([
            ['external_id' => 'branch-1', 'price' => '2500'],
        ], $city)[0];
        $this->assertNull(BookingPriceSchedule::price($result, 'price', '2026-10-31'));
    }

    public function test_managed_prices_are_transmitted_and_do_not_fall_back_to_external_prices(): void
    {
        $city = new City(['name' => 'Москва', 'branches' => [[
            'external_id' => 'branch-1',
            'price' => '',
            'price_child' => '',
            'price_periods' => [
                'price' => [],
                'price_child' => [['price' => '1500', 'starts_on' => '2026-11-01', 'ends_on' => null]],
            ],
        ]]]);

        $result = app(BookingBranchEnrichmentService::class)->enrichBranches([
            ['external_id' => 'branch-1', 'price' => '9999', 'price_child' => '8888'],
        ], $city);

        $this->assertNull($result[0]['price']);
        $this->assertNull($result[0]['price_child']);
        $this->assertSame([], $result[0]['price_periods']['price']);
        $this->assertSame('1500', $result[0]['price_periods']['price_child'][0]['price']);
    }

    public function test_it_enriches_branches_by_external_id_and_keeps_api_fallbacks(): void
    {
        $city = City::query()->create([
            'name' => 'Москва',
            'slug' => 'moskva',
            'is_default' => true,
            'active' => true,
            'branches' => [
                [
                    'name' => 'Филиал 1',
                    'external_id' => 'branch-1',
                    'address' => 'Адрес из админки',
                    'metro' => 'ВДНХ',
                    'price' => '1800',
                    'price_child' => '1200',
                ],
                [
                    'name' => 'Филиал 2',
                    'external_id' => 'branch-2',
                    'address' => '',
                    'metro' => 'Алексеевская',
                    'price' => '',
                    'price_child' => '',
                ],
            ],
        ]);

        $service = app(BookingBranchEnrichmentService::class);

        $result = $service->enrichBranches([
            [
                'id' => 101,
                'external_id' => 'BRANCH-1',
                'address' => 'Адрес из API',
                'metro' => 'Метро из API',
                'price' => '2500',
                'price_child' => '2000',
            ],
            [
                'id' => 102,
                'external_id' => 'branch-2',
                'address' => 'Адрес из API 2',
                'metro' => 'Метро из API 2',
                'price' => '2600',
                'price_child' => '2100',
            ],
            [
                'id' => 103,
                'external_id' => 'branch-3',
                'address' => 'Адрес из API 3',
                'metro' => 'Метро из API 3',
            ],
        ], $city);

        $this->assertSame('Адрес из админки', $result[0]['address']);
        $this->assertSame('ВДНХ', $result[0]['metro']);
        $this->assertSame('1800', $result[0]['price']);
        $this->assertSame('1200', $result[0]['price_child']);
        $this->assertSame('Москва', $result[0]['city']);

        $this->assertSame('Адрес из API 2', $result[1]['address']);
        $this->assertSame('Алексеевская', $result[1]['metro']);
        $this->assertSame('2600', $result[1]['price']);
        $this->assertSame('2100', $result[1]['price_child']);
        $this->assertSame('Москва', $result[1]['city']);

        $this->assertSame('Адрес из API 3', $result[2]['address']);
        $this->assertSame('Метро из API 3', $result[2]['metro']);
        $this->assertSame('Москва', $result[2]['city']);
    }

    public function test_it_falls_back_to_address_matching_when_api_external_id_is_missing(): void
    {
        $city = City::query()->create([
            'name' => 'Москва',
            'slug' => 'moskva',
            'is_default' => true,
            'active' => true,
            'branches' => [
                [
                    'name' => 'ВДНХ',
                    'external_id' => 'b49501fe-9b0f-11ed-b893-ac1f6bf62dc1',
                    'address' => 'ул. Сергея Эйзенштейна, д. 6',
                    'metro' => 'ВДНХ',
                ],
            ],
        ]);

        $service = app(BookingBranchEnrichmentService::class);

        $result = $service->enrichBranches([
            [
                'id' => 8,
                'name' => 'Ангелы зрения - Эйзенштейна 6',
                'address' => 'Эйзенштейна 6',
                'phone' => null,
            ],
        ], $city);

        $this->assertSame('ул. Сергея Эйзенштейна, д. 6', $result[0]['address']);
        $this->assertSame('ВДНХ', $result[0]['metro']);
        $this->assertSame('Москва', $result[0]['city']);
    }

    public function test_it_prioritizes_external_id_over_fallback_matching(): void
    {
        $city = City::query()->create([
            'name' => 'Москва',
            'slug' => 'moskva',
            'is_default' => true,
            'active' => true,
            'branches' => [
                [
                    'name' => 'Нужный филиал',
                    'external_id' => 'branch-8',
                    'address' => 'ул. Сергея Эйзенштейна, д. 6',
                    'metro' => 'ВДНХ',
                ],
                [
                    'name' => 'Похожий по адресу филиал',
                    'external_id' => 'branch-18',
                    'address' => 'Эйзенштейна 6',
                    'metro' => 'Митино',
                ],
            ],
        ]);

        $service = app(BookingBranchEnrichmentService::class);

        $result = $service->enrichBranches([
            [
                'id' => 8,
                'external_id' => 'branch-8',
                'name' => 'Ангелы зрения - Эйзенштейна 6',
                'address' => 'Эйзенштейна 6',
                'phone' => null,
            ],
        ], $city);

        $this->assertSame('ул. Сергея Эйзенштейна, д. 6', $result[0]['address']);
        $this->assertSame('ВДНХ', $result[0]['metro']);
    }
}
