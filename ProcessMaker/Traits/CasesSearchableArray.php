<?php

namespace ProcessMaker\Traits;

trait CasesSearchableArray
{
    /**
     * Get the indexable data array for the model.
     */
    protected function searchableArray()
    {
        // Default searchable fields
        $searchable = [
            'case_number' => $this->case_number,
            'case_title' => $this->case_title,
        ];

        $columns = config('scout.cases.searchable');

        if ($columns) {
            $columns = explode(',', $columns);
            // Only include fillable columns
            $columns = array_intersect($columns, $this->getFillable());

            foreach ($columns as $column) {
                // Add column to searchable array
                $searchable[$column] = $this->{$column};
            }
        }

        return $searchable;
    }
}
