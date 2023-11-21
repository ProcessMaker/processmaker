<?php

namespace ProcessMaker\Filters;

use Illuminate\Support\Arr;

// Represents a subject, operator, and value
class Filter
{
    const TYPE_PARTICIPANTS = 'Participants';

    const TYPE_STATUS = 'Status';

    const TYPE_FIELD = 'Field';

    public string|null $subjectValue;

    public string $subjectType;

    public string $operator;

    public $value;

    public array $or;

    public static function filter($query, string $filterDefinitions)
    {
        $filterDefinitions = json_decode($filterDefinitions, true);
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

    public function addToQuery($query)
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
        if ($this->subjectType === self::TYPE_PARTICIPANTS) {
            $this->participants($query);
        } elseif ($this->subjectType === self::TYPE_STATUS) {
            $this->status($query);
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

        return $this->subjectValue;
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

    private function participants($query)
    {
        $query->whereIn('id', function ($subQuery) {
            $subQuery->select('process_request_id')->from('process_request_tokens')
                ->whereIn('element_type', ['task', 'userTask', 'startEvent']);
            $this->applyQueryBuilderMethod($subQuery);
        });
    }

    private function customStatus($query)
    {
    }
}
