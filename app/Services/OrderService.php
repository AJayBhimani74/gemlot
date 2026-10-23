<?php

namespace App\Services;

use App\Models\Inquiry;
use App\Models\Order;
use App\Models\Stone;
use DomainException;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Accept an inquiry: reserve the stone, open an order and close out
     * any competing inquiries on the same stone.
     */
    public function acceptInquiry(Inquiry $inquiry): Order
    {
        return DB::transaction(function () use ($inquiry) {
            // Lock the stone row so two accepts can't both win the same stone.
            $stone = Stone::whereKey($inquiry->stone_id)->lockForUpdate()->firstOrFail();
            $inquiry = Inquiry::whereKey($inquiry->id)->lockForUpdate()->firstOrFail();

            if (! $inquiry->isOpen()) {
                throw new DomainException('This inquiry has already been answered.');
            }

            if ($stone->status !== Stone::STATUS_AVAILABLE) {
                throw new DomainException('This stone is no longer available.');
            }

            $inquiry->update([
                'status' => Inquiry::STATUS_ACCEPTED,
                'responded_at' => now(),
            ]);

            Inquiry::where('stone_id', $stone->id)
                ->where('id', '!=', $inquiry->id)
                ->where('status', Inquiry::STATUS_OPEN)
                ->update(['status' => Inquiry::STATUS_DECLINED, 'responded_at' => now()]);

            $stone->update(['status' => Stone::STATUS_ON_HOLD]);

            return Order::create([
                'stone_id' => $stone->id,
                'buyer_id' => $inquiry->buyer_id,
                'seller_id' => $stone->seller_id,
                'inquiry_id' => $inquiry->id,
                'agreed_price' => $inquiry->offered_price ?? $stone->total_price,
            ]);
        });
    }

    public function transition(Order $order, string $action): Order
    {
        $rule = Order::ACTIONS[$action] ?? null;

        return DB::transaction(function () use ($order, $rule) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($rule === null || ! in_array($order->status, $rule['from'], true)) {
                throw new DomainException('That step is not available for this order.');
            }

            $order->status = $rule['to'];

            match ($rule['to']) {
                Order::STATUS_CONFIRMED => $order->confirmed_at = now(),
                Order::STATUS_SHIPPED => $order->shipped_at = now(),
                Order::STATUS_COMPLETED => $order->completed_at = now(),
                default => null,
            };

            $order->save();

            $this->syncStone($order);

            return $order;
        });
    }

    private function syncStone(Order $order): void
    {
        $stoneStatus = match ($order->status) {
            Order::STATUS_COMPLETED => Stone::STATUS_SOLD,
            Order::STATUS_CANCELLED => Stone::STATUS_AVAILABLE,
            default => null,
        };

        if ($stoneStatus) {
            Stone::withTrashed()->whereKey($order->stone_id)->update(['status' => $stoneStatus]);
        }
    }
}
