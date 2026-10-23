<?php

namespace App\Policies;

use App\Models\Stone;
use App\Models\User;

class StonePolicy
{
    public function update(User $user, Stone $stone): bool
    {
        return $stone->seller_id === $user->id;
    }

    public function delete(User $user, Stone $stone): bool
    {
        return $stone->seller_id === $user->id && $stone->status !== Stone::STATUS_SOLD;
    }
}
