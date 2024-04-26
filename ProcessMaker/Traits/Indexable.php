<?php

namespace ProcessMaker\Traits;

use DomainException;
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
            $table = $this->getTableToLook($this->table, $this->type, $this->meta);

            [$column, $path] = $this->getColumnAndPath($field);

            if ($column && $path) {
                \Log::info('Updating JSON path: ' . $path . ' indexes for table: ' . $table);
                JsonColumnIndex::add($table, $column, $path);
            }
        }
    }

    public function addJsonColumnsIndexBatch($indexes)
    {
        foreach ($indexes as $index) {
            [$column, $path] = $this->getColumnAndPath($index['field']);
            JsonColumnIndex::add($index['table'], $column, $path);
        }
    }

    public function deleteJsonColumnsIndexBatch($indexes)
    {
        foreach ($indexes as $index) {
            [$column, $path] = $this->getColumnAndPath($index['field']);
            JsonColumnIndex::remove($index['table'], $column, $path);
        }
    }

    private function getColumnAndPath($field)
    {
        $exploded = explode('.', $field);

        // Only columns in format data.field
        if (count($exploded) > 1) {
            $column = $exploded[0];
            array_shift($exploded);
            $path = '$.' . implode('.', $exploded);

            return [$column, $path];
        }

        return null;
    }

    public function getTableToLook($table, $type, $meta)
    {
        if ($table !== 'saved_searches') {
            return $table;
        }

        // If table is saved search, return the corresponding table
        switch ($type) {
            case 'request':
            case 'task':
                return 'process_requests';
            case 'collection':
                if ($meta && isset($meta->collection_id)) {
                    $collectionId = $meta->collection_id;

                    return 'collection_' . $collectionId;
                }
            default:
                throw new DomainException('Unknown saved search type: ' . $type);
        }
    }
}
