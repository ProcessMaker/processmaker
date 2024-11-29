<?php

use Illuminate\Support\Facades\DB;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class AssignUserPermissionViewMyRequest extends Upgrade
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get the permission ID
        $permissionId = DB::table('permissions')
            ->where('name', 'view-my_requests')
            ->value('id');

        if ($permissionId) {
            $this->assignDefaultPermission($permissionId);
        }
    }

    /**
     * Assign view-my_requests permission to active users
     *
     * @param int $permissionId The ID of the permission
     * @return void
     */
    private function assignDefaultPermission(int $permissionId): void
    {
        $chunkSize = 500;
        DB::table('users AS u')
            // Check if the user has the permission
            ->leftJoin('assignables AS a', function ($join) use ($permissionId) {
                $join->on('u.id', '=', 'a.assignable_id')
                ->where('a.assignable_type', 'ProcessMaker\Models\User')
                ->where('a.permission_id', $permissionId);
            })
            // Filters
            ->whereNull('a.permission_id')
            ->where('u.is_system', false)
            ->where('u.status', 'ACTIVE')
            ->select('u.id')
            ->orderBy('u.id')
            ->chunk($chunkSize, function ($users) use ($permissionId) {
                $records = array_map(function ($user) use ($permissionId) {
                    return [
                        'permission_id' => $permissionId,
                        'assignable_id' => $user->id,
                        'assignable_type' => 'ProcessMaker\Models\User',
                    ];
                }, $users->toArray());

                // Insert rows
                DB::table('assignables')->insert($records);
            }, 'u.id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissionId = DB::table('permissions')
            ->where('name', 'view-my_requests')
            ->value('id');
        DB::table('assignables')
            ->where('permission_id', $permissionId)
            ->where('assignable_type', 'ProcessMaker\Models\User')
            ->delete();
    }
}
