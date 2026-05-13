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
            'https://unf.test/events?action=callback' => Http::response([
                'response' => 'ok',
                'uid_request' => '550e8400-e29b-41d4-a716-446655440000',
            ], 200),
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
            'type' => 'Заявка на звонок',
        ])
            ->assertOk()
            ->assertJson([
                'response' => 'ok',
                'uid_request' => '550e8400-e29b-41d4-a716-446655440000',
            ]);

        Http::assertSent(fn($request) => $request->url() === 'https://unf.test/events?action=callback'
            && $request['source'] === 'vk_mini_app'
            && $request['type'] === 'Заявка на звонок'
            && $request['name'] === 'Test'
        );
    }

    public function test_callback_falls_back_to_site_source_for_legacy_requests(): void
    {
        config()->set('zrenie-clinic.base_url', 'https://unf.test/');

        Http::fake([
            'https://unf.test/events?action=callback' => Http::response([
                'response' => 'ok',
                'uid_request' => '550e8400-e29b-41d4-a716-446655440000',
            ], 200),
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

        Http::assertSent(fn($request) => $request->url() === 'https://unf.test/events?action=callback'
            && $request['source'] === 'site'
        );
    }

    public function test_callback_accepts_legacy_type_and_normalizes_it_for_clinic_payload(): void
    {
        config()->set('zrenie-clinic.base_url', 'https://unf.test/');

        Http::fake([
            'https://unf.test/events?action=callback' => Http::response([
                'response' => 'ok',
                'uid_request' => '550e8400-e29b-41d4-a716-446655440000',
            ], 200),
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
            'source' => 'site',
            'type' => 'callback_form',
        ])->assertOk();

        Http::assertSent(fn($request) => $request->url() === 'https://unf.test/events?action=callback'
            && $request['type'] === 'Заявка на звонок'
        );
    }

    public function test_callback_maps_invalid_phone_response_to_validation_error(): void
    {
        $this->fakeClinicCallback(['fail' => 'InvalidPhone']);

        $this->postJson('/api/callback', $this->callbackPayload())
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Проверьте номер телефона',
                'fail' => 'InvalidPhone',
                'errors' => [
                    'phone' => ['Проверьте номер телефона'],
                ],
            ]);
    }

    public function test_callback_maps_too_frequent_response_to_soft_error(): void
    {
        $this->fakeClinicCallback(['fail' => 'TooFrequent']);

        $this->postJson('/api/callback', $this->callbackPayload())
            ->assertStatus(429)
            ->assertJson([
                'message' => 'Заявка уже отправлена. Попробуйте повторить чуть позже.',
                'fail' => 'TooFrequent',
            ]);
    }

    /**
     * @dataProvider technicalFailureProvider
     */
    public function test_callback_maps_technical_failures_to_common_error(string $fail): void
    {
        $this->fakeClinicCallback(['fail' => $fail]);

        $this->postJson('/api/callback', $this->callbackPayload())
            ->assertStatus(502)
            ->assertJson([
                'message' => 'Не удалось отправить заявку. Попробуйте позже.',
                'fail' => $fail,
            ]);
    }

    public function test_callback_maps_unexpected_response_to_common_error(): void
    {
        $this->fakeClinicCallback([]);

        $this->postJson('/api/callback', $this->callbackPayload())
            ->assertStatus(502)
            ->assertJson([
                'message' => 'Не удалось отправить заявку. Попробуйте позже.',
                'fail' => 'UnexpectedResponse',
            ]);
    }

    public function test_callback_maps_non_json_response_to_common_error(): void
    {
        $this->fakeClinicCallback('not-json');

        $this->postJson('/api/callback', $this->callbackPayload())
            ->assertStatus(502)
            ->assertJson([
                'message' => 'Не удалось отправить заявку. Попробуйте позже.',
                'fail' => 'UnexpectedResponse',
            ]);
    }

    public static function technicalFailureProvider(): array
    {
        return [
            'bad request' => ['BadRequest'],
            'no auth' => ['NoAuth'],
            'internal' => ['Internal'],
        ];
    }

    private function fakeClinicCallback(mixed $response): void
    {
        config()->set('zrenie-clinic.base_url', 'https://unf.test/');

        Http::fake([
            'https://unf.test/events?action=callback' => Http::response($response, 200),
        ]);

        Clinic::setHttp();

        User::query()->create([
            'name' => 'Old Name',
            'phone' => '+79999999999',
            'password' => Hash::make('password'),
        ]);
    }

    private function callbackPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test',
            'phone' => '+7 (999) 999-99-99',
            'city' => 'Москва',
            'privacy' => true,
            'source' => 'site',
            'type' => 'Заявка на звонок',
        ], $overrides);
    }
}
