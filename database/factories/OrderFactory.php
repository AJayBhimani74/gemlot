<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Stone;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'stone_id' => Stone::factory(),
            'buyer_id' => User::factory()->buyer(),
            'seller_id' => fn (array $attrs) => Stone::find($attrs['stone_id'])->seller_id,
            'agreed_price' => fake()->numberBetween(1000, 20000),
            'status' => Order::STATUS_PENDING,
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
