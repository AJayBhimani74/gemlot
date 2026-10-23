<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->hasRole(User::ROLE_ADMIN) || $order->partyFor($user) !== null;
    }

    public function transition(User $user, Order $order, string $action): bool
    {
        return in_array($action, $order->actionsFor($user), true);
    }
}
