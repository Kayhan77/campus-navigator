<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use App\Policies\Concerns\AuthorizesByRole;

class EventPolicy
{
    use AuthorizesByRole;

    /**
     * Determine if the user can view any events.
     */
    public function viewAny(User $user): bool
    {
        return true; // everyone can view events
    }

    /**
     * Determine if the user can create events.
     */
    public function create(User $user): bool
    {
        return $this->canManageContent($user);
    }

    /**
     * Determine if the user can update the event.
     */
    public function update(User $user, Event $event): bool
    {
        return $this->canManageContent($user);
    }

    /**
     * Determine if the user can delete the event.
     */
    public function delete(User $user, Event $event): bool
    {
        return $this->canManageContent($user);
    }
}
