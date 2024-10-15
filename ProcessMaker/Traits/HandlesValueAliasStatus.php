<?php

namespace ProcessMaker\Traits;

use ProcessMaker\Constants\CaseStatusConstants;

trait HandlesValueAliasStatus
{
    public function valueAliasStatus($value, $expression)
    {
        $statusMap = [
            'in progress' => CaseStatusConstants::IN_PROGRESS,
            'completed' => CaseStatusConstants::COMPLETED,
            'error' => CaseStatusConstants::ERROR,
            'canceled' => CaseStatusConstants::CANCELED,
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
