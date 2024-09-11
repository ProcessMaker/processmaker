<?php

namespace ProcessMaker\Http\Resources\V1_1;

use ProcessMaker\Http\Resources\ApiResource;

class CaseResource extends ApiResource
{
    protected static $defaultFields = [
        'case_number',
        'user_id',
        'case_title',
        'case_title_formatted',
        'case_status',
        'processes',
        'requests',
        'request_tokens',
        'tasks',
        'participants',
        'initiated_at',
        'completed_at',
    ];

    public function toArray($request): array
    {
        $data = [];

        foreach (static::$defaultFields as $field) {
            $data[$field] = $this->$field;
        }

        return $data;
    }
}
