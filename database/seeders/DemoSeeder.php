<?php

namespace Database\Seeders;

use App\Models\Inquiry;
use App\Models\Stone;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Database\Seeder;

/**
 * Fills the app with enough trade data to click around in.
 * Run with: php artisan db:seed --class=DemoSeeder
 * Every demo account uses the password "password".
 */
class DemoSeeder extends Seeder
{
    private const SELLERS = [
        ['Anil Mehta', 'anil@mehtagems.test', 'Mehta Gems', 'IN', true],
        ['Sofia Lindqvist', 'sofia@nordicstones.test', 'Nordic Stones', 'SE', true],
        ['Hakan Demir', 'hakan@bosphorus.test', 'Bosphorus Diamonds', 'TR', true],
        ['Wei Zhang', 'wei@pearlriver.test', 'Pearl River Trading', 'CN', false],
    ];

    private const BUYERS = [
        ['Maria Rossi', 'maria@rossijoyas.test', 'Rossi Gioielli', 'IT'],
        ['James Carter', 'james@carterandsons.test', 'Carter & Sons', 'US'],
        ['Fatima Al-Sayed', 'fatima@goldsouq.test', 'Gold Souq Trading', 'AE'],
    ];

    public function run(OrderService $orders): void
    {
        if (Stone::exists()) {
            $this->command?->warn('Stones already exist, skipping the demo data. Use migrate:fresh to start over.');

            return;
        }

        fake()->seed(2024);

        $sellers = collect(self::SELLERS)->map(fn ($s) => User::updateOrCreate(
            ['email' => $s[1]],
            [
                'name' => $s[0],
                'password' => 'password',
                'role' => User::ROLE_SELLER,
                'company_name' => $s[2],
                'country' => $s[3],
                'is_approved' => $s[4],
            ]
        ));

        $buyers = collect(self::BUYERS)->map(fn ($b) => User::updateOrCreate(
            ['email' => $b[1]],
            [
                'name' => $b[0],
                'password' => 'password',
                'role' => User::ROLE_BUYER,
                'company_name' => $b[2],
                'country' => $b[3],
                'is_approved' => true,
            ]
        ));

        $approved = $sellers->filter->is_approved->values();

        // Roughly 12 stones per approved seller, plus a few from the pending one.
        foreach ($sellers as $seller) {
            $count = $seller->is_approved ? 12 : 3;

            for ($i = 0; $i < $count; $i++) {
                $this->makeStone($seller);
            }
        }

        $this->tradeSomeStones($orders, $approved, $buyers);
    }

    private function makeStone(User $seller): Stone
    {
        $carat = fake()->randomElement([0.30, 0.50, 0.70, 0.90, 1.00, 1.20, 1.50, 2.00, 2.50, 3.01]);
        $color = fake()->randomElement(Stone::COLORS);
        $clarity = fake()->randomElement(Stone::CLARITIES);

        // Rough per-carat price: bigger, whiter and cleaner stones cost more.
        $base = 1800 + $carat * 1400;
        $colorFactor = 1.35 - array_search($color, Stone::COLORS) * 0.07;
        $clarityFactor = 1.3 - array_search($clarity, Stone::CLARITIES) * 0.06;
        $perCarat = round($base * $colorFactor * $clarityFactor / 50) * 50;

        $lab = fake()->randomElement(Stone::LABS);

        return Stone::create([
            'seller_id' => $seller->id,
            'shape' => fake()->randomElement(Stone::SHAPES),
            'carat' => $carat,
            'color' => $color,
            'clarity' => $clarity,
            'cut' => fake()->randomElement(Stone::CUTS),
            'lab' => $lab,
            'certificate_no' => fake()->numerify('##########'),
            'price_per_carat' => $perCarat,
            'status' => Stone::STATUS_AVAILABLE,
        ]);
    }

    private function tradeSomeStones(OrderService $orders, $sellers, $buyers): void
    {
        $stones = Stone::available()->whereIn('seller_id', $sellers->pluck('id'))->inRandomOrder()->limit(12)->get();

        // Open inquiries nobody has answered yet.
        foreach ($stones->splice(0, 4) as $stone) {
            Inquiry::create([
                'stone_id' => $stone->id,
                'buyer_id' => $buyers->random()->id,
                'message' => fake()->randomElement([
                    'Is the certificate available as a PDF?',
                    'Interested in this one, what is your best price?',
                    'Can you ship to Dubai within the week?',
                ]),
                'offered_price' => round($stone->total_price * 0.93, 2),
                'status' => Inquiry::STATUS_OPEN,
            ]);
        }

        // One declined inquiry.
        $declined = $stones->shift();
        Inquiry::create([
            'stone_id' => $declined->id,
            'buyer_id' => $buyers->random()->id,
            'message' => 'Would you take 70% of the list price?',
            'offered_price' => round($declined->total_price * 0.7, 2),
            'status' => Inquiry::STATUS_DECLINED,
            'responded_at' => now()->subDay(),
        ]);

        // Orders sitting at each stage of the lifecycle.
        $steps = [[], ['confirm'], ['confirm', 'ship'], ['confirm', 'ship', 'complete'], ['cancel']];

        foreach ($steps as $actions) {
            $stone = $stones->shift();

            $inquiry = Inquiry::create([
                'stone_id' => $stone->id,
                'buyer_id' => $buyers->random()->id,
                'message' => 'Ready to proceed.',
                'offered_price' => round($stone->total_price * 0.96, 2),
                'status' => Inquiry::STATUS_OPEN,
            ]);

            $order = $orders->acceptInquiry($inquiry);

            foreach ($actions as $action) {
                $orders->transition($order, $action);
            }
        }
    }
}
