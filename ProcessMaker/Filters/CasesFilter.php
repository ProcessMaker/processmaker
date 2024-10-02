<?php

namespace ProcessMaker\Filters;

class CasesFilter extends BaseFilter
{
    protected function valueAliasMethod()
    {
        if ($this->subjectType === self::TYPE_STATUS) {
            return 'valueAliasStatus';
        }

        return null;
    }
}
