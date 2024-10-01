<?php

namespace ProcessMaker\Filters\V1_1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\User;
use ProcessMaker\Traits\InteractsWithRawFilter;

class Filter
{
    use InteractsWithRawFilter;

    public const TYPE_STATUS = 'Status';

    public const TYPE_FIELD = 'Field';

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
                (new self($filter))->addToQuery($query);
            }
        });
    }

    public function addToQuery($query): void
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

        return $this->subjectValue;
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

    private function valueAliasMethod()
    {
        $method = null;

        switch ($this->subjectType) {
            case self::TYPE_STATUS:
                $method = 'valueAliasStatus';
                break;
        }

        return $method;
    }

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
}
