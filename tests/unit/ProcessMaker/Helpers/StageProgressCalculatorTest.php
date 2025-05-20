<?php

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use ProcessMaker\Helpers\StageProgressCalculator;

class StageProgressCalculatorTest extends TestCase
{
    /**
     * Test getProgressStage method with various scenarios
     */
    public function testGetProgressStage(): void
    {
        // Test case 1: Empty stages
        $this->assertEquals(
            0.0,
            StageProgressCalculator::getProgressStage(
                [],
                ['stage_id' => 1, 'stage_name' => 'Stage 1']
            )
        );

        // Test case 2: Empty current stages
        $this->assertEquals(
            0.0,
            StageProgressCalculator::getProgressStage(
                [
                    ['id' => 1, 'order' => 1, 'label' => 'Stage 1', 'selected' => false],
                    ['id' => 2, 'order' => 2, 'label' => 'Stage 2', 'selected' => false],
                ],
                []
            )
        );

        // Test case 3: First stage progress
        $this->assertEquals(
            33.33,
            StageProgressCalculator::getProgressStage(
                [
                    ['id' => 1, 'order' => 1, 'label' => 'Stage 1', 'selected' => false],
                    ['id' => 2, 'order' => 2, 'label' => 'Stage 2', 'selected' => false],
                    ['id' => 3, 'order' => 3, 'label' => 'Stage 3', 'selected' => false],
                ],
                ['stage_id' => 1, 'stage_name' => 'Stage 1']
            )
        );

        // Test case 4: Middle stage progress
        $this->assertEquals(
            66.67,
            StageProgressCalculator::getProgressStage(
                [
                    ['id' => 1, 'order' => 1, 'label' => 'Stage 1', 'selected' => false],
                    ['id' => 2, 'order' => 2, 'label' => 'Stage 2', 'selected' => false],
                    ['id' => 3, 'order' => 3, 'label' => 'Stage 3', 'selected' => false],
                ],
                ['stage_id' => 2, 'stage_name' => 'Stage 2']
            )
        );

        // Test case 5: Last stage progress
        $this->assertEquals(
            100.0,
            StageProgressCalculator::getProgressStage(
                [
                    ['id' => 1, 'order' => 1, 'label' => 'Stage 1', 'selected' => false],
                    ['id' => 2, 'order' => 2, 'label' => 'Stage 2', 'selected' => false],
                    ['id' => 3, 'order' => 3, 'label' => 'Stage 3', 'selected' => false],
                ],
                ['stage_id' => 3, 'stage_name' => 'Stage 3']
            )
        );
        // Test case 6: Unexisting stage progress
        $this->assertEquals(
            100.0,
            StageProgressCalculator::getProgressStage(
                [
                    ['id' => 1, 'order' => 1, 'label' => 'Stage 1', 'selected' => false],
                    ['id' => 2, 'order' => 2, 'label' => 'Stage 2', 'selected' => false],
                    ['id' => 3, 'order' => 3, 'label' => 'Stage 3', 'selected' => false],
                ],
                ['stage_id' => 4, 'stage_name' => 'Stage 4']
            )
        );
        // Test case 7: desordered stages progress
        $this->assertEquals(
            33.33,
            StageProgressCalculator::getProgressStage(
                [
                    ['id' => 3, 'order' => 1, 'label' => 'Stage 3', 'selected' => false],
                    ['id' => 1, 'order' => 2, 'label' => 'Stage 1', 'selected' => false],
                    ['id' => 2, 'order' => 3, 'label' => 'Stage 2', 'selected' => false],
                ],
                ['stage_id' => 3, 'stage_name' => 'Stage 3']
            )
        );
    }

    /**
     * Test that the progress is always between 0 and 100
     */
    public function testProgressIsWithinBounds(): void
    {
        $allStages = [
            ['id' => 1, 'order' => 1, 'label' => 'Stage 1', 'selected' => false],
            ['id' => 2, 'order' => 2, 'label' => 'Stage 2', 'selected' => false],
        ];

        $currentStages = ['stage_id' => 1, 'stage_name' => 'Stage 1'];

        $result = StageProgressCalculator::getProgressStage($allStages, $currentStages);

        $this->assertGreaterThanOrEqual(0, $result, 'Progress should not be negative');
        $this->assertLessThanOrEqual(100, $result, 'Progress should not exceed 100%');
    }

    /**
     * Test that the progress is rounded to 2 decimal places
     */
    public function testProgressIsRounded(): void
    {
        $allStages = [
            ['id' => 1, 'order' => 1, 'label' => 'Stage 1', 'selected' => false],
            ['id' => 2, 'order' => 2, 'label' => 'Stage 2', 'selected' => false],
            ['id' => 3, 'order' => 3, 'label' => 'Stage 3', 'selected' => false],
        ];

        $currentStages = ['stage_id' => 1, 'stage_name' => 'Stage 1'];

        $result = StageProgressCalculator::getProgressStage($allStages, $currentStages);

        // Check if the result has at most 2 decimal places
        $this->assertMatchesRegularExpression('/^\d+\.\d{1,2}$/', (string) $result,
            'Progress should be rounded to 2 decimal places');
    }
}
