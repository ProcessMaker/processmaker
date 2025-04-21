<?php

namespace ProcessMaker\Services\DataSourceIntegrations;

use InvalidArgumentException;
use ProcessMaker\Exception\DataSourceIntegrationException\UnsupportedDataSourceException;
use ProcessMaker\Services\DataSourceIntegrations\Integrations\CrunchbaseService;
use ProcessMaker\Services\DataSourceIntegrations\Integrations\IntegrationsInterface;
use ProcessMaker\Services\DataSourceIntegrations\Integrations\PitchbookService;

class IntegrationsFactory
{
    public static function create(string $source): IntegrationsInterface
    {
        return match (strtolower($source)) {
            'pitchbook' => new PitchbookService(),
            'crunchbase' => new CrunchbaseService(),
            default => throw new UnsupportedDataSourceException("Unsupported data source: {$source}"),
        };
    }

    public function getSources(): array
    {
        return ['pitchbook', 'crunchbase'];
    }
}
