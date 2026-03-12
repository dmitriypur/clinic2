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
            ->assertJsonPath('services.0.children.0.uuid', $child->uuid)
            ->assertJsonPath('services.0.children.0.prices.0.price', 25000)
            ->assertJsonPath('services.0.children.0.prices.0.old_price', 30000)
            ->assertJsonPath('cities.0.slug', 'simferopol');
    }

    public function test_search_endpoint_finds_parent_and_child_services(): void
    {
        $parent = Service::create([
            'title' => 'Лазерная коррекция',
            'uuid' => '00000000-0000-0000-0000-000000000011',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        Service::create([
            'title' => 'SMILE',
            'uuid' => '00000000-0000-0000-0000-000000000012',
            'parent_id' => $parent->id,
            'sort_order' => 20,
            'is_active' => true,
        ]);

        $this->withToken('test-token')
            ->getJson('/api/integrations/services/search?q=SMI')
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.level', 'child')
            ->assertJsonPath('results.0.parent_uuid', $parent->uuid);
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
            ->assertJsonPath('results.3.service.title', 'Катаракта')
            ->assertJsonPath('results.5.status', 'deleted');

        $this->assertDatabaseMissing('services', ['uuid' => $parent->uuid]);
        $this->assertDatabaseMissing('services', ['uuid' => $child->uuid]);
        $this->assertDatabaseHas('services', ['title' => 'Катаракта', 'parent_id' => null]);
        $this->assertDatabaseHas('services', ['title' => 'Факоэмульсификация']);
    }
}
