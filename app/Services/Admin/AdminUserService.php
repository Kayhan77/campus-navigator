<?php

namespace App\Services\Admin;

use App\Enums\UserRole;
use App\Exceptions\ApiException;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
     * Super admins can change any role.
     * Admins can only assign sub_admin to non-admin users.
     */
    public function updateRole(User $actor, User $target, string $newRole): User
    {
        $newRoleEnum = UserRole::from($newRole);
        $this->authorizeRoleChange($actor, $target, $newRoleEnum);

        return DB::transaction(function () use ($target, $newRoleEnum): User {
            $target->role = $newRoleEnum;
            $target->save();

            $this->assignRoleToUser((int) $target->id, $newRoleEnum->value, false);

            return $target->fresh();
        });
    }

    public function assignRoleToUser(int $userId, string $role, bool $requireActorAuthorization = true): User
    {
        $user = $this->findUser($userId);
        $normalizedRole = trim($role);

        if ($normalizedRole === '') {
            throw new ApiException('Role is required.', 422);
        }

        $requestedRole = UserRole::tryFrom($normalizedRole);

        if ($requestedRole === null) {
            throw new ApiException('Invalid role.', 422);
        }

        if ($requireActorAuthorization) {
            $this->authorizeRoleAssignment(Auth::user(), $user, $requestedRole);
        }

        return DB::transaction(function () use ($user, $normalizedRole): User {
            $roleModel = Role::query()->firstOrCreate(['name' => $normalizedRole]);

            $enumRole = UserRole::tryFrom($normalizedRole);
            if ($enumRole !== null) {
                $user->role = $enumRole;
                $user->save();
            }

            $user->roles()->sync([$roleModel->id]);

            return $user->fresh();
        });
    }

    /**
     * @param array<int, string> $permissions
     * @return array<int, string>
     */
    public function syncPermissionsToRole(string $role, array $permissions): array
    {
        $normalizedRole = trim($role);

        if ($normalizedRole === '') {
            throw new ApiException('Role is required.', 422);
        }

        $roleEnum = UserRole::tryFrom($normalizedRole);

        $this->authorizeRolePermissionSync(Auth::user(), $normalizedRole, $roleEnum);

        return DB::transaction(function () use ($normalizedRole, $permissions): array {
            $roleModel = Role::query()->firstOrCreate(['name' => $normalizedRole]);
            $permissionNames = collect($permissions)
                ->filter(fn ($permission) => is_string($permission) && trim($permission) !== '')
                ->map(fn (string $permission) => trim($permission))
                ->unique()
                ->values();

            $permissionIds = [];

            foreach ($permissionNames as $permissionName) {
                $permission = Permission::query()->firstOrCreate(['name' => $permissionName]);
                $permissionIds[] = $permission->id;
            }

            $roleModel->permissions()->sync($permissionIds);

            return $permissionNames->all();
        });
    }

    public function userHasPermission(int $userId, string $permission): bool
    {
        $user = $this->findUser($userId);

        return $user->hasPermission($permission);
    }

    private function authorizeRoleChange(User $actor, User $target, UserRole $newRole): void
    {
        // Prevent self-demotion
        if ($actor->id === $target->id) {
            throw new ApiException('You cannot change your own role.', 403);
        }

        if ($actor->isSuperAdmin()) {
            return;
        }

        if ($actor->hasRole(UserRole::Admin)) {
            if ($newRole !== UserRole::SubAdmin) {
                throw new ApiException('Admins can only assign the sub_admin role.', 403);
            }

            if ($target->roleEnum()->isAdminLevel()) {
                throw new ApiException('Admins cannot change admin-level users.', 403);
            }

            return;
        }

        throw new ApiException('Only admins can assign sub_admin and only super_admin can manage admin-level roles.', 403);
    }

    private function authorizeRoleAssignment(?User $actor, User $target, UserRole $newRole): void
    {
        if (!$actor instanceof User) {
            throw new ApiException('Unauthenticated.', 401);
        }

        $this->authorizeRoleChange($actor, $target, $newRole);
    }

    private function authorizeRolePermissionSync(?User $actor, string $role, ?UserRole $roleEnum): void
    {
        if (!$actor instanceof User) {
            throw new ApiException('Unauthenticated.', 401);
        }

        if ($actor->isSuperAdmin()) {
            return;
        }

        if ($actor->hasRole(UserRole::Admin) && ($roleEnum === UserRole::SubAdmin || $role === UserRole::SubAdmin->value)) {
            return;
        }

        throw new ApiException('Only super_admin can manage this role permissions.', 403);
    }
}
