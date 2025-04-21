<?php

namespace ProcessMaker\Services\DataSourceIntegrations\Integrations;

use ProcessMaker\Services\DataSourceIntegrations\Integrations\IntegrationsInterface;

class CrunchbaseService implements IntegrationsInterface
{
    public function getParameters() : array
    {
        // TODO: Implement getParameters() method with real data from Crunchbase
        return [
            'name' => 'name',
            'source' => 'crunchbase',
        ];
    }

    public function getCompanies(array $params = []) : array
    {
        // TODO: Implement getCompanies() method with real data from Crunchbase
        return [
            [
                'id' => 1,
                'name' => 'Amazon',
                'description' => 'Amazon is a platform for building process automation solutions.',
                'location' => 'Seattle, WA',
                'industry' => 'Software',
                'logo' => 'https://processmaker.com/logo.png',
            ],
            [
                'id' => 2,
                'name' => 'Telsa',
                'description' => 'Telsa is a platform for building process automation solutions.',
                'location' => 'Mountain View, CA',
                'industry' => 'Software',
                'logo' => 'https://google.com/logo.png',
            ],
        ];
    }

    public function fetchCompanyDetails(string $companyId) : array
    {
        // TODO: Implement fetchCompanyDetails() method with real data from Crunchbase
        return [
            'id' => 1,
            'name' => 'Amazon',
            'revenue' => 1000000,
            'description' => 'Amazon is a platform for building process automation solutions.',
            'location' => 'Seattle, WA',
            'industry' => 'Software',
            'employees' => 100,
            'founded_year' => 2010,
            'last_funding_amount' => 1000000,
            'last_funding_date' => '2021-01-01',
            'last_funding_round' => 'Series A',
            'short_description' => 'Amazon is a platform for building process automation solutions.',
            'long_description' => 'Amazon is a platform for building process automation solutions.',
            'logo' => 'https://processmaker.com/logo.png',
        ];
    }
}
