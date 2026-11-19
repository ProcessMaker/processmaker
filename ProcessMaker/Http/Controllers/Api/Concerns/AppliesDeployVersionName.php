<?php

declare(strict_types=1);

namespace ProcessMaker\Http\Controllers\Api\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ProcessMaker\Services\DeployVersionName;

trait AppliesDeployVersionName
{
    private function deployVersionName(): DeployVersionName
    {
        return app(DeployVersionName::class);
    }

    /**
     * @param array<string, string> $rules
     * @return array<string, string>
     */
    protected function deployRulesForUpdate(array $rules, bool $isUpdate): array
    {
        return $this->deployVersionName()->mergeRules($rules, $isUpdate);
    }

    protected function ensureDeployVersionHistoryAvailable(bool $isUpdate): ?JsonResponse
    {
        if ($isUpdate && !$this->deployVersionName()->canRecordHistory()) {
            return response()->json([
                'success' => false,
                'message' => 'Version history (package-versions) is required to publish asset updates.',
            ], 422);
        }

        return null;
    }

    protected function latestPublishedVersionId(Model $asset): int
    {
        return $this->deployVersionName()->latestPublishedVersionId($asset);
    }

    protected function applyDeployVersionName(
        Model $asset,
        Request $request,
        bool $isUpdate,
        int $publishedVersionIdBefore,
    ): void {
        $versionName = $request->input('version_name');
        $description = $request->input('version_description');

        $this->deployVersionName()->applyAfterSave(
            $asset,
            is_string($versionName) ? $versionName : null,
            (int) $request->user()->id,
            $isUpdate,
            $publishedVersionIdBefore,
            is_string($description) ? $description : null,
        );
    }
}
