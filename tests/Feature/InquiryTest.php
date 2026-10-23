<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\Stone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryTest extends TestCase
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

    public function test_buyer_can_send_an_inquiry_with_an_offer(): void
    {
        $this->actingAs($this->buyer)
            ->post("/stones/{$this->stone->id}/inquiries", ['offered_price' => 4200, 'message' => 'Can you do 4200?'])
            ->assertRedirect('/inquiries');

        $this->assertDatabaseHas('inquiries', [
            'stone_id' => $this->stone->id,
            'buyer_id' => $this->buyer->id,
            'offered_price' => 4200,
            'status' => 'open',
        ]);
    }

    public function test_buyer_cannot_have_two_open_inquiries_on_one_stone(): void
    {
        Inquiry::factory()->create(['stone_id' => $this->stone->id, 'buyer_id' => $this->buyer->id]);

        $this->actingAs($this->buyer)
            ->post("/stones/{$this->stone->id}/inquiries", ['message' => 'again'])
            ->assertSessionHasErrors('message');

        $this->assertDatabaseCount('inquiries', 1);
    }

    public function test_cannot_inquire_about_a_stone_that_is_not_available(): void
    {
        $this->stone->update(['status' => Stone::STATUS_ON_HOLD]);

        $this->actingAs($this->buyer)
            ->post("/stones/{$this->stone->id}/inquiries", [])
            ->assertNotFound();
    }

    public function test_sellers_cannot_send_inquiries(): void
    {
        $this->actingAs($this->seller)
            ->post("/stones/{$this->stone->id}/inquiries", [])
            ->assertForbidden();
    }

    public function test_seller_can_decline_an_inquiry(): void
    {
        $inquiry = Inquiry::factory()->create(['stone_id' => $this->stone->id]);

        $this->actingAs($this->seller)->post("/inquiries/{$inquiry->id}/decline")->assertRedirect();

        $this->assertSame('declined', $inquiry->fresh()->status);
        $this->assertNotNull($inquiry->fresh()->responded_at);
    }

    public function test_only_the_stones_seller_can_respond(): void
    {
        $inquiry = Inquiry::factory()->create(['stone_id' => $this->stone->id]);
        $stranger = User::factory()->seller()->create();

        $this->actingAs($stranger)->post("/inquiries/{$inquiry->id}/accept")->assertForbidden();
        $this->actingAs($stranger)->post("/inquiries/{$inquiry->id}/decline")->assertForbidden();
        $this->actingAs($this->buyer)->post("/inquiries/{$inquiry->id}/accept")->assertForbidden();
    }

    public function test_buyer_can_withdraw_only_their_own_open_inquiry(): void
    {
        $inquiry = Inquiry::factory()->create(['stone_id' => $this->stone->id, 'buyer_id' => $this->buyer->id]);
        $otherBuyer = User::factory()->buyer()->create();

        $this->actingAs($otherBuyer)->post("/inquiries/{$inquiry->id}/withdraw")->assertForbidden();
        $this->actingAs($this->buyer)->post("/inquiries/{$inquiry->id}/withdraw")->assertRedirect();

        $this->assertSame('withdrawn', $inquiry->fresh()->status);
        $this->actingAs($this->buyer)->post("/inquiries/{$inquiry->id}/withdraw")->assertForbidden();
    }

    public function test_each_side_only_sees_their_own_inquiries(): void
    {
        $mine = Inquiry::factory()->create(['stone_id' => $this->stone->id, 'buyer_id' => $this->buyer->id]);
        $theirs = Inquiry::factory()->create();

        $this->assertSame([$mine->id], $this->actingAs($this->buyer)->get('/inquiries')->viewData('inquiries')->pluck('id')->all());
        $this->assertSame([$mine->id], $this->actingAs($this->seller)->get('/inquiries')->viewData('inquiries')->pluck('id')->all());
    }
}
