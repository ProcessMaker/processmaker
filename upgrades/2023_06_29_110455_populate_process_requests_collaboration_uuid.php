<?php

use ProcessMaker\Models\ProcessCollaboration;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class PopulateProcessRequestsCollaborationUuid extends Upgrade
{
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
     * @throws \RuntimeException
     */
    public function preflightChecks()
    {
        //
    }

    /**
     * Run the upgrade migration.
     *
     * @return void
     */
    public function up()
    {
        foreach (ProcessCollaboration::cursor() as $collaboration) {
            $collaboration->uuid = ProcessCollaboration::generateUuid();
            $collaboration->save();
        }
        foreach (ProcessRequest::cursor() as $processRequest) {
            if ($processRequest->process_collaboration_id) {
                $processRequest->collaboration_uuid = $processRequest->collaboration->uuid;
            } else {
                $processRequest->collaboration_uuid = ProcessCollaboration::generateUuid();
            }
            $processRequest->save();
        }
    }

    /**
     * Reverse the upgrade migration.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
