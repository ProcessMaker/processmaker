<?php

namespace ProcessMaker\Traits;

use DB;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\ProcessRequestToken;
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

        $userHasSelfServiceTasks = ProcessRequestToken::where('is_self_service', true)
            ->whereJsonContains('self_service_groups->users', (string) $user->id)
            ->pluck('process_request_id')->toArray();

        $stringGroupIds = $user->groups()
            ->pluck('groups.id')
            ->map(fn ($id) => (string) $id);

        $userHasSelfServiceTasksGroups = ProcessRequestToken::where('is_self_service', true)
            ->whereRaw(
                'JSON_OVERLAPS(JSON_EXTRACT(`self_service_groups`, \'$."groups"\'), ?)',
                [
                    $stringGroupIds->toJson(),
                ]
            )
            ->pluck('process_request_id')->toArray();

        $processableUser = DB::table('processables')->where([
            'processable_type' => User::class,
            'processable_id' => $user->id,
            'method' => 'EDIT_DATA',
        ])->pluck('process_id')->toArray();

        $processableGroups = DB::table('processables')->where([
            'processable_type' => Group::class,
            'method' => 'EDIT_DATA',
        ])->whereIn('processable_id', $stringGroupIds->toArray())->pluck('process_id')->toArray();

        $filteredQuery = $query->userStarted($user)
            ->orWhereIn('id',
                array_unique(array_merge($userHasSelfServiceTasks, $userHasSelfServiceTasksGroups)))
            ->orWhereIn('process_id', array_unique(array_merge($processableUser, $processableGroups)));

        // If the user has participated in a request, the request is added to the results.
        // For the anonymous user this condition is not needed as it is a "placeholder" system account
        if ($user->username !== '_pm4_anon_user') {
            $mainTable = $query->getQuery()->from;
            $filteredQuery->orWhereExists(function ($query) use ($user, $mainTable) {
                $query->selectRaw('1')
                    ->from('process_request_tokens')
                    ->where('user_id', $user->id)
                    ->whereRaw('process_request_id = ' . $mainTable . '.id');
            });
        }

        return $filteredQuery;
    }

    public function scopeUserStarted($query, $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeUserParticipated($query, $user)
    {
        return $query->whereIn('id', function ($query) use ($user) {
            $query->select('process_request_id')
                ->from((new ProcessRequestToken)->getTable())
                ->where('user_id', $user->id);
        });
    }

    public function scopeUserHasSelfServiceTasks($query, $user)
    {
        $query->whereIn('id', function ($query) use ($user) {
            $stringUserId = (string) $user->id;

            return $query->select('process_request_id')
                ->from((new ProcessRequestToken)->getTable())
                ->where('is_self_service', true)
                ->whereJsonContains('self_service_groups->users', $stringUserId);
        });

        $stringGroupIds = $user->groups()
            ->pluck('groups.id')
            ->map(fn ($id) => (string) $id);

        if ($stringGroupIds->isNotEmpty()) {
            $query->orWhereIn('id', function ($query) use ($stringGroupIds) {
                return $query->select('process_request_id')
                    ->from((new ProcessRequestToken)->getTable())
                    ->where('is_self_service', true)
                    ->whereRaw(
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
