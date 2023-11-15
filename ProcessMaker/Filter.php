<?php

namespace ProcessMaker;

class Filter
{
    public function filter($query, $filterDefinitions)
    {
        foreach ($filterDefinitions as $filter) {
            $subject = $filter['subject'];
            $operator = $filter['operator'];
            $value = $filter['value'];

            switch($operator) {
                case 'in':
                    $method = 'whereIn';
                    if ($subject['type'] === 'FormData') {
                        $method = 'whereJsonContains';
                    }
                    $query->$method(
                        $this->parseSubject($subject),
                        $this->parseValue($value),
                    );
                    break;
                default:
                    $query->where(
                        $this->parseSubject($subject),
                        $this->parseOperator($operator),
                        $this->parseValue($value)
                    );
            }

            if (isset($filter['or'])) {
                $query->orWhere(function ($orQuery) use ($filter) {
                    $this->filter($orQuery, $filter['or']);
                });
            }
        }
    }

    private function parseSubject(array $subject)
    {
        switch ($subject['type']) {
            case 'FormData' :
                return 'data->' . $subject['value'];
            default:
                return $subject['value'];
        }
    }

    private function parseOperator(string $operator)
    {
        return $operator;
    }

    private function parseValue(array $value)
    {
        switch ($value['type']) {
            default:
                return $value['value'];
        }
    }
}
