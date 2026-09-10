<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        City::query()->create([
            'name' => 'Москва',
            'slug' => 'moskva',
            'is_default' => true,
            'active' => true,
            'details' => [
                [
                    'name' => 'Тестовая клиника',
                    'fullname' => 'ООО «Тестовая клиника»',
                ],
            ],
        ]);
        Page::query()->create([
            'title' => 'Главная',
            'handle' => '/',
            'active' => true,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
