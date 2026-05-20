<?php

namespace Tests\Feature\Migrations;

use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\User;
use Tests\TestCase;

class AddPermissionsTabTaskToUsersTest extends TestCase
{
    private const MIGRATION_PATH = 'database/migrations/2026_05_07_142346_add_permissions_tab_task_to_users.php';

    private const TAB_TASK_PERMISSIONS = [
        'overview-tab-task',
        'summary-tab-task',
        'completed-tab-task',
        'form-tab-task',
        'files-tab-task',
    ];

    public function testItAssignsTabTaskPermissionsToNonAdministratorUsersOnly(): void
    {
        (new PermissionSeeder)->run();

        [$existingUser, $newUser, $adminUser] = User::withoutEvents(fn () => [
            User::factory()->create(['is_administrator' => false]),
            User::factory()->create(['is_administrator' => false]),
            User::factory()->admin()->create(),
        ]);

        $existingPermission = Permission::byName('overview-tab-task');
        $existingUser->permissions()->attach($existingPermission->id);

        $this->migration()->up();

        $this->assertSame($this->tabTaskPermissionIds()->count(), $this->tabTaskPermissionCount($existingUser));
        $this->assertSame($this->tabTaskPermissionIds()->count(), $this->tabTaskPermissionCount($newUser));
        $this->assertSame(0, $this->tabTaskPermissionCount($adminUser));
        $this->assertSame(1, $this->userPermissionCount($existingUser, $existingPermission));
    }

    public function testItRemovesTabTaskPermissionAssignmentsOnRollback(): void
    {
        (new PermissionSeeder)->run();

        $user = User::withoutEvents(fn () => User::factory()->create(['is_administrator' => false]));

        $migration = $this->migration();
        $migration->up();
        $migration->down();

        $this->assertSame(0, $this->tabTaskPermissionCount($user));
    }

    private function migration()
    {
        return include base_path(self::MIGRATION_PATH);
    }

    private function tabTaskPermissionCount(User $user): int
    {
        return DB::table('assignables')
            ->where('assignable_id', $user->id)
            ->where('assignable_type', User::class)
            ->whereIn('permission_id', $this->tabTaskPermissionIds())
            ->count();
    }

    private function userPermissionCount(User $user, Permission $permission): int
    {
        return DB::table('assignables')
            ->where('assignable_id', $user->id)
            ->where('assignable_type', User::class)
            ->where('permission_id', $permission->id)
            ->count();
    }

    private function tabTaskPermissionIds()
    {
        return Permission::whereIn('name', self::TAB_TASK_PERMISSIONS)
            ->pluck('id', 'name')
            ->values();
    }
}
