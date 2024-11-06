<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateCommentsCaseNumber extends Command
{
    const CHUNK_SIZE = 2000;

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
            ->orderBy('comments.id', 'asc')
            ->chunk($chunkSize, function ($comments) {
                $updates = $comments->mapWithKeys(function ($comment) {
                    if (!is_null($comment->case_number) && !empty($comment->case_number)) {
                        return [$comment->id => ['case_number' => $comment->case_number]];
                    }

                    return [];
                })->toArray();
                // Execute in bath the update
                if (!empty($updates)) {
                    $query = 'UPDATE comments SET case_number = CASE id';
                    foreach ($updates as $id => $data) {
                        $query .= " WHEN {$id} THEN '{$data['case_number']}'";
                    }
                    $query .= ' END WHERE id IN (' . implode(',', array_keys($updates)) . ')';
                    DB::statement($query);
                    $this->info(count($updates) . ' comments updated in this chunk related to ProcessRequestToken');
                }
            });
        // Update the comments related to ProcessRequest
        DB::table('comments')
            ->leftJoin('process_requests', 'comments.commentable_id', '=', 'process_requests.id')
            ->where('comments.commentable_type', 'ProcessMaker\\Models\\ProcessRequest')
            ->whereNull('comments.case_number')
            ->select('comments.id', 'process_requests.case_number')
            ->orderBy('comments.id', 'asc')
            ->chunk($chunkSize, function ($comments) {
                $updates = $comments->mapWithKeys(function ($comment) {
                    if (!is_null($comment->case_number) && !empty($comment->case_number)) {
                        return [$comment->id => ['case_number' => $comment->case_number]];
                    }

                    return [];
                })->toArray();
                // Execute in bath the update
                if (!empty($updates)) {
                    $query = 'UPDATE comments SET case_number = CASE id';
                    foreach ($updates as $id => $data) {
                        $query .= " WHEN {$id} THEN '{$data['case_number']}'";
                    }
                    $query .= ' END WHERE id IN (' . implode(',', array_keys($updates)) . ')';
                    DB::statement($query);
                    $this->info(count($updates) . ' comments updated in this chunk related to ProcessRequest');
                }
            });

        return $this->info('Comments case_number updated successfully');
    }
}
