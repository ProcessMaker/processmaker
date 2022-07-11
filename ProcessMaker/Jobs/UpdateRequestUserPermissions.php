<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use DB;

use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\RequestUserPermission;

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

        // TODO:
        // - Check if can_view is used anywhere?
        // - If an entry exists in the request_user_permission table, assume they can view.
        // - Handle view all or edit all permission somewhere else (cached user permission)
        // - Handle users that can edit data (not just view)

        $usersCanSelfService = $processRequest->tokens()->select('users.id')
        ->where('process_request_tokens.is_self_service', true)
        ->join('users', function($join) {
            $join->on(function($on) {
                $on->whereRaw('JSON_CONTAINS(process_request_tokens.self_service_groups, CAST(users.id as JSON), "$.users")');
            });
        });

        $usersInSelfServiceGroups = $processRequest->tokens()
            ->join('group_members as gm', function($join) {
                $join->on(function($on) {
                    $on->whereRaw('JSON_CONTAINS(process_request_tokens.self_service_groups, CAST(gm.group_id as JSON), "$.groups")');
                });
            });
        
        // Handle participants
        // Handle users in process->usersCanEditData
        // Handle users in process->groupsCanEditData
        // Any others?
        
        $query->where('process_request_token.is_self_service', true);

        foreach($query->pluck('users.id') as $userId) {
            RequestUserPermission::updateOrCreate(
                ['request_id' => $processRequest->id, 'user_id' => $userId],
                ['can_view' => true]
            );
        }
            
        // Handle removing users that are no longer have permission?
    }
}
