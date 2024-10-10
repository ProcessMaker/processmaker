<?php

namespace ProcessMaker\Traits;

trait HandlesValueAliasStatus
{
    public function valueAliasStatus($value, $expression)
    {
        $statusMap = [
            'in progress' => 'IN_PROGRESS',
            'completed' => 'COMPLETED',
            'error' => 'ERROR',
            'canceled' => 'CANCELED',
        ];

        $value = mb_strtolower($value);

        return function ($query) use ($value, $statusMap, $expression) {
            if (array_key_exists($value, $statusMap)) {
                $value = $statusMap[$value];
            }
            $query->where('case_status', $expression->operator, $value);
        };
    }
}
