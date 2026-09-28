<?php

namespace ProcessMaker\Exception;

use RuntimeException;
use Throwable;

class DevLinkRemoteBundleException extends RuntimeException
{
    public function __construct(array $invalidAssets, ?Throwable $previous = null)
    {
        $assets = collect($invalidAssets)->map(function (array $asset) {
            $type = class_basename($asset['asset_type'] ?? 'Asset');
            $id = $asset['asset_id'] ?? '?';
            $bundleAssetId = $asset['bundle_asset_id'] ?? '?';

            return "$type #$id (bundle asset #$bundleAssetId)";
        })->implode(', ');

        parent::__construct(__(
            'The remote bundle contains unavailable assets: :assets. Repair the bundle on the source instance and try again.',
            ['assets' => $assets]
        ), 0, $previous);
    }
}
