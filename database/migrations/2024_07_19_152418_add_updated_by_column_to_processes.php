<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Process;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('processes', function (Blueprint $table) {
            if (! Schema::hasColumn('processes', 'updated_by')) {
                $table->unsignedInteger('updated_by')->nullable()->after('updated_at');

                $table->foreign('updated_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            }
        });

        Schema::table('process_versions', function (Blueprint $table) {
            if (! Schema::hasColumn('process_versions', 'updated_by')) {
                $table->unsignedInteger('updated_by')->nullable()->after('updated_at');

                $table->foreign('updated_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            }
        });

        foreach (Process::all() as $process) {
            $updatedBy = User::find($process->user_id);

            if ($updatedBy) {
                DB::table('processes')->where('id', $process->id)->update([
                    'updated_by' => $updatedBy->id,
                ]);

                $this->updateVersionForAlternative($process, 'A', $updatedBy);
                $this->updateVersionForAlternative($process, 'B', $updatedBy);
            }
        }
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

    private function updateVersionForAlternative($process, $alternative, $updatedBy)
    {
        $latestPublished = $process->getLatestVersion($alternative);
        $latestDraftOrPublished = $process->getDraftOrPublishedLatestVersion($alternative);

        if ($latestPublished) {
            $latestPublished->update([
                'updated_by' => $updatedBy->id,
            ]);
        }

        if ($latestDraftOrPublished && $latestPublished && $latestPublished->id !== $latestDraftOrPublished->id) {
            $latestDraftOrPublished->update([
                'updated_by' => $updatedBy->id,
            ]);
        }
    }
};