<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\Comment;

class UpdateCommentsCaseNumber extends Command
{
    const CHUNK_SIZE = 5000;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:update-comments-case-number';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the column case_number in comments';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Update the case_number with the corresponding value in the table comments
        $chunkSize = self::CHUNK_SIZE;
        // Update the comments related to ProcessRequestToken
        DB::table('comments')
            ->leftJoin('process_request_tokens', 'comments.commentable_id', '=', 'process_request_tokens.id')
            ->leftJoin('process_requests', 'process_request_tokens.process_request_id', '=', 'process_requests.id')
            ->where('comments.commentable_type', 'ProcessMaker\\Models\\ProcessRequestToken')
            ->whereNull('comments.case_number')
            ->select('comments.id', 'process_requests.case_number')
            ->chunkById($chunkSize, function ($comments) {
                foreach ($comments as $comment) {
                    // Update the comments.case_number with ptrocess_requests.case_number
                    DB::table('comments')
                        ->where('id', $comment->id)
                        ->update(['case_number' => $comment->case_number]);
                }
            });
        // Update the comments related to ProcessRequest
        DB::table('comments')
            ->leftJoin('process_requests', 'comments.commentable_id', '=', 'process_requests.id')
            ->where('comments.commentable_type', 'ProcessMaker\\Models\\ProcessRequest')
            ->whereNull('comments.case_number')
            ->select('comments.id', 'process_requests.case_number')
            ->chunkById($chunkSize, function ($comments) {
                foreach ($comments as $comment) {
                    // Update the comments.case_number with ptrocess_requests.case_number
                    DB::table('comments')
                        ->where('id', $comment->id)
                        ->update(['case_number' => $comment->case_number]);
                }
            });

        return $this->info('Comments case_number updated successfully');
    }
}
