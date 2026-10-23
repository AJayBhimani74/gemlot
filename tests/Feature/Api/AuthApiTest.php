<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_a_token_that_works(): void
    {
        $user = User::factory()->buyer()->create(['password' => 'secret123']);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
            'device_name' => 'pixel-8',
        ])
            ->assertOk()
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.role', 'buyer')
            ->assertJsonMissingPath('user.password')
            ->json('token');

        $this->withToken($token)->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);

        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'pixel-8']);
    }

    public function test_wrong_credentials_get_a_422_and_no_token(): void
    {
        $user = User::factory()->buyer()->create(['password' => 'secret123']);

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_requests_without_a_token_get_a_401_even_without_an_accept_header(): void
    {
        $this->get('/api/v1/me')->assertUnauthorized();
        $this->get('/api/v1/stones')->assertUnauthorized();
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->buyer()->create();
        $phone = $user->createToken('phone')->plainTextToken;
        $user->createToken('tablet');

        $this->withToken($phone)->postJson('/api/v1/auth/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'tablet']);
    }
}
