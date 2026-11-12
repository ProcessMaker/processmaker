<?php

declare(strict_types=1);

namespace ProcessMaker\Support;

final class TaskDraftIncludes
{
    /** @var list<string> */
    public const EAGER_COLUMNS = ['id', 'task_id'];

    private const NESTED_DRAFT_RELATIONS = [
        'processRequest.tokens',
        'process.requests.tokens',
    ];

    /** @return list<string> */
    public static function parseIncludeList(string $include): array
    {
        if ($include === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $include))));
    }

    public static function includesDraft(array $includes): bool
    {
        foreach ($includes as $token) {
            if (self::isDraftToken($token)) {
                return true;
            }
        }

        return false;
    }

    public static function isDraftToken(string $token): bool
    {
        return $token === 'draft'
            || str_starts_with($token, 'draft.')
            || str_contains($token, '.draft');
    }

    public static function constrainedEagerLoad(): \Closure
    {
        return fn ($q) => $q->select(self::EAGER_COLUMNS);
    }

    /**
     * @return array<string, \Closure>
     */
    public static function nestedDraftEagerLoadMap(array $includes): array
    {
        $with = [];
        $constrained = fn ($q) => $q->with(['draft' => self::constrainedEagerLoad()]);

        foreach ($includes as $token) {
            if (!self::isDraftToken($token) || $token === 'draft' || str_starts_with($token, 'draft.')) {
                continue;
            }
            foreach (self::NESTED_DRAFT_RELATIONS as $relation) {
                if ($token === "{$relation}.draft" || str_starts_with($token, "{$relation}.")) {
                    $with[$relation] = $constrained;
                    break;
                }
            }
        }

        return $with;
    }
}
