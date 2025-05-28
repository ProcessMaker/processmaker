<?php

namespace ProcessMaker\Services\DataSourceIntegrations;

use Exception;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Exception\DataSourceIntegrationException;
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
        $this->currentSource = $source;

        return $this;
    }

    public function getParameters(): array
    {
        // If a specific source is set, only return parameters for that source
        if ($this->currentSource) {
            try {
                $integration = $this->factory->create($this->currentSource);

                return [$this->currentSource => $integration->getParameters()];
            } catch (DataSourceIntegrationException $e) {
                // Log the error but continue with other sources
                Log::error($e->getMessage(), [
                    'source' => $this->currentSource,
                    'exception' => get_class($e),
                ]);
            }
        }

        // Otherwise, get all parameters from all available sources
        $allParameters = [];
        foreach ($this->factory->getSources() as $source) {
            try {
                $integration = $this->factory->create($source);
                $allParameters[$source] = $integration->getParameters();
            } catch (DataSourceIntegrationException $e) {
                // Log the error but continue with other sources
                Log::error($e->getMessage(), [
                    'source' => $source,
                    'exception' => get_class($e),
                ]);
                $allParameters[$source] = ['error' => $e->getMessage()];
            }
        }

        return $allParameters;
    }

    public function getCompanies(array $params = []): array
    {
        // If a specific source is set, only return companies for that source
        if ($this->currentSource) {
            try {
                $integration = $this->factory->create($this->currentSource);

                return $integration->getCompanies($params);
            } catch (DataSourceIntegrationException $e) {
                // Log the error but continue with other sources
                Log::error($e->getMessage(), [
                    'source' => $this->currentSource,
                    'exception' => get_class($e),
                ]);
            }
        }

        // Otherwise, get all companies from all available sources
        $allCompanies = [];
        foreach ($this->factory->getSources() as $source) {
            try {
                $integration = $this->factory->create($source);
                $allSourcesCompanies = $integration->getCompanies($params);
                $allCompanies = array_merge($allCompanies, $allSourcesCompanies);
            } catch (DataSourceIntegrationException $e) {
                // Log the error but continue with other sources
                Log::error($e->getMessage(), [
                    'source' => $source,
                    'exception' => get_class($e),
                ]);
                $allCompanies[$source] = ['error' => $e->getMessage()];
            }
        }

        return $allCompanies;
    }

    public function fetchCompanyDetails(string $source, string $companyId): array
    {
        $integration = $this->factory->create($source);

        return $integration->fetchCompanyDetails($companyId);
    }
}
