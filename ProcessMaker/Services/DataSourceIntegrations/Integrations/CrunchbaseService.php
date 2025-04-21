<?php

namespace ProcessMaker\Services\DataSourceIntegrations\Integrations;

use ProcessMaker\Services\DataSourceIntegrations\Integrations\IntegrationsInterface;

class CrunchbaseService implements IntegrationsInterface
{
    public function getParameters() : array
    {
        return [
            'name' => 'name',
            'source' => 'crunchbase',
        ];
    }

    public function getCompanies(array $params = []) : array
    {
        return [
            'companies' => 'companies',
        ];
    }

    public function fetchCompanyDetails(string $source, string $companyId) : array
    {
        return [
            'company_details' => 'company_details',
        ];
    }
}
