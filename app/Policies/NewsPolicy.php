<?php

namespace App\Policies;

use App\Models\News;
use App\Models\User;
use App\Policies\Concerns\AuthorizesByRole;

class NewsPolicy
{
    use AuthorizesByRole;

    /**
     * Determine if the user can view any news.
     */
    public function viewAny(User $user): bool
    {
        return true; // everyone can view news
    }

    /**
     * Determine if the user can create news.
     */
    public function create(User $user): bool
    {
        return $this->canManageContent($user);
    }

    /**
     * Determine if the user can update the news.
     */
    public function update(User $user, News $news): bool
    {
        return $this->canManageContent($user);
    }

    /**
     * Determine if the user can delete the news.
     */
    public function delete(User $user, News $news): bool
    {
        return $this->canManageContent($user);
    }
}
