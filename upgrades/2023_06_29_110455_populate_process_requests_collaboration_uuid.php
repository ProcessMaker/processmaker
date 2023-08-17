<?php

use ProcessMaker\Models\ProcessCollaboration;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class PopulateProcessRequestsCollaborationUuid extends Upgrade
{
    /**
     * Run the upgrade migration.
     *
     * @return void
     */
    public function up()
    {
        $batchSize = 5;
        ProcessCollaboration::whereNull('uuid')
            ->chunkById($batchSize, function ($collaborations) {
                foreach ($collaborations as $collaboration) {
                    $collaboration->uuid = ProcessCollaboration::generateUuid();
                    $collaboration->save();
                }
            });

        ProcessRequest::select(['id', 'process_collaboration_id', 'collaboration_uuid'])->whereNull('collaboration_uuid')
            ->chunkById($batchSize, function ($requests) {
                foreach ($requests as $request) {
                    if ($request->process_collaboration_id) {
                        $request->collaboration_uuid = $request->collaboration->uuid;
                    } else {
                        $request->collaboration_uuid = ProcessCollaboration::generateUuid();
                    }
                    ProcessRequest::where('id', $request->id)->update(['collaboration_uuid' => $request->collaboration_uuid]);
                }
            });
    }
}
