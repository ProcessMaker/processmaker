<?php

namespace ProcessMaker\Traits;

use ProcessMaker\Models\ProcessRequest;
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
            ->orWhere(fn ($q) => $q->userGroupsHaveSelfServiceTasks($user));
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

        return $query;
    }

    public function scopeUserGroupsHaveSelfServiceTasks($query, $user)
    {
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
}
