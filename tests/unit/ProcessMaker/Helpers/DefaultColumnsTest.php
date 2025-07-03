<?php

namespace Tests\Unit\ProcessMaker\Helpers;

use ProcessMaker\Helpers\DefaultColumns;
use ProcessMaker\Package\SavedSearch\Models\SavedSearch;
use Tests\TestCase;

class DefaultColumnsTest extends TestCase
{
    /**
     * Test verifyDefaultColumns with null savedColumns
     */
    public function testVerifyDefaultColumnsWithNull()
    {
        $result = DefaultColumns::verifyDefaultColumns(null, SavedSearch::KEY_TASKS);
        $this->assertTrue($result);
    }

    /**
     * Test verifyDefaultColumns with non-array savedColumns
     */
    public function testVerifyDefaultColumnsWithNonArray()
    {
        $result = DefaultColumns::verifyDefaultColumns('not_an_array', SavedSearch::KEY_TASKS);
        $this->assertTrue($result);
    }

    /**
     * Test verifyDefaultColumns with invalid key
     */
    public function testVerifyDefaultColumnsWithInvalidKey()
    {
        $savedColumns = [
            ['field' => 'case_number', 'label' => 'Case #'],
            ['field' => 'case_title', 'label' => 'Case Title'],
        ];

        $result = DefaultColumns::verifyDefaultColumns($savedColumns, 'invalid_key');
        $this->assertTrue($result);
    }

    /**
     * Test verifyDefaultColumns with empty array
     */
    public function testVerifyDefaultColumnsWithEmptyArray()
    {
        $result = DefaultColumns::verifyDefaultColumns([], SavedSearch::KEY_TASKS);
        $this->assertTrue($result);
    }

    /**
     * Test verifyDefaultColumns with exact default columns for tasks (array format)
     */
    public function testVerifyDefaultColumnsWithExactDefaultColumnsForTasks()
    {
        $savedColumns = [
            ['field' => 'case_number', 'label' => 'Case #'],
            ['field' => 'case_title', 'label' => 'Case Title'],
            ['field' => 'is_priority', 'label' => 'Priority'],
            ['field' => 'element_name', 'label' => 'Task'],
            ['field' => 'status', 'label' => 'Status'],
            ['field' => 'due_at', 'label' => 'Due Date'],
        ];

        $result = DefaultColumns::verifyDefaultColumns($savedColumns, SavedSearch::KEY_TASKS);
        $this->assertTrue($result);
    }

    /**
     * Test verifyDefaultColumns with default columns in different order
     */
    public function testVerifyDefaultColumnsWithDifferentOrder()
    {
        $savedColumns = [
            ['field' => 'status', 'label' => 'Status'],
            ['field' => 'case_number', 'label' => 'Case #'],
            ['field' => 'due_at', 'label' => 'Due Date'],
            ['field' => 'element_name', 'label' => 'Task'],
            ['field' => 'case_title', 'label' => 'Case Title'],
            ['field' => 'is_priority', 'label' => 'Priority'],
        ];

        $result = DefaultColumns::verifyDefaultColumns($savedColumns, SavedSearch::KEY_TASKS);
        $this->assertTrue($result);
    }

    /**
     * Test verifyDefaultColumns with object format columns
     */
    public function testVerifyDefaultColumnsWithObjectFormat()
    {
        $savedColumns = [
            (object) ['field' => 'case_number', 'label' => 'Case #'],
            (object) ['field' => 'case_title', 'label' => 'Case Title'],
            (object) ['field' => 'is_priority', 'label' => 'Priority'],
            (object) ['field' => 'element_name', 'label' => 'Task'],
            (object) ['field' => 'status', 'label' => 'Status'],
            (object) ['field' => 'due_at', 'label' => 'Due Date'],
        ];

        $result = DefaultColumns::verifyDefaultColumns($savedColumns, SavedSearch::KEY_TASKS);
        $this->assertTrue($result);
    }

    /**
     * Test verifyDefaultColumns with missing default columns
     */
    public function testVerifyDefaultColumnsWithMissingColumns()
    {
        $savedColumns = [
            ['field' => 'case_number', 'label' => 'Case #'],
            ['field' => 'case_title', 'label' => 'Case Title'],
            ['field' => 'element_name', 'label' => 'Task'],
            // Missing: is_priority, status, due_at
        ];

        $result = DefaultColumns::verifyDefaultColumns($savedColumns, SavedSearch::KEY_TASKS);
        $this->assertFalse($result);
    }

    /**
     * Test verifyDefaultColumns with extra columns
     */
    public function testVerifyDefaultColumnsWithExtraColumns()
    {
        $savedColumns = [
            ['field' => 'case_number', 'label' => 'Case #'],
            ['field' => 'case_title', 'label' => 'Case Title'],
            ['field' => 'is_priority', 'label' => 'Priority'],
            ['field' => 'element_name', 'label' => 'Task'],
            ['field' => 'status', 'label' => 'Status'],
            ['field' => 'due_at', 'label' => 'Due Date'],
            ['field' => 'custom_field', 'label' => 'Custom Field'],
        ];

        $result = DefaultColumns::verifyDefaultColumns($savedColumns, SavedSearch::KEY_TASKS);
        $this->assertFalse($result);
    }

    /**
     * Test verifyDefaultColumns with different field names
     */
    public function testVerifyDefaultColumnsWithDifferentFieldNames()
    {
        $savedColumns = [
            ['field' => 'case_number', 'label' => 'Case #'],
            ['field' => 'case_title', 'label' => 'Case Title'],
            ['field' => 'priority', 'label' => 'Priority'],
            ['field' => 'element_name', 'label' => 'Task'],
            ['field' => 'status', 'label' => 'Status'],
            ['field' => 'due_at', 'label' => 'Due Date'],
        ];

        $result = DefaultColumns::verifyDefaultColumns($savedColumns, SavedSearch::KEY_TASKS);
        $this->assertFalse($result);
    }

    /**
     * Test verifyDefaultColumns with columns missing field property
     */
    public function testVerifyDefaultColumnsWithMissingFieldProperty()
    {
        $savedColumns = [
            ['field' => 'case_number', 'label' => 'Case #'],
            ['label' => 'Case Title'],
            ['field' => 'is_priority', 'label' => 'Priority'],
            ['field' => 'element_name', 'label' => 'Task'],
            ['field' => 'status', 'label' => 'Status'],
            ['field' => 'due_at', 'label' => 'Due Date'],
        ];

        $result = DefaultColumns::verifyDefaultColumns($savedColumns, SavedSearch::KEY_TASKS);
        $this->assertFalse($result);
    }

    /**
     * Test verifyDefaultColumns with additional properties in columns
     */
    public function testVerifyDefaultColumnsWithAdditionalProperties()
    {
        $savedColumns = [
            ['field' => 'case_number', 'label' => 'Case #', 'sortable' => true, 'width' => 100],
            ['field' => 'case_title', 'label' => 'Case Title', 'sortable' => true, 'width' => 200],
            ['field' => 'is_priority', 'label' => 'Priority', 'sortable' => false, 'width' => 50],
            ['field' => 'element_name', 'label' => 'Task', 'sortable' => true, 'width' => 150],
            ['field' => 'status', 'label' => 'Status', 'sortable' => true, 'width' => 100],
            ['field' => 'due_at', 'label' => 'Due Date', 'sortable' => true, 'width' => 120],
        ];

        $result = DefaultColumns::verifyDefaultColumns($savedColumns, SavedSearch::KEY_TASKS);
        $this->assertTrue($result);
    }
}
