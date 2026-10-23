<?php

namespace Database\Factories;

use App\Models\Inquiry;
use App\Models\Stone;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inquiry>
 */
class InquiryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'stone_id' => Stone::factory(),
            'buyer_id' => User::factory()->buyer(),
            'message' => fake()->sentence(),
            'offered_price' => null,
            'status' => Inquiry::STATUS_OPEN,
        ];
    }
}
