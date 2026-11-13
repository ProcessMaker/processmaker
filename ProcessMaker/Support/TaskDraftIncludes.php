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
            $path = strtolower(self::relationPathFromIncludeToken($token));
            if ($path === 'draft' || str_starts_with($path, 'draft.')) {
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
            $path = strtolower(self::relationPathFromIncludeToken($token));
            if ($path === 'draft' || str_starts_with($path, 'draft.')) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Laravel include tokens may end with :col or :*; only the relation path is significant here.
     */
    public static function relationPathFromIncludeToken(string $token): string
    {
        $token = trim($token);
        if ($token === '') {
            return '';
        }

        $colonAt = strpos($token, ':');
        if ($colonAt === false) {
            return $token;
        }

        return substr($token, 0, $colonAt);
    }

    public static function isDraftToken(string $token): bool
    {
        $path = strtolower(self::relationPathFromIncludeToken($token));

        return $path === 'draft'
            || str_starts_with($path, 'draft.')
            || str_ends_with($path, '.draft')
            || str_contains($path, '.draft.');
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
            $path = strtolower(self::relationPathFromIncludeToken($token));
            if (!str_starts_with($path, 'draft.') || $path === 'draft') {
                continue;
            }
            $suffix = substr($path, strlen('draft.'));
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
            $path = strtolower(self::relationPathFromIncludeToken($token));
            if (!self::isDraftToken($token) || $path === 'draft' || str_starts_with($path, 'draft.')) {
                continue;
            }
            foreach (self::NESTED_DRAFT_RELATIONS as $relation) {
                $relationLower = strtolower($relation);
                if ($path === "{$relationLower}.draft" || str_starts_with($path, "{$relationLower}.")) {
                    $with[$relation] = $constrained;
                    break;
                }
            }
        }

        return $with;
    }
}
