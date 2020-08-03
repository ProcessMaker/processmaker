<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\ProcessCollaboration;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestLock;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\RequestUserPermission;
use ProcessMaker\Models\ScheduledTask;

class ProcessmakerClearRequests extends Command
{
    const message = 'Are you sure you\'d like to remove all requests and related data? Make sure you have backed up your database as this cannot be undone.';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:clear-requests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all requests / task data';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->confirm(self::message, false)) {
            ScheduledTask::query()->truncate();
            ProcessRequestLock::query()->truncate();
            ProcessRequestToken::query()->delete();
            Media::where('model_type', ProcessRequest::class)->delete();
            Comment::where('commentable_type', ProcessRequest::class)->delete();
            Comment::where('commentable_type', ProcessRequestToken::class)->delete();
            RequestUserPermission::query()->truncate();
            ProcessCollaboration::query()->truncate();
            ProcessRequest::query()->truncate();
        }
    }
}
