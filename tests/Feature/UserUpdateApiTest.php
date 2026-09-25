<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserUpdateApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('zrenie-clinic.lo_token', 'test-lo-token');
    }

    public function test_unknown_user_uid_returns_not_found_without_creating_a_user(): void
    {
        $this->withHeader('X-LO-Token', 'test-lo-token')
            ->putJson('/api/user', [
                'uid' => 999999,
                'contact' => 'a2e6c14f-82ee-49b8-a247-47ddd3e59a63',
            ])
            ->assertNotFound()
            ->assertJsonPath('message', 'Record not found.');

        $this->assertSame(0, User::query()->count());
    }

    public function test_existing_user_is_updated_with_the_existing_empty_success_response(): void
    {
        $user = User::factory()->create();
        $contact = 'b939a16a-2874-4b71-84a9-6539d3f8a248';

        $this->withHeader('X-LO-Token', 'test-lo-token')
            ->putJson('/api/user', [
                'uid' => $user->id,
                'contact' => $contact,
            ])
            ->assertOk()
            ->assertExactJson([]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'uuid' => $contact,
        ]);
    }

    public function test_update_requires_the_configured_token(): void
    {
        $user = User::factory()->create();

        $this->putJson('/api/user', [
            'uid' => $user->id,
            'contact' => '01f2d2a5-92a7-491c-9fa4-79af891c9c3a',
        ])
            ->assertForbidden();

        $this->withHeader('X-LO-Token', 'wrong-token')
            ->putJson('/api/user', [
                'uid' => $user->id,
                'contact' => '01f2d2a5-92a7-491c-9fa4-79af891c9c3a',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'uuid' => null,
        ]);
    }

    public function test_update_rejects_an_invalid_contact_without_mutating_the_user(): void
    {
        $user = User::factory()->create();

        $this->withHeader('X-LO-Token', 'test-lo-token')
            ->putJson('/api/user', [
                'uid' => $user->id,
                'contact' => 'not-a-uuid',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('contact');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'uuid' => null,
        ]);
    }
}
