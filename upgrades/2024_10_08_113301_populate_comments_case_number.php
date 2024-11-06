<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class PopulateCommentsCaseNumber extends Upgrade
{
    const CHUNK_SIZE = 2000;

    /**
     * Run any validations/pre-run checks to ensure the environment, settings,
     * packages installed, etc. are right correct to run this upgrade.
     *
     * Throw a \RuntimeException if the conditions are *NOT* correct for this
     * upgrade migration to run. If this is not a required upgrade, then it
     * will be skipped. Otherwise the exception thrown will be caught, noted,
     * and will prevent the remaining migrations from continuing to run.
     *
     * Returning void or null denotes the checks were successful.
     *
     * @return void
     *
     * @throws RuntimeException
     */
    public function preflightChecks()
    {
        //
    }

    /**
     * Run the migrations.
     */
    public function up(): void
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
                    Log::info(count($updates) . ' comments updated in this chunk related to ProcessRequestToken');
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
                    Log::info(count($updates) . ' comments updated in this chunk related to ProcessRequest');
                }
            });
    }

    /**
     * Reverse the upgrade migration.
     *
     * @return void
     */
    public function down()
    {
        // Set the case_number
        DB::table('comments')->update([
            'case_number' => null,
        ]);
    }
}
