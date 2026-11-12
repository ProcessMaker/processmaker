<?php

declare(strict_types=1);

namespace ProcessMaker\Support;

final class TaskDraftIncludes
{
    /** @var list<string> */
    public const EAGER_COLUMNS = ['id', 'task_id'];

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
        return $token === 'draft' || str_starts_with($token, 'draft.');
    }

    public static function constrainedEagerLoad(): \Closure
    {
        return fn ($q) => $q->select(self::EAGER_COLUMNS);
    }
}
