<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function registration(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Priya Shah',
            'email' => 'priya@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'buyer',
            'company_name' => 'Shah Jewels',
            'country' => 'in',
        ], $overrides);
    }

    public function test_buyer_can_register_and_is_approved_straight_away(): void
    {
        $this->post('/register', $this->registration())->assertRedirect('/dashboard');

        $user = User::firstWhere('email', 'priya@example.com');

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->is_approved);
        $this->assertSame('IN', $user->country);
    }

    public function test_seller_starts_unapproved(): void
    {
        $this->post('/register', $this->registration(['role' => 'seller']));

        $this->assertFalse(User::firstWhere('email', 'priya@example.com')->is_approved);
    }

    public function test_nobody_can_register_as_admin(): void
    {
        $this->post('/register', $this->registration(['role' => 'admin']))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_needs_a_confirmed_password(): void
    {
        $this->post('/register', $this->registration(['password_confirmation' => 'different']))
            ->assertSessionHasErrors('password');
    }

    public function test_user_can_log_in_and_out(): void
    {
        $user = User::factory()->buyer()->create(['password' => 'secret123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->buyer()->create(['password' => 'secret123']);

        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'nope'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/stones')->assertRedirect('/login');
    }
}
