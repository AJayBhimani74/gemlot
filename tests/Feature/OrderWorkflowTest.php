<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\Order;
use App\Models\Stone;
use App\Models\User;
use App\Services\OrderService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
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

    private function inquiry(?User $buyer = null, ?float $offer = null): Inquiry
    {
        return Inquiry::factory()->create([
            'stone_id' => $this->stone->id,
            'buyer_id' => ($buyer ?? $this->buyer)->id,
            'offered_price' => $offer,
        ]);
    }

    private function order(string $status): Order
    {
        $this->stone->update(['status' => Stone::STATUS_ON_HOLD]);

        return Order::factory()->status($status)->create([
            'stone_id' => $this->stone->id,
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
        ]);
    }

    public function test_accepting_creates_an_order_at_the_offered_price_and_holds_the_stone(): void
    {
        $inquiry = $this->inquiry(offer: 4200);

        $this->actingAs($this->seller)->post("/inquiries/{$inquiry->id}/accept")->assertRedirect();

        $order = Order::first();
        $this->assertEquals(4200, $order->agreed_price);
        $this->assertSame('pending', $order->status);
        $this->assertSame($this->buyer->id, $order->buyer_id);
        $this->assertSame($this->seller->id, $order->seller_id);
        $this->assertStringStartsWith('ORD-', $order->reference);
        $this->assertSame('on_hold', $this->stone->fresh()->status);
        $this->assertSame('accepted', $inquiry->fresh()->status);
    }

    public function test_order_uses_the_list_price_when_no_offer_was_made(): void
    {
        $inquiry = $this->inquiry();

        $this->actingAs($this->seller)->post("/inquiries/{$inquiry->id}/accept");

        $this->assertEquals(5000, Order::first()->agreed_price);
    }

    public function test_accepting_one_inquiry_declines_the_rest(): void
    {
        $winner = $this->inquiry();
        $loser = $this->inquiry(User::factory()->buyer()->create());
        $elsewhere = Inquiry::factory()->create();

        $this->actingAs($this->seller)->post("/inquiries/{$winner->id}/accept");

        $this->assertSame('declined', $loser->fresh()->status);
        $this->assertSame('open', $elsewhere->fresh()->status);
    }

    public function test_an_answered_inquiry_cannot_be_accepted_again(): void
    {
        $inquiry = $this->inquiry();

        $this->actingAs($this->seller)->post("/inquiries/{$inquiry->id}/accept");
        $this->actingAs($this->seller)->post("/inquiries/{$inquiry->id}/accept")->assertForbidden();

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_service_refuses_a_stone_that_is_no_longer_available(): void
    {
        $inquiry = $this->inquiry();
        $this->stone->update(['status' => Stone::STATUS_SOLD]);

        $this->expectException(DomainException::class);

        app(OrderService::class)->acceptInquiry($inquiry);
    }

    public function test_happy_path_from_pending_to_completed(): void
    {
        $order = $this->order(Order::STATUS_PENDING);

        $this->actingAs($this->seller)->post("/orders/{$order->id}/confirm")->assertRedirect();
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->confirmed_at);

        $this->actingAs($this->seller)->post("/orders/{$order->id}/ship")->assertRedirect();
        $this->assertSame('shipped', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->shipped_at);

        $this->actingAs($this->buyer)->post("/orders/{$order->id}/complete")->assertRedirect();
        $this->assertSame('completed', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->completed_at);
        $this->assertSame('sold', $this->stone->fresh()->status);
    }

    public function test_cancelling_releases_the_stone(): void
    {
        $order = $this->order(Order::STATUS_CONFIRMED);

        $this->actingAs($this->buyer)->post("/orders/{$order->id}/cancel")->assertRedirect();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('available', $this->stone->fresh()->status);
    }

    public function test_steps_cannot_be_skipped(): void
    {
        $order = $this->order(Order::STATUS_PENDING);

        $this->actingAs($this->seller)->post("/orders/{$order->id}/ship")->assertForbidden();
        $this->actingAs($this->buyer)->post("/orders/{$order->id}/complete")->assertForbidden();

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_each_step_belongs_to_the_right_party(): void
    {
        $pending = $this->order(Order::STATUS_PENDING);
        $this->actingAs($this->buyer)->post("/orders/{$pending->id}/confirm")->assertForbidden();

        $shipped = $this->order(Order::STATUS_SHIPPED);
        $this->actingAs($this->seller)->post("/orders/{$shipped->id}/complete")->assertForbidden();
    }

    public function test_shipped_and_finished_orders_cannot_be_cancelled(): void
    {
        foreach ([Order::STATUS_SHIPPED, Order::STATUS_COMPLETED, Order::STATUS_CANCELLED] as $status) {
            $order = $this->order($status);

            $this->actingAs($this->buyer)->post("/orders/{$order->id}/cancel")->assertForbidden();
            $this->actingAs($this->seller)->post("/orders/{$order->id}/cancel")->assertForbidden();
        }
    }

    public function test_outsiders_cannot_view_or_change_an_order(): void
    {
        $order = $this->order(Order::STATUS_PENDING);
        $outsider = User::factory()->seller()->create();

        $this->actingAs($outsider)->get("/orders/{$order->id}")->assertForbidden();
        $this->actingAs($outsider)->post("/orders/{$order->id}/cancel")->assertForbidden();
    }

    public function test_admin_can_view_but_not_change_orders(): void
    {
        $order = $this->order(Order::STATUS_PENDING);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_approved' => true]);

        $this->actingAs($admin)->get("/orders/{$order->id}")->assertOk();
        $this->actingAs($admin)->post("/orders/{$order->id}/cancel")->assertForbidden();
    }

    public function test_unknown_action_is_a_404(): void
    {
        $order = $this->order(Order::STATUS_PENDING);

        $this->actingAs($this->seller)->post("/orders/{$order->id}/refund")->assertNotFound();
    }

    public function test_order_list_only_shows_your_own(): void
    {
        $mine = $this->order(Order::STATUS_PENDING);
        Order::factory()->create();

        $ids = $this->actingAs($this->buyer)->get('/orders')->viewData('orders')->pluck('id')->all();

        $this->assertSame([$mine->id], $ids);
    }

    public function test_who_can_do_what_at_every_status(): void
    {
        // Written out by hand on purpose, so it doesn't just mirror Order::ACTIONS.
        $allowed = [
            Order::STATUS_PENDING => ['seller' => ['confirm', 'cancel'], 'buyer' => ['cancel']],
            Order::STATUS_CONFIRMED => ['seller' => ['ship', 'cancel'], 'buyer' => ['cancel']],
            Order::STATUS_SHIPPED => ['seller' => [], 'buyer' => ['complete']],
            Order::STATUS_COMPLETED => ['seller' => [], 'buyer' => []],
            Order::STATUS_CANCELLED => ['seller' => [], 'buyer' => []],
        ];

        foreach ($allowed as $status => $byParty) {
            $order = $this->order($status);

            foreach (['seller' => $this->seller, 'buyer' => $this->buyer] as $party => $user) {
                foreach (array_keys(Order::ACTIONS) as $action) {
                    $expected = in_array($action, $byParty[$party], true);

                    $this->assertSame(
                        $expected,
                        $user->can('transition', [$order, $action]),
                        "{$party} on a {$status} order, action '{$action}'"
                    );
                }
            }
        }
    }

    public function test_accepting_leaves_already_closed_inquiries_alone(): void
    {
        $withdrawn = Inquiry::factory()->create([
            'stone_id' => $this->stone->id,
            'status' => Inquiry::STATUS_WITHDRAWN,
        ]);
        $winner = $this->inquiry();

        $this->actingAs($this->seller)->post("/inquiries/{$winner->id}/accept");

        $this->assertSame('withdrawn', $withdrawn->fresh()->status);
        $this->assertNull($withdrawn->fresh()->responded_at);
    }
}
