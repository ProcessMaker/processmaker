<?php

namespace ProcessMaker\Services\DataSourceIntegrations;

use Exception;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Services\DataSourceIntegrations\IntegrationsFactory;

class DataSourceIntegrationsService
{
    protected IntegrationsFactory $factory;

    protected ?string $currentSource = null;

    public function __construct(IntegrationsFactory $factory)
    {
        $this->factory = $factory;
    }

    public function setSource(string $source): self
    {
        dd('set source');
        $this->currentSource = $source;

        return $this;
    }

    public function getParameters(): array
    {
        // If a specific source is set, only return parameters for that source
        if ($this->currentSource) {
            $integration = $this->factory->create($this->currentSource);

            return [$this->currentSource => $integration->getParameters()];
        }

        // Otherwise, get all parameters from all available sources
        $allParameters = [];
        foreach ($this->factory->getSources() as $source) {
            try {
                $integration = $this->factory->create($source);
                $allParameters[$source] = $integration->getParameters();
            } catch (Exception $e) {
                // Log the error but continue with other sources
                Log::error("Error getting parameters for source: {$source}", [
                    'message' => $e->getMessage(),
                ]);
                $allParameters[$source] = ['error' => $e->getMessage()];
            }
        }

        return $allParameters;
    }

    public function getCompanies(array $params = []): array
    {
        if (!$this->currentSource) {
            return [];
        }

        $integration = $this->factory->create($this->currentSource);

        return $integration->getCompanies($params);
    }

    public function fetchCompanyDetails(string $source, string $companyId): array
    {
        $integration = $this->factory->create($source);

        return $integration->fetchCompanyDetails($companyId);
    }
}
