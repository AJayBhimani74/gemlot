<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSellerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN, 'is_approved' => true]);
    }

    public function test_admin_can_approve_and_revoke_a_seller(): void
    {
        $seller = User::factory()->seller()->create(['is_approved' => false]);

        $this->actingAs($this->admin())->post("/admin/sellers/{$seller->id}/approve")->assertRedirect();
        $this->assertTrue($seller->fresh()->is_approved);

        $this->actingAs($this->admin())->post("/admin/sellers/{$seller->id}/revoke")->assertRedirect();
        $this->assertFalse($seller->fresh()->is_approved);
    }

    public function test_pending_filter_only_lists_unapproved_sellers(): void
    {
        User::factory()->seller()->create(['company_name' => 'Waiting Co', 'is_approved' => false]);
        User::factory()->seller()->create(['company_name' => 'Cleared Co', 'is_approved' => true]);

        $this->actingAs($this->admin())
            ->get('/admin/sellers?filter=pending')
            ->assertSee('Waiting Co')
            ->assertDontSee('Cleared Co');
    }

    public function test_only_admins_can_approve(): void
    {
        $seller = User::factory()->seller()->create(['is_approved' => false]);

        $this->actingAs(User::factory()->buyer()->create())
            ->post("/admin/sellers/{$seller->id}/approve")
            ->assertForbidden();

        $this->actingAs($seller)->post("/admin/sellers/{$seller->id}/approve")->assertForbidden();
        $this->assertFalse($seller->fresh()->is_approved);
    }

    public function test_approve_only_applies_to_sellers(): void
    {
        $buyer = User::factory()->buyer()->create();

        $this->actingAs($this->admin())->post("/admin/sellers/{$buyer->id}/approve")->assertNotFound();
    }
}
