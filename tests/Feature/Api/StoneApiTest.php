<?php

namespace Tests\Feature\Api;

use App\Models\Stone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StoneApiTest extends TestCase
{
    use RefreshDatabase;

    private User $seller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->seller()->create();
        Sanctum::actingAs(User::factory()->buyer()->create());
    }

    private function stone(array $attrs = []): Stone
    {
        return Stone::factory()->for($this->seller, 'seller')->create($attrs);
    }

    public function test_lists_available_stones_with_seller_details(): void
    {
        $stone = $this->stone();
        $this->stone(['status' => Stone::STATUS_SOLD]);

        $this->getJson('/api/v1/stones')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sku', $stone->sku)
            ->assertJsonPath('data.0.seller.company_name', $this->seller->company_name)
            ->assertJsonStructure([
                'data' => [['id', 'sku', 'shape', 'carat', 'color', 'clarity', 'total_price', 'status']],
                'links',
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    }

    public function test_uses_the_same_filters_and_sorting_as_the_website(): void
    {
        $this->stone(['shape' => 'Round', 'carat' => 1, 'price_per_carat' => 9000]);
        $cheap = $this->stone(['shape' => 'Round', 'carat' => 1, 'price_per_carat' => 1000]);
        $this->stone(['shape' => 'Oval']);

        $this->getJson('/api/v1/stones?shape[]=Round&sort=price_asc')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.sku', $cheap->sku);
    }

    public function test_page_size_is_capped(): void
    {
        $this->stone();

        $this->getJson('/api/v1/stones?per_page=9999')->assertOk()->assertJsonPath('meta.per_page', 50);
    }

    public function test_numbers_come_back_as_numbers(): void
    {
        $this->stone(['carat' => 1.5, 'price_per_carat' => 4000]);

        $stone = $this->getJson('/api/v1/stones')->json('data.0');

        $this->assertIsNotString($stone['carat']);
        $this->assertIsNotString($stone['total_price']);
        $this->assertEquals(1.5, $stone['carat']);
        $this->assertEquals(6000, $stone['total_price']);
    }

    public function test_unavailable_stone_is_a_404(): void
    {
        $sold = $this->stone(['status' => Stone::STATUS_SOLD]);
        $open = $this->stone();

        $this->getJson("/api/v1/stones/{$sold->id}")->assertNotFound();
        $this->getJson("/api/v1/stones/{$open->id}")->assertOk()->assertJsonPath('data.id', $open->id);
    }

    public function test_sellers_cannot_browse_the_buyer_catalogue(): void
    {
        Sanctum::actingAs($this->seller);

        $this->getJson('/api/v1/stones')->assertForbidden();
    }
}
