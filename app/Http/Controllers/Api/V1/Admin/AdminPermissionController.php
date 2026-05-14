<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class AdminPermissionController extends Controller
{
    /**
     * Get all available permissions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $permissions = Permission::all(['id', 'name'])->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
            ];
        });

        return ApiResponse::success(
            $permissions,
            'Permissions retrieved successfully.'
        );
    }

    /**
     * Get permissions for a specific role.
     *
     * @param string $role
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByRole(string $role)
    {
        $role = Role::where('name', $role)->first();

        if (!$role) {
            return ApiResponse::error('Role not found.', 404);
        }

        $permissions = $role->permissions()->pluck('name');

        return ApiResponse::success([
            'role' => $role->name,
            'permissions' => $permissions,
        ], "Permissions for role '{$role->name}' retrieved successfully.");
    }

    /**
     * Get all roles with their permissions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function rolesWithPermissions()
    {
        $roles = Role::with('permissions')
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $role->permissions->map(fn($p) => ['id' => $p->id, 'name' => $p->name]),
                ];
            });

        return ApiResponse::success(
            $roles,
            'Roles with permissions retrieved successfully.'
        );
    }
}
