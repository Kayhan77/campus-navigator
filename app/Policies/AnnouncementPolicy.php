<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;
use App\Policies\Concerns\AuthorizesByRole;

class AnnouncementPolicy
{
    use AuthorizesByRole;

    /**
     * Determine if the user can view any announcements.
     */
    public function viewAny(User $user): bool
    {
        return true; // everyone authenticated can view announcements
    }

    /**
     * Determine if the user can create announcements.
     */
    public function create(User $user): bool
    {
        return $this->canManageContent($user);
    }

    /**
     * Determine if the user can update the announcement.
     */
    public function update(User $user, Announcement $announcement): bool
    {
        return $this->canManageContent($user);
    }

    /**
     * Determine if the user can delete the announcement.
     */
    public function delete(User $user, Announcement $announcement): bool
    {
        return $this->canManageContent($user);
    }
}
