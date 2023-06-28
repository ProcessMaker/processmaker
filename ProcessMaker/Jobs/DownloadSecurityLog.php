<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Events\SecurityLogDownloadJobCompleted;
use ProcessMaker\Models\User;

class DownloadSecurityLog implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    private User $user;

    private string $format;

    private ?int $userId;

    /**
     * @param User $user
     * @param string $format
     * @param int|null $userId
     */
    public function __construct(User $user, string $format, int $userId = null)
    {
        $this->user = $user;
        $this->format = $format;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //1. Get all data from security_logs table
        //2. Create a file in specified format: csv or xml
        //3. Zip this file
        //4. Store in S3, with private visibility, with 24h of lifecycle
        //5. Send email to user with the link or error message
        if (mt_rand(1, 10) <= 8) {
            event(new SecurityLogDownloadJobCompleted($this->user, true, __('Click on the link and download the file. This link will be available until midnight tonight.'), 'http://processmaker.com/?download=true'));
        } else {
            event(new SecurityLogDownloadJobCompleted($this->user, false, __('Sorry, it was not possible to generate the log file. Please contact the administrator.')));
        }
    }
}
