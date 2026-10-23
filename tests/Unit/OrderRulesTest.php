<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class OrderRulesTest extends TestCase
{
    private function user(int $id): User
    {
        return (new User)->forceFill(['id' => $id]);
    }

    private function order(string $status): Order
    {
        return (new Order)->forceFill(['status' => $status, 'buyer_id' => 1, 'seller_id' => 2]);
    }

    public function test_finished_orders_have_no_actions_for_anyone(): void
    {
        foreach ([Order::STATUS_COMPLETED, Order::STATUS_CANCELLED] as $status) {
            $order = $this->order($status);

            $this->assertSame([], $order->actionsFor($this->user(1)));
            $this->assertSame([], $order->actionsFor($this->user(2)));
        }
    }

    public function test_strangers_get_no_actions(): void
    {
        $this->assertSame([], $this->order(Order::STATUS_PENDING)->actionsFor($this->user(99)));
    }

    public function test_party_is_worked_out_from_the_user_id(): void
    {
        $order = $this->order(Order::STATUS_PENDING);

        $this->assertSame('buyer', $order->partyFor($this->user(1)));
        $this->assertSame('seller', $order->partyFor($this->user(2)));
        $this->assertNull($order->partyFor($this->user(3)));
    }

    public function test_every_action_leads_somewhere_that_exists(): void
    {
        $statuses = [
            Order::STATUS_PENDING, Order::STATUS_CONFIRMED, Order::STATUS_SHIPPED,
            Order::STATUS_COMPLETED, Order::STATUS_CANCELLED,
        ];

        foreach (Order::ACTIONS as $name => $rule) {
            $this->assertContains($rule['to'], $statuses, "{$name} ends in an unknown status");
            $this->assertNotEmpty($rule['from'], "{$name} can never be used");
            $this->assertNotContains($rule['to'], $rule['from'], "{$name} would loop on itself");
        }
    }

    public function test_the_buyer_and_seller_each_have_a_next_step_while_an_order_is_live(): void
    {
        foreach ([Order::STATUS_PENDING, Order::STATUS_CONFIRMED, Order::STATUS_SHIPPED] as $status) {
            $order = $this->order($status);
            $either = array_merge($order->actionsFor($this->user(1)), $order->actionsFor($this->user(2)));

            $this->assertNotEmpty($either, "A {$status} order should never be stuck");
        }
    }
}
