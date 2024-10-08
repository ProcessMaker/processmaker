<?php

use Illuminate\Support\Facades\DB;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class PopulateCommentsCaseNumber extends Upgrade
{
    const CHUNK_SIZE = 5000;

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
            ->select('comments.id', 'process_requests.case_number')
            ->chunkById($chunkSize, function ($comments) {
                foreach ($comments as $comment) {
                    // Actualizar cada comentario con el case_number
                    DB::table('comments')
                        ->where('id', $comment->id)
                        ->update(['case_number' => $comment->case_number]);
                }
            });
        // Update the comments related to ProcessRequest
        DB::table('comments')
            ->leftJoin('process_requests', 'comments.commentable_id', '=', 'process_requests.id')
            ->where('comments.commentable_type', 'ProcessMaker\\Models\\ProcessRequest')
            ->select('comments.id', 'process_requests.case_number')
            ->chunkById($chunkSize, function ($comments) {
                foreach ($comments as $comment) {
                    // Actualizar cada comentario con el case_number
                    DB::table('comments')
                        ->where('id', $comment->id)
                        ->update(['case_number' => $comment->case_number]);
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
