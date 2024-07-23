<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\Process;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('processes', function (Blueprint $table) {
            $table->unsignedInteger('updated_by')->nullable()->after('updated_at');
            //Foreign keys
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('process_versions', function (Blueprint $table) {
            $table->unsignedInteger('updated_by')->nullable()->after('updated_at');
            //Foreign keys
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        $this->updateUpdatedBy();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('processes', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropColumn('updated_by');
        });

        Schema::table('process_versions', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropColumn('updated_by');
        });
    }

    private function updateUpdatedBy(): void
    {
        foreach (Process::all() as $process) {
            DB::table('processes')->where('id', $process->id)->update([
                'updated_by' => $process->user_id,
            ]);

            $this->updateVersionForAlternative($process, 'A');
            $this->updateVersionForAlternative($process, 'B');
        }
    }

    private function updateVersionForAlternative($process, $alternative)
    {
        $latestPublished = $process->getLatestVersion($alternative);
        $latestDraftOrPublished = $process->getDraftOrPublishedLatestVersion($alternative);

        if ($latestPublished) {
            $latestPublished->update([
                'updated_by' => $process->user_id,
            ]);
        }

        if ($latestDraftOrPublished && $latestPublished && $latestPublished->id !== $latestDraftOrPublished->id) {
            $latestDraftOrPublished->update([
                'updated_by' => $process->user_id,
            ]);
        }
    }
};
