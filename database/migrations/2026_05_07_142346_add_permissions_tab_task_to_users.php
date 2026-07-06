<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\User;

return new class extends Migration {
    private const TAB_TASK_PERMISSIONS = [
        'overview-tab-task',
        'summary-tab-task',
        'completed-tab-task',
        'form-tab-task',
        'files-tab-task',
    ];

    private const CHUNK_SIZE = 500;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', self::TAB_TASK_PERMISSIONS)
            ->pluck('id', 'name')
            ->values();

        foreach ($permissionIds as $permissionId) {
            $this->assignPermissionToUsers((int) $permissionId);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', self::TAB_TASK_PERMISSIONS)
            ->pluck('id', 'name')
            ->values();

        DB::table('assignables')
            ->whereIn('permission_id', $permissionIds)
            ->where('assignable_type', User::class)
            ->delete();
    }

    private function assignPermissionToUsers(int $permissionId): void
    {
        $lastUserId = 0;

        do {
            $users = DB::table('users AS u')
                ->leftJoin('assignables AS a', function ($join) use ($permissionId) {
                    $join->on('u.id', '=', 'a.assignable_id')
                        ->where('a.assignable_type', User::class)
                        ->where('a.permission_id', $permissionId);
                })
                ->whereNull('a.permission_id')
                ->where('u.id', '>', $lastUserId)
                ->where('u.is_administrator', false)
                ->select('u.id')
                ->orderBy('u.id')
                ->limit(self::CHUNK_SIZE)
                ->get();

            if ($users->isEmpty()) {
                return;
            }

            $records = $users->map(fn ($user) => [
                'permission_id' => $permissionId,
                'assignable_id' => $user->id,
                'assignable_type' => User::class,
            ])->all();

            DB::table('assignables')->insert($records);
            $lastUserId = $users->last()->id;
        } while ($users->count() === self::CHUNK_SIZE);
    }
};
