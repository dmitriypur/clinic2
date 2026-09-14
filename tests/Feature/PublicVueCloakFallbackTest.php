<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicVueCloakFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_content_is_not_cloaked_when_vue_cannot_mount(): void
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

        $response = $this->get('/')->assertOk();
        $html = $response->getContent();

        $this->assertMatchesRegularExpression('/<div id="app"[^>]*>/', $html);
        $this->assertDoesNotMatchRegularExpression('/<div id="app"[^>]*\bv-cloak\b[^>]*>/', $html);
        $response->assertSee('Главная');

        $this->assertMatchesRegularExpression(
            '/<button(?=[^>]*\bv-cloak\b)(?=[^>]*v-show="showToTopButton")[^>]*>/',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/<cookie-toast(?=[^>]*\bv-cloak\b)[^>]*>/',
            $html,
        );
    }
}
