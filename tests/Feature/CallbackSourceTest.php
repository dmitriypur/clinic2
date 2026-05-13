<?php

namespace Tests\Feature;

use App\Clinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CallbackSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_callback_sends_source_to_clinic_payload(): void
    {
        config()->set('zrenie-clinic.base_url', 'https://unf.test/');

        Http::fake([
            'https://unf.test/events?action=callrequest' => Http::response([], 200),
        ]);

        Clinic::setHttp();

        User::query()->create([
            'name' => 'Old Name',
            'phone' => '+79999999999',
            'password' => Hash::make('password'),
        ]);

        $this->postJson('/api/callback', [
            'name' => 'Test',
            'phone' => '+7 (999) 999-99-99',
            'city' => 'Москва',
            'privacy' => true,
            'source' => 'vk_mini_app',
            'type' => 'callback_form',
        ])->assertOk();

        Http::assertSent(fn($request) => $request->url() === 'https://unf.test/events?action=callrequest'
            && $request['source'] === 'vk_mini_app'
            && $request['type'] === 'callback_form'
            && $request['name'] === 'Test'
        );
    }

    public function test_callback_falls_back_to_site_source_for_legacy_requests(): void
    {
        config()->set('zrenie-clinic.base_url', 'https://unf.test/');

        Http::fake([
            'https://unf.test/events?action=callrequest' => Http::response([], 200),
        ]);

        Clinic::setHttp();

        User::query()->create([
            'name' => 'Old Name',
            'phone' => '+79999999999',
            'password' => Hash::make('password'),
        ]);

        $this->postJson('/api/callback', [
            'name' => 'Test',
            'phone' => '+7 (999) 999-99-99',
            'city' => 'Москва',
            'privacy' => true,
        ])->assertOk();

        Http::assertSent(fn($request) => $request->url() === 'https://unf.test/events?action=callrequest'
            && $request['source'] === 'site'
        );
    }
}
