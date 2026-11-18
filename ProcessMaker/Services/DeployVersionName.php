<?php

declare(strict_types=1);

namespace ProcessMaker\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
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

    public function applyAfterSave(Model $asset, ?string $versionName, int $userId, ?string $description = null): void
    {
        if ($versionName === null || trim($versionName) === '') {
            return;
        }

        if (!$this->canRecordHistory()) {
            return;
        }

        if (!method_exists($asset, 'versions')) {
            return;
        }

        $version = $asset->versions()->orderByDesc('id')->first();
        if ($version === null) {
            return;
        }

        $history = new VersionHistory();
        $history->user_id = $userId;
        $history->versionable_id = (int) $version->getKey();
        $history->versionable_type = $version::class;
        $history->subject = trim($versionName);
        $history->description = $description !== null && trim($description) !== ''
            ? trim($description)
            : null;
        $history->status = 'ACTIVE';
        $history->saveOrFail();
    }
}
