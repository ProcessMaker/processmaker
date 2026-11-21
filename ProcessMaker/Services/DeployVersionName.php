<?php

declare(strict_types=1);

namespace ProcessMaker\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use ProcessMaker\Package\Versions\Models\VersionHistory;

final class DeployVersionName
{
    /**
     * @return array<string, string>
     */
    public function validationRules(bool $isUpdate): array
    {
        if ($isUpdate) {
            return ['version_name' => 'required|string|min:1|max:255'];
        }

        return ['version_name' => 'nullable|string|max:255'];
    }

    /**
     * @param array<string, string> $rules
     * @return array<string, string>
     */
    public function mergeRules(array $rules, bool $isUpdate): array
    {
        return array_merge($rules, $this->validationRules($isUpdate));
    }

    public function canRecordHistory(): bool
    {
        return class_exists(VersionHistory::class)
            && Schema::hasTable('version_history');
    }

    public function latestPublishedVersionId(Model $asset): int
    {
        if (!method_exists($asset, 'versions')) {
            return 0;
        }

        $maxId = $asset->versions()->published()->max('id');

        return $maxId !== null ? (int) $maxId : 0;
    }

    public function applyAfterSave(
        Model $asset,
        ?string $versionName,
        int $userId,
        bool $isUpdate,
        int $publishedVersionIdBefore,
        ?string $description = null,
    ): void {
        $subject = $versionName !== null ? trim($versionName) : '';
        if (
            $subject === ''
            || !$this->canRecordHistory()
            || !method_exists($asset, 'versions')
        ) {
            return;
        }

        $version = $this->resolvePublishedVersionAfterSave($asset, $publishedVersionIdBefore)
            ?? $this->resolveCurrentPublishedForRedeploy($asset, $isUpdate, $publishedVersionIdBefore);
        if ($version === null) {
            if ($isUpdate) {
                throw ValidationException::withMessages([
                    'version_name' => 'Deploy did not publish a new asset version.',
                ]);
            }

            return;
        }

        $history = new VersionHistory();
        $history->user_id = $userId;
        $history->versionable_id = (int) $version->getKey();
        $history->versionable_type = $version::class;
        $history->subject = $subject;
        $history->description = $description !== null && trim($description) !== ''
            ? trim($description)
            : null;
        $history->status = 'ACTIVE';
        $history->saveOrFail();
    }

    private function resolvePublishedVersionAfterSave(Model $asset, int $publishedVersionIdBefore): ?Model
    {
        $version = $asset->versions()->published()->orderByDesc('id')->first();
        if ($version === null) {
            return null;
        }

        if ((int) $version->getKey() <= $publishedVersionIdBefore) {
            return null;
        }

        return $version;
    }

    /**
     * Scripts can skip publishing when a draft exists and versionable content is unchanged.
     * Still record deploy version history on the current published row (idempotent upsert).
     */
    private function resolveCurrentPublishedForRedeploy(
        Model $asset,
        bool $isUpdate,
        int $publishedVersionIdBefore,
    ): ?Model {
        if (!$isUpdate || $publishedVersionIdBefore < 1) {
            return null;
        }

        $version = $asset->versions()->published()->orderByDesc('id')->first();
        if ($version === null) {
            return null;
        }

        return (int) $version->getKey() === $publishedVersionIdBefore ? $version : null;
    }
}
