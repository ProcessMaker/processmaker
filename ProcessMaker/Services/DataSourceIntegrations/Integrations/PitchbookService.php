<?php

namespace ProcessMaker\Services\DataSourceIntegrations\Integrations;

use ProcessMaker\Services\DataSourceIntegrations\Integrations\IntegrationsInterface;

class PitchbookService implements IntegrationsInterface
{
    public function getParameters() : array
    {
        // TODO: Implement getParameters() method with real data from Pitchbook
        return [
            'name' => 'name',
            'revenue' => 'revenue',
        ];
    }

    public function getCompanies(array $params = []) : array
    {
        // TODO: Implement getCompanies() method with real data from Pitchbook
        return [
            [
                'id' => 1,
                'name' => 'ProcessMaker',
                'description' => 'ProcessMaker is a platform for building process automation solutions.',
                'location' => 'San Francisco, CA',
                'industry' => 'Software',
                'logo' => 'https://processmaker.com/logo.png',
            ],
            [
                'id' => 2,
                'name' => 'Google',
                'description' => 'Google is a platform for building process automation solutions.',
                'location' => 'Mountain View, CA',
                'industry' => 'Software',
                'logo' => 'https://google.com/logo.png',
            ],
        ];
    }

    public function fetchCompanyDetails(string $companyId) : array
    {
        // TODO: Implement fetchCompanyDetails() method with real data from Pitchbook
        return [
            'id' => 1,
            'name' => 'ProcessMaker',
            'revenue' => 1000000,
            'description' => 'ProcessMaker is a platform for building process automation solutions.',
            'location' => 'San Francisco, CA',
            'industry' => 'Software',
            'employees' => 100,
            'founded_year' => 2010,
            'last_funding_amount' => 1000000,
            'last_funding_date' => '2021-01-01',
            'last_funding_round' => 'Series A',
            'short_description' => 'ProcessMaker is a platform for building process automation solutions.',
            'long_description' => 'ProcessMaker is a platform for building process automation solutions.',
            'logo' => 'https://processmaker.com/logo.png',
        ];
    }
}
