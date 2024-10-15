<?php

namespace ProcessMaker\Filters;

use Illuminate\Database\Eloquent\Builder;

class Filter extends BaseFilter
{
    public const TYPE_PARTICIPANTS = 'Participants';

    public const TYPE_PROCESS = 'Process';

    public const TYPE_RELATIONSHIP = 'Relationship';

    /**
     * Forward Status and Participant subjects to PMQL methods on the models.
     *
     * For now, we only need Participants and Status because Request and Requester
     * are columns on the tables (process_request_id and user_id).
     */
    protected function valueAliasMethod()
    {
        $method = null;

        switch ($this->subjectType) {
            case self::TYPE_PARTICIPANTS:
                $method = 'valueAliasParticipant';
                break;
            case self::TYPE_PARTICIPANTS_FULLNAME:
                $method = 'valueAliasParticipantByFullName';
                break;
            case self::TYPE_ASSIGNEES_FULLNAME:
                $method = 'valueAliasAssigneeByFullName';
                break;
            case self::TYPE_STATUS:
                $method = 'valueAliasStatus';
                break;
            case self::TYPE_ALTERNATIVE:
                $method = 'valueAliasAlternative';
                break;
        }

        return $method;
    }
}
