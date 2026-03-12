<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Service;
use App\Models\ServicePrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RunServicesAiAgentCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_builds_dry_run_without_applying_changes(): void
    {
        $parent = Service::create([
            'title' => 'Лазерная коррекция',
            'uuid' => '00000000-0000-0000-0000-000000000101',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $child = Service::create([
            'title' => 'SMILE',
            'uuid' => '00000000-0000-0000-0000-000000000102',
            'parent_id' => $parent->id,
            'sort_order' => 20,
            'is_active' => true,
        ]);

        ServicePrice::create([
            'service_id' => $child->id,
            'city_id' => null,
            'price' => 25000,
            'old_price' => null,
            'price_from' => false,
        ]);

        Http::fake([
            'http://127.0.0.1:11434/api/chat' => Http::response([
                'message' => [
                    'content' => json_encode([
                        'need_clarification' => false,
                        'message' => null,
                        'operations' => [
                            [
                                'type' => 'upsert_price',
                                'service_uuid' => $child->uuid,
                                'city_slug' => null,
                                'price' => 27000,
                                'old_price' => 30000,
                                'price_from' => false,
                            ],
                        ],
                    ], JSON_UNESCAPED_UNICODE),
                ],
            ]),
        ]);

        $this->artisan('app:services-ai-agent', [
            'instruction' => ['Для', 'SMILE', 'сделай', 'цену', '27000', 'и', 'старую', '30000'],
        ])
            ->expectsOutputToContain('Dry-run результат:')
            ->expectsOutputToContain('Изменения не применены')
            ->assertSuccessful();

        $this->assertDatabaseHas('service_prices', [
            'service_id' => $child->id,
            'price' => 25000,
            'old_price' => null,
        ]);
    }

    public function test_command_can_apply_changes_after_confirmation(): void
    {
        $parent = Service::create([
            'title' => 'Лазерная коррекция',
            'uuid' => '00000000-0000-0000-0000-000000000111',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        Http::fake([
            'http://127.0.0.1:11434/api/chat' => Http::response([
                'message' => [
                    'content' => json_encode([
                        'need_clarification' => false,
                        'message' => null,
                        'operations' => [
                            [
                                'type' => 'create_service',
                                'parent_uuid' => $parent->uuid,
                                'title' => 'Femto LASIK',
                                'sort_order' => 30,
                            ],
                        ],
                    ], JSON_UNESCAPED_UNICODE),
                ],
            ]),
        ]);

        $this->artisan('app:services-ai-agent', [
            'instruction' => ['Добавь', 'подуслугу', 'Femto', 'LASIK', 'к', 'лазерной', 'коррекции'],
            '--apply' => true,
        ])
            ->expectsConfirmation('Применить эти изменения в базу?', 'yes')
            ->expectsOutputToContain('Изменения применены:')
            ->assertSuccessful();

        $this->assertDatabaseHas('services', [
            'title' => 'Femto LASIK',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_command_normalizes_nested_ollama_operations_with_ref_placeholders(): void
    {
        Http::fake([
            'http://127.0.0.1:11434/api/chat' => Http::response([
                'message' => [
                    'content' => json_encode([
                        'need_clarification' => false,
                        'message' => null,
                        'operations' => [
                            [
                                'type' => 'create_service',
                                'data' => [
                                    'title' => 'Тестовая услуга ИИ 01',
                                    'parent_uuid' => null,
                                    'is_active' => true,
                                    'sort_order' => 0,
                                    'city_slugs' => [],
                                ],
                            ],
                            [
                                'type' => 'create_service',
                                'data' => [
                                    'title' => 'Тестовая подуслуга ИИ 01',
                                    'parent_uuid' => 'ref',
                                    'is_active' => true,
                                    'sort_order' => 0,
                                    'city_slugs' => [],
                                ],
                            ],
                            [
                                'type' => 'upsert_price',
                                'data' => [
                                    'service_uuid' => 'ref',
                                    'price' => 3900,
                                    'old_price' => 4500,
                                    'city_slug' => null,
                                ],
                            ],
                        ],
                    ], JSON_UNESCAPED_UNICODE),
                ],
            ]),
        ]);

        $this->artisan('app:services-ai-agent', [
            'instruction' => ['Создай', 'новую', 'услугу', 'и', 'подуслугу', 'с', 'ценой'],
        ])
            ->expectsOutputToContain('Dry-run результат:')
            ->assertSuccessful();
    }

    public function test_command_normalizes_hallucinated_refs_and_uuids_from_ollama(): void
    {
        City::create([
            'name' => 'Москва',
            'slug' => 'moskva',
            'is_default' => true,
            'active' => true,
        ]);

        Http::fake([
            'http://127.0.0.1:11434/api/chat' => Http::response([
                'message' => [
                    'content' => json_encode([
                        'need_clarification' => false,
                        'message' => null,
                        'operations' => [
                            [
                                'type' => 'create_service',
                                'service_ref' => 'Тестовая услуга ИИ 02',
                                'parent_ref' => 'ecd5eda0-8364-49e1-8e4b-9d7f657f844c',
                                'city_slugs' => ['moskva'],
                            ],
                            [
                                'type' => 'create_service',
                                'service_ref' => 'Тестовая подуслуга ИИ 02',
                                'parent_ref' => 'Тестовая услуга ИИ 02',
                                'city_slugs' => ['moskva'],
                            ],
                            [
                                'type' => 'upsert_price',
                                'service_uuid' => '9629fdf4-759c-43eb-93a4-6470ea60b34a',
                                'city_slug' => null,
                                'price' => 3900,
                                'old_price' => 4500,
                            ],
                        ],
                    ], JSON_UNESCAPED_UNICODE),
                ],
            ]),
        ]);

        $this->artisan('app:services-ai-agent', [
            'instruction' => ['Создай', 'новую', 'услугу', 'с', 'подуслугой', 'и', 'ценой', 'для', 'Москвы'],
        ])
            ->expectsOutputToContain('Dry-run результат:')
            ->assertSuccessful();
    }
}
