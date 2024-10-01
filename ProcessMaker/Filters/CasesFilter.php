<?php

namespace ProcessMaker\Filters;

class CasesFilter extends BaseFilter
{
    public const TYPE_STATUS = 'Status';

    protected function valueAliasMethod()
    {
        if ($this->subjectType === self::TYPE_STATUS) {
            return 'valueAliasStatus';
        }

        return null;
    }
}
