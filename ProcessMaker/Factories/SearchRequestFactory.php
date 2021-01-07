<?php declare(strict_types=1);

namespace ProcessMaker\Factories;

use ElasticScoutDriver\Factories\SearchRequestFactoryInterface;
use ElasticAdapter\Search\SearchRequest;
use Laravel\Scout\Builder;
use stdClass;

final class SearchRequestFactory implements SearchRequestFactoryInterface
{
    public function makeFromBuilder(Builder $builder, array $options = []): SearchRequest
    {
        $searchRequest = new SearchRequest($this->makeQuery($builder));

        if ($sort = $this->makeSort($builder)) {
            $searchRequest->setSort($sort);
        }

        if ($from = $this->makeFrom($options)) {
            $searchRequest->setFrom($from);
        }

        if ($size = $this->makeSize($builder, $options)) {
            $searchRequest->setSize($size);
        }
        
        $searchRequest->setSource('id');

        return $searchRequest;
    }

    protected function makeQuery(Builder $builder): array
    {
        $query = [
            'bool' => [],
        ];

        if (strlen($builder->query) > 0) {
            $query['bool']['must'] = [
                'query_string' => [
                    'query' => $builder->query,
                ],
            ];
        } else {
            $query['bool']['must'] = [
                'match_all' => new stdClass(),
            ];
        }

        if ($filter = $this->makeFilter($builder)) {
            $query['bool']['filter'] = $filter;
        }

        return $query;
    }

    protected function makeFilter(Builder $builder): ?array
    {
        $filter = collect($builder->wheres)->map(static function ($value, string $field) {
            return [
                'term' => [$field => $value],
            ];
        })->values();

        return $filter->isEmpty() ? null : $filter->all();
    }

    protected function makeSort(Builder $builder): ?array
    {
        $sort = collect($builder->orders)->map(static function (array $order) {
            return [
                $order['column'] => $order['direction'],
            ];
        });

        return $sort->isEmpty() ? null : $sort->all();
    }

    protected function makeFrom(array $options): ?int
    {
        if (isset($options['page']) && isset($options['perPage'])) {
            return ($options['page'] - 1) * $options['perPage'];
        }

        return null;
    }

    protected function makeSize(Builder $builder, array $options): ?int
    {
        return $options['perPage'] ?? $builder->limit;
    }
}
