<?php

namespace ProcessMaker\Traits;

use Facades\ProcessMaker\JsonColumnIndex;

trait Indexable
{
    public function getFieldsFromPmql($pmql)
    {
        return ExtendedPMQL::getFields($pmql);
    }

    public function addJsonColumnsIndex($fields)
    {
        foreach ($fields as $field) {
            $exploded = explode('.', $field);
            $table = $this->table();

            // Only columns in format data.field
            if (count($exploded) > 1) {
                $column = $exploded[0];
                array_shift($exploded);
                $path = '$.' . implode('.', $exploded);
                \Log::info('Updating JSON path: ' . $path . ' indexes for table: ' . $table);
                JsonColumnIndex::add($table, $column, $path);
            }
        }
    }

    public function table()
    {
        if ($this->table !== 'saved_searches') {
            return $this->table;
        }

        // If table is saved search, return the corresponding table
        switch ($this->type) {
            case 'request':
                return 'process_requests';
                break;
            case 'task':
                return 'process_request_tokens';
                break;
            case 'collection':
                $collectionId = $this->meta->collection_id;
                return 'collection_' . $collectionId;
                break;
        }
    }
}
