<?php

namespace ProcessMaker\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ProcessMaker\Query\BaseField;
use ProcessMaker\Query\Expression;

class Filter
{
    const TYPE_PARTICIPANTS = 'Participants';

    const TYPE_STATUS = 'Status';

    const TYPE_FIELD = 'Field';

    const TYPE_PROCESS = 'Process';

    const TYPE_RELATIONSHIP = 'Relationship';

    public string|null $subjectValue;

    public string $subjectType;

    public string $operator;

    public $value;

    public array $or;

    public static function filter(Builder $query, string $filterDefinitions)
    {
        $filterDefinitions = json_decode($filterDefinitions, true);
        if (!isset($filterDefinitions)) {
            // If the value is incorrect, we return a filter that produces an empty result.
            $default = '{"subject":{"type":"Field","value":"id"},"operator":"=","value":""}';
            $filterDefinitions = [json_decode($default, true)];
        }
        $query->where(function ($query) use ($filterDefinitions) {
            foreach ($filterDefinitions as $filter) {
                (new self($filter))->addToQuery($query);
            }
        });
    }

    public function __construct($definition)
    {
        $this->subjectType = $definition['subject']['type'];
        $this->subjectValue = Arr::get($definition, 'subject.value');
        $this->operator = $definition['operator'];
        $this->value = $definition['value'];
        $this->or = Arr::get($definition, 'or', []);
    }

    public function addToQuery(Builder $query)
    {
        if (!empty($this->or)) {
            $query->where(function ($query) {
                $this->apply($query);
            });
        } else {
            $this->apply($query);
        }
    }

    private function apply($query)
    {
        if ($valueAliasMethod = $this->valueAliasMethod()) {
            $this->valueAliasAdapter($valueAliasMethod, $query);
        } elseif ($this->subjectType === self::TYPE_PROCESS) {
            $this->filterByProcessId($query);
        } elseif ($this->subjectType === self::TYPE_RELATIONSHIP) {
            $this->filterByRelationship($query);
        } else {
            $this->applyQueryBuilderMethod($query);
        }

        if (!empty($this->or)) {
            $query->orWhere(function ($orQuery) {
                foreach ($this->or as $or) {
                    (new self($or))->addToQuery($orQuery);
                }
            });
        }
    }

    private function applyQueryBuilderMethod($query)
    {
        $method = $this->method();

        if (in_array($method, ['whereIn', 'whereBetween', 'whereJsonContains'])) {
            $query->$method(
                $this->subject(),
                $this->value(),
            );
        } else {
            $query->$method(
                $this->subject(),
                $this->operator(),
                $this->value(),
            );
        }
    }

    private function operator()
    {
        if ($this->operator === 'contains' || $this->operator === 'starts_with') {
            return 'like';
        }

        if ($this->operator === 'regex') {
            $this->operator = 'REGEXP';
        }

        return $this->operator;
    }

    private function method()
    {
        switch($this->operator) {
            case 'in':
                $method = 'whereIn';
                if ($this->isJsonData()) {
                    $method = 'whereJsonContains';
                }
                break;
            case 'between':
                $method = 'whereBetween';
                break;
            default:
                $method = 'where';
        }

        return $method;
    }

    private function isJsonData()
    {
        return $this->subjectType === self::TYPE_FIELD && str_starts_with($this->subjectValue, 'data.');
    }

    private function subject()
    {
        if ($this->isJsonData()) {
            return str_replace('.', '->', $this->subjectValue);
        }

        if ($this->subjectType === self::TYPE_PARTICIPANTS) {
            return 'user_id';
        }

        if ($this->subjectType === self::TYPE_PROCESS) {
            return 'process_id';
        }

        if ($this->subjectType === self::TYPE_RELATIONSHIP) {
            return $this->relationshipSubjectTypeParts()[1];
        }

        return $this->subjectValue;
    }

    private function relationshipSubjectTypeParts()
    {
        return explode('.', $this->subjectValue);
    }

    private function value()
    {
        if ($this->operator === 'contains') {
            return '%' . $this->value . '%';
        }

        if ($this->operator === 'starts_with') {
            return $this->value . '%';
        }

        return $this->value;
    }

    /**
     * Forward Status and Participant subjects to PMQL methods on the models.
     *
     * For now, we only need Participants and Status because Request and Requester
     * are columns on the tables (process_request_id and user_id).
     */
    private function valueAliasMethod()
    {
        $method = null;

        switch ($this->subjectType) {
            case self::TYPE_PARTICIPANTS:
                $method = 'valueAliasParticipant';
                break;
            case self::TYPE_STATUS:
                $method = 'valueAliasStatus';
                break;
        }

        return $method;
    }

    private function valueAliasAdapter(string $method, Builder $query)
    {
        $operator = $this->operator();
        if ($operator === 'in') {
            $operator = '=';
        }
        $values = (array) $this->value();

        $expression = (object) ['operator' => $operator];

        $model = $query->getModel();

        if ($method === 'valueAliasParticipant') {
            $values = $this->convertUserIdsToUsernames($values);
        }

        foreach ($values as $i => $value) {
            if ($i === 0) {
                $query->where($model->$method($value, $expression));
            } else {
                $query->orWhere($model->$method($value, $expression));
            }
        }
    }

    private function convertUserIdsToUsernames($values)
    {
        return array_map(function ($value) {
            $username = User::find($value)?->username;

            return isset($username) ? $username : $value;
        }, $values);
    }

    private function filterByProcessId(Builder $query)
    {
        if ($query->getModel() instanceof ProcessRequestToken) {
            $query->whereIn('process_request_id', function ($query) {
                $query->select('id')->from('process_requests')
                    ->whereIn('process_id', (array) $this->value());
            });
        } else {
            $this->applyQueryBuilderMethod($query);
        }
    }

    private function filterByRelationship(Builder $query)
    {
        $relationshipName = $this->relationshipSubjectTypeParts()[0];
        $query->whereHas($relationshipName, function ($rel) {
            $this->applyQueryBuilderMethod($rel);
        });
    }
}
