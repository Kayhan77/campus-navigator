<?php

namespace App\Policies;

use App\Models\ItemClaim;
use App\Models\User;

class ItemClaimPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, ItemClaim $claim): bool
    {
        return $user->id === $claim->lostItem->user_id;
    }

    public function accept(User $user, ItemClaim $claim): bool
    {
        return $user->id === $claim->lostItem->user_id;
    }

    public function reject(User $user, ItemClaim $claim): bool
    {
        return $user->id === $claim->lostItem->user_id;
    }
}
