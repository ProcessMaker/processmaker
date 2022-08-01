<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;
use ProcessMaker\Models\ProcessRequest;

class UpdateRequestUserPermissions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $processRequestId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $processRequestId)
    {
        $this->processRequestId = $processRequestId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $processRequest = ProcessRequest::findOrFail($this->processRequestId);


        $isSystem = $processRequest->process()->whereHas('categories', function ($query) {
            $query->where('is_system', true);
        })->exists();

        if ($isSystem) {
            return;
        }

        // TODO:
        // - Handle view all or edit all permission somewhere else (cached user permission)

        $query = User::select('users.id as id');

        // Self serve users 
        $query->whereIn('id', function ($query) use ($processRequest) {
            $query->selectRaw("j.ids AS ids FROM process_request_tokens, JSON_TABLE(self_service_groups, '$.users[*]' COLUMNS(ids INT PATH '$')) as j")
                ->where('process_request_tokens.is_self_service', true)
                ->where('process_request_tokens.process_request_id', $processRequest->id)
                ;
        });

        // Self serve groups
        $query->orWhereIn('id', function ($query) use ($processRequest) {
            $query->select('member_id')->from('group_members')
                ->join('groups', 'groups.id', '=', 'group_members.group_id')
                ->where('member_type', User::class)
                ->whereIn('group_id', function($query) use ($processRequest) {
                    $query->selectRaw("j.ids AS ids FROM process_request_tokens, JSON_TABLE(self_service_groups, '$.groups[*]' COLUMNS(ids INT PATH '$')) as j")
                        ->where('process_request_tokens.is_self_service', true)
                        ->where('process_request_tokens.process_request_id', $processRequest->id);
                })
                ->where('groups.status', 'ACTIVE');
        });

        // Process request user
        $query->orWhere('id', $processRequest->user_id);

        // Participants
        $query->orWhereIn('id', function($query) use ($processRequest) {
            $query->select('user_id')->from('process_request_tokens')
                ->where('process_request_id', $processRequest->id);
        });
        
        // usersCanEditData
        $query->orWhereIn('id', function($query) use ($processRequest) {
            $query->select('processable_id')->from('processables')
                ->where('processable_type', User::class)
                ->where('process_id', $processRequest->process_id)
                ->whereIn('method', ['CANCEL', 'EDIT_DATA']);
        });

        // groupsCanEditData
        $query->orWhereIn('id', function($query) use ($processRequest) {
            $query->select('member_id')->from('group_members')
                ->join('groups', 'groups.id', '=', 'group_members.group_id')
                ->where('member_type', User::class)
                ->whereIn('group_id', function($query) use ($processRequest) {
                    $query->select('processable_id')->from('processables')
                        ->where('processable_type', Group::class)
                        ->where('process_id', $processRequest->process_id)
                        ->whereIn('method', ['CANCEL', 'EDIT_DATA']);
                })
                ->where('groups.status', 'ACTIVE');
        });

        $userIds = $query->get()->pluck('id')->toArray();

        if (empty($userIds)) {
            return;
        }

        $upsert = "INSERT INTO request_user_permissions (request_id, user_id, created_at, updated_at) VALUES ";
        foreach ($userIds as $userId) {
            $upsert .= "($processRequest->id, $userId, NOW(), NOW()),";
        }
        $upsert = substr($upsert, 0, -1);
        $upsert .= " ON DUPLICATE KEY UPDATE updated_at = NOW();";
        \DB::statement($upsert);

        $delete = "DELETE FROM request_user_permissions WHERE request_id = $processRequest->id AND user_id NOT IN (" . implode(',', $userIds) . ");";
        \DB::statement($delete);
    }
}
