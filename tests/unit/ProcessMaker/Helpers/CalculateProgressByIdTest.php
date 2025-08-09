<?php

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;

class CalculateProgressByIdTest extends TestCase
{
    /**
     * Test calculateProgressById helper method with various scenarios
     */
    public function testCalculateProgressByOrder(): void
    {
        // Test case 0: null stages
        $this->assertEquals(
            0.0,
            calculateProgressById(null, null)
        );

        // Test case 1: Empty stages
        $this->assertEquals(
            0.0,
            calculateProgressById(null, [])
        );

        // Test case 2: Empty current stage
        $this->assertEquals(
            0.0,
            calculateProgressById(0, [])
        );

        // Test case 3: First stage progress
        $this->assertEquals(
            25.0,
            calculateProgressById(1, [
                ['id' => 1, 'order' => 1, 'name' => 'Stage 1', 'selected' => false],
                ['id' => 2, 'order' => 2, 'name' => 'Stage 2', 'selected' => false],
                ['id' => 3, 'order' => 3, 'name' => 'Stage 3', 'selected' => false],
            ])
        );

        // Test case 4: Middle stage progress
        $this->assertEquals(
            50.0,
            calculateProgressById(2, [
                ['id' => 1, 'order' => 1, 'name' => 'Stage 1', 'selected' => false],
                ['id' => 2, 'order' => 2, 'name' => 'Stage 2', 'selected' => false],
                ['id' => 3, 'order' => 3, 'name' => 'Stage 3', 'selected' => false],
            ])
        );

        // Test case 5: Last stage progress
        $this->assertEquals(
            75.0,
            calculateProgressById(3, [
                ['id' => 1, 'order' => 1, 'name' => 'Stage 1', 'selected' => false],
                ['id' => 2, 'order' => 2, 'name' => 'Stage 2', 'selected' => false],
                ['id' => 3, 'order' => 3, 'name' => 'Stage 3', 'selected' => false],
            ])
        );

        // Test case 6: Non-existing stage progress
        $this->assertEquals(
            0.0,
            calculateProgressById(4, [
                ['id' => 1, 'order' => 1, 'name' => 'Stage 1', 'selected' => false],
                ['id' => 2, 'order' => 2, 'name' => 'Stage 2', 'selected' => false],
                ['id' => 3, 'order' => 3, 'name' => 'Stage 3', 'selected' => false],
            ])
        );

        // Test case 7: Disordered stages progress
        $this->assertEquals(
            25.0,
            calculateProgressById(3, [
                ['id' => 3, 'order' => 1, 'name' => 'Stage 3', 'selected' => false],
                ['id' => 1, 'order' => 2, 'name' => 'Stage 1', 'selected' => false],
                ['id' => 2, 'order' => 3, 'name' => 'Stage 2', 'selected' => false],
            ])
        );
    }

    /**
     * Test that the progress is rounded to 2 decimal places using calculateProgressById
     */
    public function testCalculateProgressByIdIsRounded(): void
    {
        $allStages = [
            ['id' => 1, 'order' => 1, 'name' => 'Stage 1', 'selected' => false],
            ['id' => 2, 'order' => 2, 'name' => 'Stage 2', 'selected' => false],
            ['id' => 3, 'order' => 3, 'name' => 'Stage 3', 'selected' => false],
        ];

        $result = calculateProgressById(1, $allStages);

        // Check if the result is a valid number (integer or decimal with max 2 decimal places)
        $this->assertMatchesRegularExpression('/^\d+(\.\d{1,2})?$/', (string) $result,
            'Progress should be a valid number with at most 2 decimal places');
    }
}
