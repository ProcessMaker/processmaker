<?php

namespace ProcessMaker\Traits;

use DB;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;

trait ForUserScope
{
    public function scopeForUser($query, $user)
    {
        if ($user->is_administrator) {
            // Allow all
            return $query;
        }

        if ($user->canAny('edit-request_data|view-all_requests')) {
            // Allow all
            return $query;
        }

        return $query->userStarted($user)
            ->orWhere(fn ($q) => $q->userParticipated($user))
            ->orWhere(fn ($q) => $q->userHasSelfServiceTasks($user))
            ->orWhere(fn ($q) => $q->userHasEditProcessDataPermission($user));
    }

    public function scopeUserStarted($query, $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeUserParticipated($query, $user)
    {
        return $query->whereHas('tokens', fn ($q) => $q->where('user_id', $user->id));
    }

    public function scopeUserHasSelfServiceTasks($query, $user)
    {
        $query->whereHas('tokens', function ($query) use ($user) {
            $stringUserId = (string) $user->id;
            $query->where('is_self_service', true);
            $query->whereJsonContains('self_service_groups->users', $stringUserId);
        });

        $stringGroupIds = $user->groups()
            ->pluck('groups.id')
            ->map(fn ($id) => (string) $id);

        if ($stringGroupIds->isNotEmpty()) {
            $query->orWhereHas('tokens', function ($query) use ($stringGroupIds) {
                $query->where('is_self_service', true);
                $query->whereRaw(
                    'JSON_OVERLAPS(JSON_EXTRACT(`self_service_groups`, \'$."groups"\'), ?)',
                    [
                        $stringGroupIds->toJson(),
                    ]
                );
            });
        }

        return $query;
    }

    public function scopeUserHasEditProcessDataPermission($query, $user)
    {
        $query->whereIn('process_id', DB::table('processables')->select('process_id')->where([
            'processable_type' => User::class,
            'processable_id' => $user->id,
            'method' => 'EDIT_DATA',
        ]));

        $stringGroupIds = $user->groups()
            ->pluck('groups.id')
            ->map(fn ($id) => (string) $id);

        if ($stringGroupIds->isNotEmpty()) {
            $processables = DB::table('processables')->select('process_id')->where([
                'processable_type' => Group::class,
                'method' => 'EDIT_DATA',
            ])->whereIn('processable_id', $stringGroupIds);
            $query->orWhereIn('process_id', $processables);
        }

        return $query;
    }
}
