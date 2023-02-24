<?php

namespace ProcessMaker\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use ProcessMaker\Models\Notification;

class MarkNotificationAsRead implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable;

    public $conditions;

    public $updates;

    /**
     * Create a new job instance.
     *
     * @param array $conditions
     * @param array $updates
     *
     * @return void
     */
    public function __construct($conditions, $updates)
    {
        $this->conditions = $conditions;
        $this->updates = $updates;
    }

    public function handle()
    {
        Notification::where($this->conditions)
            ->whereNull('read_at')
            ->update($this->updates);
    }
}
