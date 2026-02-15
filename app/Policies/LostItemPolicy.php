<?php

namespace App\Policies;

use App\Models\User;
use App\Models\LostItem;

class LostItemPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, LostItem $item): bool { return true; }
    public function create(User $user): bool { return true; } // anyone can report
    public function update(User $user, LostItem $item): bool
    {
        return $user->id === $item->user_id || $user->role === 'admin';
    }
    public function delete(User $user, LostItem $item): bool
    {
        return $user->id === $item->user_id || $user->role === 'admin';
    }
}
