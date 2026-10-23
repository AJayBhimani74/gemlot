<?php

namespace App\Policies;

use App\Models\Inquiry;
use App\Models\User;

class InquiryPolicy
{
    public function respond(User $user, Inquiry $inquiry): bool
    {
        return $inquiry->isOpen() && $inquiry->stone->seller_id === $user->id;
    }

    public function withdraw(User $user, Inquiry $inquiry): bool
    {
        return $inquiry->isOpen() && $inquiry->buyer_id === $user->id;
    }
}
