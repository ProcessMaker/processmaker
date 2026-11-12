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
        $lower = strtolower($token);

        return $lower === 'draft'
            || str_starts_with($lower, 'draft.')
            || str_ends_with($lower, '.draft')
            || str_contains($lower, '.draft.');
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
            $lower = strtolower($token);
            if (!self::isDraftToken($token) || $lower === 'draft' || str_starts_with($lower, 'draft.')) {
                continue;
            }
            foreach (self::NESTED_DRAFT_RELATIONS as $relation) {
                $relationLower = strtolower($relation);
                if ($lower === "{$relationLower}.draft" || str_starts_with($lower, "{$relationLower}.")) {
                    $with[$relation] = $constrained;
                    break;
                }
            }
        }

        return $with;
    }
}
