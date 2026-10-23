<?php

namespace Tests\Feature\Api;

use App\Models\Inquiry;
use App\Models\Order;
use App\Models\Stone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TradeApiTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;
    private User $seller;
    private Stone $stone;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buyer = User::factory()->buyer()->create();
        $this->seller = User::factory()->seller()->create();
        $this->stone = Stone::factory()->for($this->seller, 'seller')->create(['carat' => 1, 'price_per_carat' => 5000]);
    }

    public function test_full_trade_over_the_api(): void
    {
        Sanctum::actingAs($this->buyer);
        $inquiryId = $this->postJson("/api/v1/stones/{$this->stone->id}/inquiries", [
            'offered_price' => 4500,
            'message' => 'Best price?',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.offered_price', 4500)
            ->json('data.id');

        Sanctum::actingAs($this->seller);
        $orderId = $this->postJson("/api/v1/inquiries/{$inquiryId}/accept")
            ->assertCreated()
            ->json('order_id');

        $this->postJson("/api/v1/orders/{$orderId}/confirm")
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.available_actions', ['ship', 'cancel']);

        $this->postJson("/api/v1/orders/{$orderId}/ship")->assertOk();

        Sanctum::actingAs($this->buyer);
        $this->getJson("/api/v1/orders/{$orderId}")
            ->assertOk()
            ->assertJsonPath('data.agreed_price', 4500)
            ->assertJsonPath('data.available_actions', ['complete']);

        $this->postJson("/api/v1/orders/{$orderId}/complete")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.available_actions', []);

        $this->assertSame('sold', $this->stone->fresh()->status);
    }

    public function test_inquiry_validation_and_duplicates_return_422(): void
    {
        Sanctum::actingAs($this->buyer);

        $this->postJson("/api/v1/stones/{$this->stone->id}/inquiries", ['offered_price' => 'cheap'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('offered_price');

        $this->postJson("/api/v1/stones/{$this->stone->id}/inquiries", [])->assertCreated();
        $this->postJson("/api/v1/stones/{$this->stone->id}/inquiries", [])
            ->assertStatus(422)
            ->assertJsonPath('message', 'You already have an open inquiry on this stone.');
    }

    public function test_wrong_party_gets_403_on_inquiries_and_orders(): void
    {
        $inquiry = Inquiry::factory()->create(['stone_id' => $this->stone->id, 'buyer_id' => $this->buyer->id]);
        $stranger = User::factory()->seller()->create();

        Sanctum::actingAs($stranger);
        $this->postJson("/api/v1/inquiries/{$inquiry->id}/accept")->assertForbidden();

        Sanctum::actingAs($this->buyer);
        $this->postJson("/api/v1/inquiries/{$inquiry->id}/accept")->assertForbidden();

        $this->stone->update(['status' => Stone::STATUS_ON_HOLD]);
        $order = Order::factory()->create([
            'stone_id' => $this->stone->id,
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
        ]);

        $this->postJson("/api/v1/orders/{$order->id}/confirm")->assertForbidden();

        Sanctum::actingAs($stranger);
        $this->getJson("/api/v1/orders/{$order->id}")->assertForbidden();
    }

    public function test_unapproved_seller_gets_a_json_403_instead_of_a_redirect(): void
    {
        $inquiry = Inquiry::factory()->create(['stone_id' => $this->stone->id]);
        $this->seller->update(['is_approved' => false]);

        Sanctum::actingAs($this->seller);

        $this->postJson("/api/v1/inquiries/{$inquiry->id}/accept")
            ->assertForbidden()
            ->assertJsonPath('message', 'Your account is still waiting for approval.');
    }

    public function test_lists_only_return_the_users_own_records(): void
    {
        $mine = Inquiry::factory()->create(['stone_id' => $this->stone->id, 'buyer_id' => $this->buyer->id]);
        Inquiry::factory()->create();

        Sanctum::actingAs($this->buyer);

        $this->getJson('/api/v1/inquiries')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id);
    }

    public function test_unknown_order_action_is_a_404(): void
    {
        $this->stone->update(['status' => Stone::STATUS_ON_HOLD]);
        $order = Order::factory()->create([
            'stone_id' => $this->stone->id,
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
        ]);

        Sanctum::actingAs($this->seller);

        $this->postJson("/api/v1/orders/{$order->id}/refund")->assertNotFound();
    }
}
