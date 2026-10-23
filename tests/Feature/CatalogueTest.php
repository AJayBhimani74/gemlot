<?php

namespace Tests\Feature;

use App\Models\Stone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogueTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;
    private User $seller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buyer = User::factory()->buyer()->create();
        $this->seller = User::factory()->seller()->create();
    }

    private function stone(array $attrs = [], ?User $seller = null): Stone
    {
        return Stone::factory()->for($seller ?? $this->seller, 'seller')->create($attrs);
    }

    private function skus(array $query = []): array
    {
        $response = $this->actingAs($this->buyer)->get('/stones?'.http_build_query($query));

        return $response->viewData('stones')->pluck('sku')->all();
    }

    public function test_only_available_stones_from_approved_sellers_are_listed(): void
    {
        $visible = $this->stone();
        $this->stone(['status' => Stone::STATUS_SOLD]);
        $this->stone(['status' => Stone::STATUS_ON_HOLD]);
        $this->stone([], User::factory()->seller()->create(['is_approved' => false]));

        $this->assertSame([$visible->sku], $this->skus());
    }

    public function test_filters_by_shape_colour_and_clarity(): void
    {
        $match = $this->stone(['shape' => 'Round', 'color' => 'D', 'clarity' => 'VS1']);
        $this->stone(['shape' => 'Oval', 'color' => 'D', 'clarity' => 'VS1']);
        $this->stone(['shape' => 'Round', 'color' => 'K', 'clarity' => 'VS1']);
        $this->stone(['shape' => 'Round', 'color' => 'D', 'clarity' => 'SI2']);

        $this->assertSame(
            [$match->sku],
            $this->skus(['shape' => ['Round'], 'color' => ['D'], 'clarity' => ['VS1']])
        );
    }

    public function test_multiple_values_for_one_filter_are_or_ed(): void
    {
        $this->stone(['shape' => 'Round']);
        $this->stone(['shape' => 'Oval']);
        $this->stone(['shape' => 'Pear']);

        $this->assertCount(2, $this->skus(['shape' => ['Round', 'Oval']]));
    }

    public function test_filters_by_carat_and_total_price_range(): void
    {
        $small = $this->stone(['carat' => 0.5, 'price_per_carat' => 4000]);   // 2,000
        $mid = $this->stone(['carat' => 1.5, 'price_per_carat' => 4000]);     // 6,000
        $big = $this->stone(['carat' => 3, 'price_per_carat' => 4000]);       // 12,000

        $this->assertEqualsCanonicalizing([$mid->sku, $big->sku], $this->skus(['carat_min' => 1]));
        $this->assertSame([$mid->sku], $this->skus(['price_min' => 5000, 'price_max' => 7000]));
        $this->assertSame([$small->sku], $this->skus(['carat_max' => 0.9]));
    }

    public function test_unknown_filter_values_are_ignored(): void
    {
        $this->stone();

        $this->assertCount(1, $this->skus(['shape' => ['Nope'], 'carat_min' => 'abc']));
    }

    public function test_sorting(): void
    {
        $cheap = $this->stone(['carat' => 1, 'price_per_carat' => 1000]);
        $pricey = $this->stone(['carat' => 1, 'price_per_carat' => 9000]);

        $this->assertSame([$cheap->sku, $pricey->sku], $this->skus(['sort' => 'price_asc']));
        $this->assertSame([$pricey->sku, $cheap->sku], $this->skus(['sort' => 'price_desc']));
    }

    public function test_unknown_sort_falls_back_to_newest(): void
    {
        $this->stone();

        $this->actingAs($this->buyer)->get('/stones?sort=drop_table')->assertOk();
    }

    public function test_stone_page_is_hidden_unless_available(): void
    {
        $sold = $this->stone(['status' => Stone::STATUS_SOLD]);
        $open = $this->stone();

        $this->actingAs($this->buyer)->get("/stones/{$sold->id}")->assertNotFound();
        $this->actingAs($this->buyer)->get("/stones/{$open->id}")->assertOk()->assertSee($open->sku);
    }

    public function test_sellers_cannot_use_the_buyer_catalogue(): void
    {
        $this->actingAs($this->seller)->get('/stones')->assertForbidden();
    }
}
