<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\Event;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RbacManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    #[Test]
    public function super_admin_can_assign_role_to_user(): void
    {
        /** @var User $superAdmin */
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        /** @var User $targetUser */
        $targetUser = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($superAdmin, 'api')
            ->postJson("/api/v1/admin/users/{$targetUser->id}/assign-role", [
                'role' => 'sub_admin',
            ]);

        $response->assertOk();

        $targetUser->refresh();
        $this->assertSame('sub_admin', $targetUser->role->value);

        $subAdminRoleId = Role::query()->where('name', 'sub_admin')->value('id');
        $this->assertDatabaseHas('user_role', [
            'user_id' => $targetUser->id,
            'role_id' => $subAdminRoleId,
        ]);
    }

    #[Test]
    public function admin_can_assign_sub_admin_role_to_user(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        /** @var User $targetUser */
        $targetUser = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($admin, 'api')
            ->postJson("/api/v1/admin/users/{$targetUser->id}/assign-role", [
                'role' => 'sub_admin',
            ]);

        $response->assertOk();

        $targetUser->refresh();
        $this->assertSame('sub_admin', $targetUser->role->value);
    }

    #[Test]
    public function admin_can_sync_sub_admin_role_permissions(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'api')
            ->putJson('/api/v1/admin/roles/sub_admin/permissions', [
                'permissions' => ['create_news'],
            ]);

        $response->assertOk();

        $roleId = Role::query()->where('name', 'sub_admin')->value('id');
        $permissionId = Permission::query()->where('name', 'create_news')->value('id');

        $this->assertDatabaseHas('role_permission', [
            'role_id' => $roleId,
            'permission_id' => $permissionId,
        ]);
    }

    #[Test]
    public function admin_cannot_sync_admin_role_permissions(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'api')
            ->putJson('/api/v1/admin/roles/admin/permissions', [
                'permissions' => ['manage_users'],
            ]);

        $response->assertForbidden();
    }

    #[Test]
    public function super_admin_can_sync_role_permissions(): void
    {
        /** @var User $superAdmin */
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin, 'api')
            ->putJson('/api/v1/admin/roles/sub_admin/permissions', [
                'permissions' => ['create_news'],
            ]);

        $response->assertOk();

        $roleId = Role::query()->where('name', 'sub_admin')->value('id');
        $permissionId = Permission::query()->where('name', 'create_news')->value('id');

        $this->assertDatabaseHas('role_permission', [
            'role_id' => $roleId,
            'permission_id' => $permissionId,
        ]);
    }

    #[Test]
    public function admin_can_create_event_when_permitted_but_sub_admin_without_permission_is_blocked(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        /** @var User $subAdmin */
        $subAdmin = User::factory()->create(['role' => 'sub_admin']);

        $adminRoleId = Role::query()->where('name', 'admin')->value('id');
        $subAdminRoleId = Role::query()->where('name', 'sub_admin')->value('id');

        $admin->roles()->sync([$adminRoleId]);
        $subAdmin->roles()->sync([$subAdminRoleId]);

        // Remove all dynamic permissions from sub_admin to prove policy enforcement.
        Role::query()->where('name', 'sub_admin')->first()?->permissions()->sync([]);

        $this->assertTrue($admin->can('create', Event::class));
        $this->assertFalse($subAdmin->can('create', Event::class));
    }
}
