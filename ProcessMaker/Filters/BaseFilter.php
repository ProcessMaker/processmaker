<?php

namespace ProcessMaker\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ProcessMaker\Traits\InteractsWithRawFilter;

abstract class BaseFilter
{
    use InteractsWithRawFilter;

    public const TYPE_PARTICIPANTS = 'Participants';

    public const TYPE_PARTICIPANTS_FULLNAME = 'ParticipantsFullName';

    public const TYPE_ASSIGNEES_FULLNAME = 'AssigneesFullName';

    public const TYPE_STATUS = 'Status';

    public const TYPE_ALTERNATIVE = 'Alternative';

    public const TYPE_FIELD = 'Field';

    public const TYPE_PROCESS = 'Process';

    public const TYPE_PROCESS_NAME = 'ProcessName';

    public const PROCESS_NAME_IN_REQUEST = 'process_request.name';

    public const TYPE_RELATIONSHIP = 'Relationship';

    public string|null $subjectValue;

    public string $subjectType;

    public string $operator;

    public $value;

    public array $or;

    public array $operatorWhitelist = [
        '=',
        '!=',
        '>',
        '<',
        '>=',
        '<=',
        'between',
        'in',
        'contains',
        'regex',
        'starts_with',
    ];

    public function __construct($definition)
    {
        $this->subjectType = $definition['subject']['type'];
        $this->subjectValue = Arr::get($definition, 'subject.value');
        $this->operator = $definition['operator'];
        $this->value = $definition['value'];
        $this->or = Arr::get($definition, 'or', []);

        $this->detectRawValue();
    }

    public static function filter(Builder $query, string|array $filterDefinitions): void
    {
        if (is_string($filterDefinitions)) {
            $filterDefinitions = json_decode($filterDefinitions, true);
        }

        if (!$filterDefinitions) {
            return;
        }

        $query->where(function ($query) use ($filterDefinitions) {
            foreach ($filterDefinitions as $filter) {
                (new static($filter))->addToQuery($query);
            }
        });
    }

    public function addToQuery(Builder $query): void
    {
        if (!empty($this->or)) {
            $query->where(fn ($query) => $this->apply($query));
        } else {
            $this->apply($query);
        }
    }

    private function apply($query): void
    {
        if ($valueAliasMethod = $this->valueAliasMethod()) {
            $this->valueAliasAdapter($valueAliasMethod, $query);
        } elseif ($this->subjectType === self::TYPE_PROCESS) {
            $this->filterByProcessId($query);
        } elseif ($this->subjectValue === self::PROCESS_NAME_IN_REQUEST) {
            // For performance reasons, the task list uses the column process_request.name
            // But the filters must use the Process table, for this reason the subjectType is updated
            $this->subjectType = self::TYPE_PROCESS_NAME;
            $this->filterByProcessName($query);
        } elseif ($this->subjectType === self::TYPE_PROCESS_NAME) {
            $this->filterByProcessName($query);
        } elseif ($this->subjectType === self::TYPE_RELATIONSHIP) {
            $this->filterByRelationship($query);
        } elseif ($this->isJsonData() && $query->getModel() instanceof ProcessRequestToken) {
            $this->filterByRequestData($query);
        } else {
            $this->applyQueryBuilderMethod($query);
        }

        if (!empty($this->or)) {
            $query->orWhere(function ($orQuery) {
                foreach ($this->or as $or) {
                    (new static($or))->addToQuery($orQuery);
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
        } elseif ($this->isJsonData()) {
            $this->manuallyAddJsonWhere($query);
        } else {
            $query->$method(
                $this->subject(),
                $this->operator(),
                $this->value(),
            );
        }
    }

    /**
     * We must do this manually because Laravel bindings cast
     * floats/doubles to strings and that wont work to compare
     * json values
     *
     * @param [type] $query
     * @return void
     */
    private function manuallyAddJsonWhere($query): void
    {
        $parts = explode('.', $this->subjectValue);

        array_shift($parts);

        $selector = implode('"."', $parts);
        $operator = $this->operator();
        $value = $this->value();

        if (!is_numeric($value)) {
            $value = DB::connection()->getPdo()->quote($value);
        }

        if ($operator === 'like') {
            // For JSON data is required to do a CAST in order to make insensitive the comparison
            $query->whereRaw(
                "cast(json_unquote(json_extract(`data`, '$.\"{$selector}\"')) as CHAR) {$operator} {$value}"
            );
        } else {
            $query->whereRaw("json_unquote(json_extract(`data`, '$.\"{$selector}\"')) {$operator} {$value}");
        }
    }

    private function operator()
    {
        if (!in_array($this->operator, $this->operatorWhitelist)) {
            abort(422, "Invalid operator: {$this->operator}");
        }

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

        if ($this->subjectType === self::TYPE_PROCESS_NAME) {
            return 'name';
        }

        return $this->subjectValue;
    }

    private function relationshipSubjectTypeParts(): array
    {
        return explode('.', $this->subjectValue);
    }

    public function value()
    {
        if ($this->operator === 'contains') {
            return '%' . $this->value . '%';
        }

        if ($this->operator === 'starts_with') {
            return $this->value . '%';
        }

        if ($this->filteringWithRawValue()) {
            return $this->getRawValue();
        }

        return $this->value;
    }

    abstract protected function valueAliasMethod();

    private function valueAliasAdapter(string $method, Builder $query): void
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

    private function filterByProcessId(Builder $query): void
    {
        if ($query->getModel() instanceof ProcessRequestToken) {
            $query->whereIn('process_request_id', function ($query) {
                $query->select('id')
                      ->from('process_requests')
                      ->whereIn('process_id', (array) $this->value());
            });
        } else {
            $this->applyQueryBuilderMethod($query);
        }
    }

    private function filterByProcessName(Builder $query): void
    {
        if ($query->getModel() instanceof ProcessRequestToken) {
            $query->whereIn('process_request_id', function ($query) {
                $query->select('id')->from('process_requests');
                $this->applyQueryBuilderMethod($query);
            });
        } else {
            $query->whereIn('name', (array) $this->value());
        }
    }

    private function filterByRelationship(Builder $query): void
    {
        $relationshipName = $this->relationshipSubjectTypeParts()[0];
        $query->whereHas($relationshipName, function ($rel) {
            $this->applyQueryBuilderMethod($rel);
        });
    }

    private function filterByRequestData(Builder $query): void
    {
        $query->whereHas('processRequest', function ($rel) {
            $this->applyQueryBuilderMethod($rel);
        });
    }
}
