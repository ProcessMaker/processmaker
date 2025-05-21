<?php

namespace ProcessMaker\Helpers;

class StageProgressCalculator
{
    /**
     * Calculate the progress percentage of a stage.
     *
     * @param array $allStages List of all stages.
     *                         Example:
     *                         [
     *                            ['id' => 1, 'order' => 1, 'name' => 'Stage 1', 'selected' => false],
     *                            ['id' => 2, 'order' => 2, 'name' => 'Stage 2', 'selected' => false],
     *                         ]
     * @param array $currentStages Current stage details.
     *                             Example:
     *                             ['stage_id' = 1, 'stage_name' = 'Stage 1']
     * @return float Progress percentage.
     */
    public static function getProgressStage(array $allStages, array $currentStages): float
    {
        // Total number of stages
        $totalStages = count($allStages);

        // If there are no stages, return 0% progress
        if ($totalStages === 0) {
            return 0.0;
        }

        // Total number of current stages
        $totalCurrentStages = count($currentStages);

        // If there are no current stages, return 0% progress
        if ($totalCurrentStages === 0) {
            return 0.0;
        }

        // Count the number of completed stages
        $completedStages = 0;

        // Extract the current stage ID from the currentStages array
        $currentStageId = $currentStages['stage_id'];

        foreach ($allStages as $stage) {
            $completedStages++;
            // Check if the current stage ID matches the current stages
            if ($stage['id'] === $currentStageId) {
                break; // Exit the loop once the stage is found
            }
        }

        // Calculate progress percentage
        $progressPercentage = ($completedStages / $totalStages) * 100;

        return round($progressPercentage, 2);
    }
}
