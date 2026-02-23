<?php

namespace App\Services\Admin;

use App\Exceptions\ApiException;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminUserService
{
    public function listUsers(int $perPage = 15): LengthAwarePaginator
    {
        return User::latest()->paginate($perPage);
    }

    public function findUser(int $id): User
    {
        $user = User::find($id);

        if (!$user) {
            throw new ApiException('User not found.', 404);
        }

        return $user;
    }

    /**
     * Update a user's role.
     *
     * Only super_admin can promote to admin / super_admin or demote an admin.
     */
    public function updateRole(User $actor, User $target, string $newRole): User
    {
        $this->authorizeRoleChange($actor, $target, $newRole);

        $target->role = $newRole;
        $target->save();

        return $target->fresh();
    }

    private function authorizeRoleChange(User $actor, User $target, string $newRole): void
    {
        // Prevent self-demotion
        if ($actor->id === $target->id) {
            throw new ApiException('You cannot change your own role.', 403);
        }

        // Only super_admin can promote to admin / super_admin or demote admins
        $privilegedChange = in_array($newRole, ['admin', 'super_admin'])
            || in_array($target->role, ['admin', 'super_admin']);

        if ($privilegedChange && !$actor->isSuperAdmin()) {
            throw new ApiException(
                'Only super_admin can promote or demote admin-level roles.',
                403
            );
        }
    }
}
