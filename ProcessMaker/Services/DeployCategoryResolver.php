<?php

declare(strict_types=1);

namespace ProcessMaker\Services;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\ScriptCategory;

class DeployCategoryResolver
{
    private const UNCATEGORIZED = 'Uncategorized';

    public function resolveProcessCategoryId(?string $name): ?int
    {
        return $this->resolveActiveCategoryId(ProcessCategory::class, $name);
    }

    public function resolveScreenCategoryId(?string $name): ?int
    {
        return $this->resolveActiveCategoryId(ScreenCategory::class, $name);
    }

    public function resolveScriptCategoryId(?string $name): ?int
    {
        return $this->resolveActiveCategoryId(ScriptCategory::class, $name);
    }

    /**
     * @param class-string<Model> $modelClass
     */
    private function resolveActiveCategoryId(string $modelClass, ?string $name): ?int
    {
        $trimmed = $name !== null ? trim($name) : '';
        if ($trimmed === '') {
            $trimmed = self::UNCATEGORIZED;
        }

        $category = $modelClass::query()
            ->where('name', $trimmed)
            ->where('status', 'ACTIVE')
            ->first();

        if ($category !== null) {
            return (int) $category->getKey();
        }

        if ($trimmed !== self::UNCATEGORIZED) {
            return $this->resolveActiveCategoryId($modelClass, self::UNCATEGORIZED);
        }

        return null;
    }
}
