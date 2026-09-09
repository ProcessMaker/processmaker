<?php

namespace ProcessMaker\Exception;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use ProcessMaker\Models\Bundle;
use ProcessMaker\Models\BundleAsset;
use RuntimeException;

class BundleIntegrityException extends RuntimeException
{
    private array $invalidAssets;

    public function __construct(Bundle $bundle, Collection $invalidAssets)
    {
        $this->invalidAssets = $invalidAssets
            ->map(fn (BundleAsset $asset) => $asset->integrityDetails())
            ->values()
            ->all();

        parent::__construct(__(
            'The bundle :bundle contains unavailable assets and cannot be exported.',
            ['bundle' => $bundle->name]
        ));
    }

    public function invalidAssets(): array
    {
        return $this->invalidAssets;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 422,
                'message' => $this->getMessage(),
            ],
            'errors' => [
                'assets' => $this->invalidAssets,
            ],
        ], 422);
    }
}
