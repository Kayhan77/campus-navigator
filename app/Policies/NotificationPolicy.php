<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\AuthorizesByRole;

class NotificationPolicy
{
    use AuthorizesByRole;

    public function send(User $user): bool
    {
        return $this->canByPermission($user, 'send_notification');
    }
}
