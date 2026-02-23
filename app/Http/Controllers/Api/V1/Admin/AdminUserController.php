<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Http\Resources\Api\V1\Admin\AdminUserResource;
use App\Models\User;
use App\Services\Admin\AdminUserService;

class AdminUserController extends Controller
{
    public function __construct(
        private AdminUserService $service
    ) {}

    public function index()
    {
        $users = $this->service->listUsers()
            ->through(fn($user) => new AdminUserResource($user));

        return ApiResponse::paginated($users, 'Users retrieved successfully.');
    }

    public function show(User $user)
    {
        return ApiResponse::success(
            new AdminUserResource($user),
            'User retrieved successfully.'
        );
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user)
    {
        $updated = $this->service->updateRole(
            $request->user(),
            $user,
            $request->validated('role')
        );

        return ApiResponse::success(
            new AdminUserResource($updated),
            'User role updated successfully.'
        );
    }
}
