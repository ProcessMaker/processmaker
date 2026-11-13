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
        return self::includesRootDraft($includes) || self::includesNestedDraft($includes);
    }

    public static function includesRootDraft(array $includes): bool
    {
        foreach ($includes as $token) {
            $lower = strtolower(trim($token));
            if ($lower === 'draft' || str_starts_with($lower, 'draft.')) {
                return true;
            }
        }

        return false;
    }

    public static function includesNestedDraft(array $includes): bool
    {
        foreach ($includes as $token) {
            if (!self::isDraftToken($token)) {
                continue;
            }
            $lower = strtolower(trim($token));
            if ($lower === 'draft' || str_starts_with($lower, 'draft.')) {
                continue;
            }

            return true;
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
     * @return list<string>
     */
    public static function rootDraftNestedRelations(array $includes): array
    {
        $relations = [];
        foreach ($includes as $token) {
            $lower = strtolower(trim($token));
            if (!str_starts_with($lower, 'draft.') || $lower === 'draft') {
                continue;
            }
            $suffix = substr($lower, strlen('draft.'));
            if ($suffix !== '') {
                $relations[] = $suffix;
            }
        }

        return array_values(array_unique($relations));
    }

    public static function rootDraftEagerLoad(array $includes): \Closure
    {
        $nested = self::rootDraftNestedRelations($includes);

        return function ($q) use ($nested) {
            $q->select(self::EAGER_COLUMNS);
            if ($nested !== []) {
                $q->with($nested);
            }
        };
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
