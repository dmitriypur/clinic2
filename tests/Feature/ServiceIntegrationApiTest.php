<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Service;
use App\Models\ServicePrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceIntegrationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services-integration.token', 'test-token');
        config()->set('services-integration.allowed_ips', []);
        config()->set('services-integration.default_city_slug', null);
    }

    public function test_tree_endpoint_requires_token(): void
    {
        $this->getJson('/api/integrations/services/tree')
            ->assertUnauthorized();
    }

    public function test_tree_endpoint_returns_services_children_and_prices(): void
    {
        $city = City::create([
            'name' => 'Симферополь',
            'slug' => 'simferopol',
            'is_default' => true,
            'active' => true,
        ]);

        $parent = Service::create([
            'title' => 'Лазерная коррекция',
            'uuid' => '00000000-0000-0000-0000-000000000001',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        $parent->cities()->sync([$city->id]);

        $child = Service::create([
            'title' => 'SMILE',
            'uuid' => '00000000-0000-0000-0000-000000000002',
            'parent_id' => $parent->id,
            'sort_order' => 20,
            'is_active' => true,
        ]);

        ServicePrice::create([
            'service_id' => $child->id,
            'city_id' => null,
            'price' => 25000,
            'old_price' => 30000,
            'price_from' => false,
        ]);

        $this->withToken('test-token')
            ->getJson('/api/integrations/services/tree')
            ->assertOk()
            ->assertJsonPath('services.0.uuid', $parent->uuid)
            ->assertJsonPath('services.0.available_in_all_cities', false)
            ->assertJsonPath('services.0.children.0.uuid', $child->uuid)
            ->assertJsonPath('services.0.children.0.available_in_all_cities', true)
            ->assertJsonPath('services.0.children.0.prices.0.price', 25000)
            ->assertJsonPath('services.0.children.0.prices.0.available_in_all_cities', true)
            ->assertJsonPath('services.0.children.0.prices.0.old_price', 30000)
            ->assertJsonPath('cities.0.slug', 'simferopol');
    }

    public function test_tree_endpoint_accepts_city_slug_parameter(): void
    {
        City::create([
            'name' => 'Москва',
            'slug' => 'moskva',
            'is_default' => true,
            'active' => true,
        ]);

        City::create([
            'name' => 'Киров',
            'slug' => 'kirov',
            'is_default' => false,
            'active' => true,
        ]);

        $this->withToken('test-token')
            ->getJson('/api/integrations/services/tree?city_slug=kirov')
            ->assertOk()
            ->assertJsonPath('cities.0.slug', 'kirov');
    }

    public function test_tree_endpoint_uses_configured_default_city_slug(): void
    {
        City::create([
            'name' => 'Москва',
            'slug' => 'moskva',
            'is_default' => false,
            'active' => true,
        ]);

        City::create([
            'name' => 'Киров',
            'slug' => 'kirov',
            'is_default' => true,
            'active' => true,
        ]);

        config()->set('services-integration.default_city_slug', 'moskva');

        $this->withToken('test-token')
            ->getJson('/api/integrations/services/tree')
            ->assertOk()
            ->assertJsonPath('cities.0.slug', 'kirov');
    }

    public function test_parents_endpoint_returns_only_parent_services_summary(): void
    {
        $parent = Service::create([
            'title' => 'Лазерная коррекция',
            'uuid' => '00000000-0000-0000-0000-000000000003',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        Service::create([
            'title' => 'SMILE',
            'uuid' => '00000000-0000-0000-0000-000000000004',
            'parent_id' => $parent->id,
            'sort_order' => 20,
            'is_active' => true,
        ]);

        $this->withToken('test-token')
            ->getJson('/api/integrations/services/parents')
            ->assertOk()
            ->assertJsonCount(1, 'services')
            ->assertJsonPath('services.0.title', 'Лазерная коррекция')
            ->assertJsonPath('services.0.available_in_all_cities', true)
            ->assertJsonPath('services.0.children_count', 1);
    }

    public function test_search_endpoint_finds_parent_and_child_services(): void
    {
        $city = City::create([
            'name' => 'Москва',
            'slug' => 'moskva',
            'is_default' => true,
            'active' => true,
        ]);

        $parent = Service::create([
            'title' => 'Лазерная коррекция',
            'uuid' => '00000000-0000-0000-0000-000000000011',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        $parent->cities()->sync([$city->id]);

        $child = Service::create([
            'title' => 'SMILE',
            'uuid' => '00000000-0000-0000-0000-000000000012',
            'parent_id' => $parent->id,
            'sort_order' => 20,
            'is_active' => true,
        ]);
        $child->cities()->sync([$city->id]);

        $this->withToken('test-token')
            ->getJson('/api/integrations/services/search?q=SMI')
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.level', 'child')
            ->assertJsonPath('results.0.parent_uuid', $parent->uuid)
            ->assertJsonPath('results.0.city_slugs.0', 'moskva')
            ->assertJsonPath('results.0.available_in_all_cities', false);
    }

    public function test_children_endpoint_returns_compact_children_and_prices(): void
    {
        $parent = Service::create([
            'title' => 'Ночные линзы',
            'uuid' => '00000000-0000-0000-0000-000000000013',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $child = Service::create([
            'title' => 'Баннер',
            'uuid' => '00000000-0000-0000-0000-000000000014',
            'parent_id' => $parent->id,
            'sort_order' => 20,
            'is_active' => true,
        ]);

        ServicePrice::create([
            'service_id' => $child->id,
            'city_id' => null,
            'price' => 1500,
            'old_price' => 2300,
            'price_from' => false,
        ]);

        $this->withToken('test-token')
            ->getJson("/api/integrations/services/{$parent->uuid}/children")
            ->assertOk()
            ->assertJsonPath('service.title', 'Ночные линзы')
            ->assertJsonPath('children.0.title', 'Баннер')
            ->assertJsonPath('children.0.available_in_all_cities', true)
            ->assertJsonPath('children.0.prices.0.available_in_all_cities', true)
            ->assertJsonPath('children.0.prices.0.price', 1500);
    }

    public function test_children_by_title_endpoint_returns_all_children_for_parent_title(): void
    {
        $parent = Service::create([
            'title' => 'Ночные линзы',
            'uuid' => '00000000-0000-0000-0000-000000000015',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        foreach (['Первая', 'Вторая', 'Третья'] as $index => $title) {
            Service::create([
                'title' => $title,
                'uuid' => sprintf('00000000-0000-0000-0000-00000000001%d', $index + 6),
                'parent_id' => $parent->id,
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }

        $this->withToken('test-token')
            ->getJson('/api/integrations/services/children-by-title?q=Ночные%20линзы')
            ->assertOk()
            ->assertJsonPath('service.title', 'Ночные линзы')
            ->assertJsonCount(3, 'children');
    }

    public function test_apply_endpoint_supports_dry_run_with_refs_without_persisting_changes(): void
    {
        $parent = Service::create([
            'title' => 'Лазерная коррекция',
            'uuid' => '00000000-0000-0000-0000-000000000021',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $payload = [
            'dry_run' => true,
            'operations' => [
                [
                    'type' => 'create_service',
                    'ref' => 'new_child',
                    'parent_uuid' => $parent->uuid,
                    'title' => 'SMILE',
                    'sort_order' => 30,
                ],
                [
                    'type' => 'upsert_price',
                    'service_ref' => 'new_child',
                    'price' => 25000,
                    'old_price' => 30000,
                    'price_from' => false,
                ],
            ],
        ];

        $this->withToken('test-token')
            ->postJson('/api/integrations/services/apply', $payload)
            ->assertOk()
            ->assertJsonPath('dry_run', true)
            ->assertJsonPath('results.0.status', 'created')
            ->assertJsonPath('results.1.price.price', 25000)
            ->assertJsonStructure([
                'refs' => ['new_child'],
            ]);

        $this->assertDatabaseCount('services', 1);
        $this->assertDatabaseCount('service_prices', 0);
    }

    public function test_preview_endpoint_returns_compact_dry_run_response(): void
    {
        $parent = Service::create([
            'title' => 'Ночные линзы',
            'uuid' => '00000000-0000-0000-0000-000000000022',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $payload = [
            'operations' => [
                [
                    'type' => 'create_service',
                    'ref' => 'new_child',
                    'parent_uuid' => $parent->uuid,
                    'title' => 'Баннер',
                ],
                [
                    'type' => 'upsert_price',
                    'service_ref' => 'new_child',
                    'price' => 1500,
                    'old_price' => 2300,
                ],
            ],
        ];

        $this->withToken('test-token')
            ->postJson('/api/integrations/services/preview', $payload)
            ->assertOk()
            ->assertJsonPath('dry_run', true)
            ->assertJsonPath('results.0.service.title', 'Баннер')
            ->assertJsonMissingPath('results.0.service.children');
    }

    public function test_apply_endpoint_creates_updates_and_deletes_services_and_prices(): void
    {
        $city = City::create([
            'name' => 'Симферополь',
            'slug' => 'simferopol',
            'is_default' => true,
            'active' => true,
        ]);

        $parent = Service::create([
            'title' => 'Лазерная коррекция',
            'uuid' => '00000000-0000-0000-0000-000000000031',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $child = Service::create([
            'title' => 'Femto LASIK',
            'uuid' => '00000000-0000-0000-0000-000000000032',
            'parent_id' => $parent->id,
            'sort_order' => 20,
            'is_active' => true,
        ]);

        ServicePrice::create([
            'service_id' => $child->id,
            'city_id' => null,
            'price' => 20000,
            'old_price' => null,
            'price_from' => false,
        ]);

        $payload = [
            'operations' => [
                [
                    'type' => 'update_service',
                    'service_uuid' => $child->uuid,
                    'title' => 'SMILE',
                    'city_slugs' => [$city->slug],
                ],
                [
                    'type' => 'upsert_price',
                    'service_uuid' => $child->uuid,
                    'city_slug' => null,
                    'price' => 25000,
                    'old_price' => 30000,
                    'price_from' => false,
                ],
                [
                    'type' => 'delete_price',
                    'service_uuid' => $child->uuid,
                    'city_slug' => null,
                ],
                [
                    'type' => 'create_service',
                    'ref' => 'new_root',
                    'title' => 'Катаракта',
                    'sort_order' => 40,
                ],
                [
                    'type' => 'create_service',
                    'parent_ref' => 'new_root',
                    'title' => 'Факоэмульсификация',
                    'sort_order' => 10,
                ],
                [
                    'type' => 'delete_service',
                    'service_uuid' => $parent->uuid,
                    'cascade_children' => true,
                ],
            ],
        ];

        $this->withToken('test-token')
            ->postJson('/api/integrations/services/apply', $payload)
            ->assertOk()
            ->assertJsonPath('results.0.service.title', 'SMILE')
            ->assertJsonMissingPath('results.0.service.children')
            ->assertJsonPath('results.3.service.title', 'Катаракта')
            ->assertJsonPath('results.5.status', 'deleted');

        $this->assertDatabaseMissing('services', ['uuid' => $parent->uuid]);
        $this->assertDatabaseMissing('services', ['uuid' => $child->uuid]);
        $this->assertDatabaseHas('services', ['title' => 'Катаракта', 'parent_id' => null]);
        $this->assertDatabaseHas('services', ['title' => 'Факоэмульсификация']);
    }
}
