<?php

namespace Tests\Feature;

use App\Models\Stone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SellerStoneTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'shape' => 'Oval',
            'carat' => 1.25,
            'color' => 'F',
            'clarity' => 'VS1',
            'price_per_carat' => 5000,
            'lab' => 'GIA',
        ], $overrides);
    }

    public function test_approved_seller_can_list_a_stone(): void
    {
        Storage::fake('public');
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller)
            ->post('/seller/stones', $this->payload(['image' => UploadedFile::fake()->image('stone.jpg')]))
            ->assertRedirect('/seller/stones');

        $stone = $seller->stones()->first();

        $this->assertNotNull($stone);
        $this->assertStringStartsWith('GL-', $stone->sku);
        $this->assertEquals(6250, $stone->total_price);
        Storage::disk('public')->assertExists($stone->image_path);
    }

    public function test_total_price_follows_carat_and_rate_on_update(): void
    {
        $seller = User::factory()->seller()->create();
        $stone = Stone::factory()->for($seller, 'seller')->create(['carat' => 1, 'price_per_carat' => 1000]);

        $this->actingAs($seller)
            ->put("/seller/stones/{$stone->id}", $this->payload(['carat' => 2, 'price_per_carat' => 3000]))
            ->assertRedirect('/seller/stones');

        $this->assertEquals(6000, $stone->fresh()->total_price);
    }

    public function test_invalid_stone_is_rejected(): void
    {
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller)
            ->post('/seller/stones', $this->payload(['shape' => 'Blob', 'carat' => -1]))
            ->assertSessionHasErrors(['shape', 'carat']);

        $this->assertDatabaseCount('stones', 0);
    }

    public function test_unapproved_seller_is_kept_out(): void
    {
        $seller = User::factory()->seller()->create(['is_approved' => false]);

        $this->actingAs($seller)->get('/seller/stones')->assertRedirect('/dashboard');
        $this->actingAs($seller)->post('/seller/stones', $this->payload())->assertRedirect('/dashboard');
    }

    public function test_buyers_cannot_reach_seller_pages(): void
    {
        $buyer = User::factory()->buyer()->create();

        $this->actingAs($buyer)->get('/seller/stones')->assertForbidden();
    }

    public function test_seller_cannot_touch_another_sellers_stone(): void
    {
        $owner = User::factory()->seller()->create();
        $other = User::factory()->seller()->create();
        $stone = Stone::factory()->for($owner, 'seller')->create();

        $this->actingAs($other)->get("/seller/stones/{$stone->id}/edit")->assertForbidden();
        $this->actingAs($other)->put("/seller/stones/{$stone->id}", $this->payload())->assertForbidden();
        $this->actingAs($other)->delete("/seller/stones/{$stone->id}")->assertForbidden();
    }

    public function test_sold_stones_cannot_be_deleted(): void
    {
        $seller = User::factory()->seller()->create();
        $stone = Stone::factory()->for($seller, 'seller')->create(['status' => Stone::STATUS_SOLD]);

        $this->actingAs($seller)->delete("/seller/stones/{$stone->id}")->assertForbidden();
    }

    public function test_deleting_a_stone_is_a_soft_delete(): void
    {
        $seller = User::factory()->seller()->create();
        $stone = Stone::factory()->for($seller, 'seller')->create();

        $this->actingAs($seller)->delete("/seller/stones/{$stone->id}")->assertRedirect('/seller/stones');

        $this->assertSoftDeleted($stone);
    }
}
