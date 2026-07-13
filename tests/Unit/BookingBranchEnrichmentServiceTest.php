<?php

namespace Tests\Unit;

use App\Models\City;
use App\Services\BookingBranchEnrichmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingBranchEnrichmentServiceTest extends TestCase
{
    use RefreshDatabase;

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
