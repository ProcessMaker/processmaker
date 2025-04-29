<?php

namespace ProcessMaker\Services\DataSourceIntegrations\Integrations;

use Illuminate\Support\LazyCollection;
use ProcessMaker\Services\DataSourceIntegrations\Integrations\BaseIntegrationService;
use ProcessMaker\Services\DataSourceIntegrations\Integrations\IntegrationsInterface;
use ProcessMaker\Services\DataSourceIntegrations\Schemas\PitchbookApiSchema;

class PitchbookService extends BaseIntegrationService implements IntegrationsInterface
{
    public function getParameters() : array
    {
        return PitchbookApiSchema::getSchema();
    }

    public function getCompanies(array $params = []) : array
    {
        $response = $this->fetchCompaniesFromApi($params);

        if (empty($response['items'])) {
            return [];
        }

        return $this->transformCompaniesData($response['items']);
    }

    /**
     * Fetch companies data from Pitchbook API
     *
     * @param array $params The query parameters
     * @return array The raw API response
     */
    private function fetchCompaniesFromApi(array $params = []) : array
    {
        // TODO: Implement real API call to Pitchbook
        return [
            'stats' => [
                'total' => 864,
                'perPage' => 25,
                'page' => 1,
                'lastPage' => 35,
            ],
            'items' => [
                [
                    'companyId' => '102196-63',
                    'companyName' => 'Wisdo Health',
                    'website' => 'www.wisdo.com',
                ],
                [
                    'companyId' => '187513-39',
                    'companyName' => 'Agricolum',
                    'website' => 'www.agricolum.com',
                ],
                [
                    'companyId' => '222511-24',
                    'companyName' => 'OneSoil',
                    'website' => 'www.onesoil.ai',
                ],
                [
                    'companyId' => '226335-52',
                    'companyName' => 'Strangeworks',
                    'website' => 'www.strangeworks.com',
                ],
                [
                    'companyId' => '227010-61',
                    'companyName' => 'Mimica',
                    'website' => 'www.mimica.ai',
                ],
                [
                    'companyId' => '228916-18',
                    'companyName' => 'Bianjie.AI',
                    'website' => 'www.bianjie.ai',
                ],
                [
                    'companyId' => '231721-66',
                    'companyName' => 'Kodiak Robotics',
                    'website' => 'www.kodiak.ai',
                ],
                [
                    'companyId' => '233784-19',
                    'companyName' => 'Voiceflow',
                    'website' => 'www.voiceflow.com',
                ],
                [
                    'companyId' => '234055-54',
                    'companyName' => 'PIVOT Fintech',
                    'website' => 'www.pivotfintech.com',
                ],
                [
                    'companyId' => '234458-83',
                    'companyName' => 'Ikigai (Business/Productivity Software)',
                    'website' => 'www.ikigailabs.io',
                ],
                [
                    'companyId' => '235069-30',
                    'companyName' => 'Taygo',
                    'website' => 'www.taygo.com',
                ],
                [
                    'companyId' => '265560-04',
                    'companyName' => 'Taskade',
                    'website' => 'www.taskade.com',
                ],
                [
                    'companyId' => '265634-92',
                    'companyName' => 'Briq',
                    'website' => 'www.briq.com',
                ],
                [
                    'companyId' => '267096-34',
                    'companyName' => 'Myelin Foundry',
                    'website' => 'www.myelinfoundry.com',
                ],
                [
                    'companyId' => '267498-55',
                    'companyName' => 'Ravin AI',
                    'website' => 'www.ravin.ai',
                ],
                [
                    'companyId' => '267653-71',
                    'companyName' => 'Pixxel',
                    'website' => 'www.pixxel.space',
                ],
                [
                    'companyId' => '267790-96',
                    'companyName' => 'Databento',
                    'website' => 'www.databento.com',
                ],
                [
                    'companyId' => '267829-39',
                    'companyName' => 'Metaspectral',
                    'website' => 'www.metaspectral.com',
                ],
                [
                    'companyId' => '268240-69',
                    'companyName' => 'Unnatural Products',
                    'website' => 'www.unnaturalproducts.com',
                ],
                [
                    'companyId' => '268302-52',
                    'companyName' => 'Hayden AI',
                    'website' => 'www.hayden.ai',
                ],
                [
                    'companyId' => '268418-08',
                    'companyName' => 'Vannevar Labs',
                    'website' => 'www.vannevarlabs.com',
                ],
                [
                    'companyId' => '268790-05',
                    'companyName' => 'MDI Health',
                    'website' => 'www.mdihealth.com',
                ],
                [
                    'companyId' => '277215-04',
                    'companyName' => 'OSYTE',
                    'website' => 'www.osyte.com',
                ],
                [
                    'companyId' => '277269-76',
                    'companyName' => 'Poseidon-AI',
                    'website' => 'www.poseidon-ai.com',
                ],
                [
                    'companyId' => '277527-79',
                    'companyName' => 'nFlux',
                    'website' => 'www.nflux.ai',
                ],
            ],
        ];
    }

    public function fetchCompanyDetails(string $companyId) : array
    {
        $response = $this->fetchCompanyDetailsFromApi($companyId);

        return $this->mapCompanyData($response);
    }

    /**
     * Fetch company details from Pitchbook API
     *
     * @param string $companyId The company ID
     * @return array The raw API response
     */
    private function fetchCompanyDetailsFromApi(string $companyId) : array
    {
        // TODO: Implement real API call to Pitchbook
        return [
            'companyId' => '10618-03',
            'companyName' => [
                'formalName' => 'Jaguar Land Rover Automotive',
                'alsoKnownAs' => 'Jaguar Land Rover',
                'legalName' => 'Jaguar Land Rover Automotive PLC',
                'formerlyKnownAs' => 'Jaguar Land Rover PLC',
            ],
            'parentCompanyId' => '41517-91',
            'parentCompanyName' => 'Tata Motors',
            'hqLocation' => [
                'city' => 'Coventry',
                'stateProvince' => 'England',
                'postCode' => 'CV3 4LF',
                'country' => 'United Kingdom',
            ],
            'description' => 'Manufacturer and distributor of automobiles and spare parts based in Coventry, England, United Kingdom. The company engages in the manufacturing and marketing of automobiles such as luxury sedans, sports cars and off-road vehicles, along with designing spare parts and other automotive components.',
            'financingStatus' => [
                'code' => 'CBOA',
                'description' => 'Corporate Backed or Acquired',
            ],
            'businessStatus' => [
                'code' => 'PROF',
                'description' => 'Profitable',
            ],
            'ownershipStatus' => [
                'code' => 'ACQOS',
                'description' => 'Acquired/Merged (Operating Subsidiary)',
            ],
            'universe' => [
                [
                    'code' => 'CSU',
                    'description' => 'M&A',
                ],
                [
                    'code' => 'DEBTU',
                    'description' => 'Debt Financed',
                ],
            ],
            'website' => 'www.jaguarlandrover.com',
            'employees' => 44000,
            'employeeHistory' => [
                [
                    'asOfDate' => '2023-04-01',
                    'employeeCount' => 44000,
                ],
                [
                    'asOfDate' => '2022-09-29',
                    'employeeCount' => 47000,
                ],
                [
                    'asOfDate' => '2022-08-30',
                    'employeeCount' => 40000,
                ],
                [
                    'asOfDate' => '2022-01-01',
                    'employeeCount' => 42000,
                ],
                [
                    'asOfDate' => '2021-09-06',
                    'employeeCount' => 40000,
                ],
                [
                    'asOfDate' => '2020-10-16',
                    'employeeCount' => 40000,
                ],
                [
                    'asOfDate' => '2020-09-05',
                    'employeeCount' => 38000,
                ],
                [
                    'asOfDate' => '2020-08-08',
                    'employeeCount' => 40000,
                ],
                [
                    'asOfDate' => '2020-08-03',
                    'employeeCount' => 16000,
                ],
                [
                    'asOfDate' => '2020-07-28',
                    'employeeCount' => 40000,
                ],
                [
                    'asOfDate' => '2020-06-21',
                    'employeeCount' => 40000,
                ],
                [
                    'asOfDate' => '2020-05-10',
                    'employeeCount' => 43000,
                ],
                [
                    'asOfDate' => '2020-04-23',
                    'employeeCount' => 40000,
                ],
                [
                    'asOfDate' => '2020-01-06',
                    'employeeCount' => 40000,
                ],
                [
                    'asOfDate' => '2019-09-23',
                    'employeeCount' => 40000,
                ],
                [
                    'asOfDate' => '2019-03-31',
                    'employeeCount' => 44000,
                ],
                [
                    'asOfDate' => '2019-01-12',
                    'employeeCount' => 44000,
                ],
                [
                    'asOfDate' => '2018-12-31',
                    'employeeCount' => 43000,
                ],
                [
                    'asOfDate' => '2018-09-21',
                    'employeeCount' => 43000,
                ],
                [
                    'asOfDate' => '2018-07-04',
                    'employeeCount' => 40000,
                ],
                [
                    'asOfDate' => '2018-06-30',
                    'employeeCount' => 42000,
                ],
                [
                    'asOfDate' => '2018-04-13',
                    'employeeCount' => 40000,
                ],
                [
                    'asOfDate' => '2017-06-19',
                    'employeeCount' => 40000,
                ],
            ],
            'exchange' => null,
            'ticker' => null,
            'yearFounded' => 1922,
            'financingStatusNote' => [
                [
                    'note' => 'The company received $704.5 million of debt financing in the form of a revolving loan from Bank of China, Bank of Communications, China Construction Bank, Industrial and Commercial Bank of China and Shanghai Pudong Development Bank Company on June 5, 2020. The loan facility can help the company better manage cash flow amid the coronavirus epidemic.',
                    'asOfDate' => '2020-06-05',
                ],
            ],
            'totalMoneyRaised' => [
                [
                    'amount' => 12009834883.64,
                    'currency' => 'USD',
                    'nativeAmount' => 10158638934.67,
                    'nativeCurrency' => 'EUR',
                    'estimated' => false,
                ],
            ],
            'sicCodes' => [
                [
                    'code' => '3711',
                    'description' => 'Motor vehicles & passenger car bodies',
                ],
            ],
            'morningstarCode' => null,
            'cikCode' => null,
            'companySocialURLs' => [
                [
                    'facebookProfileUrl' => 'https://www.facebook.com/LifeatJLR',
                    'twitterProfileUrl' => 'https://twitter.com/jlr_news',
                    'linkedInProfileUrl' => 'https://www.linkedin.com/company/jaguar-land-rover_1',
                ],
            ],
            'pitchBookProfileLink' => 'https://my.pitchbook.com/profile/10618-03/company/profile',
        ];
    }

    /**
     * Get the name of the data source
     *
     * @return string The source name
     */
    protected function getSourceName() : string
    {
        return 'pitchbook';
    }

    /**
     * Extract company ID from raw data
     *
     * @param array $item Raw company data
     * @return string|null Company ID
     */
    protected function extractId(array $item) : ?string
    {
        return $item['companyId'] ?? null;
    }

    /**
     * Extract company name from raw data
     *
     * @param array $item Raw company data
     * @return string|null Company name
     */
    protected function extractCompanyName(array $item) : ?string
    {
        return isset($item['companyName']) && is_array($item['companyName'])
            ? ($item['companyName']['formalName'] ?? null)
            : ($item['companyName'] ?? null);
    }

    /**
     * Extract company logo from raw data
     *
     * @param array $item Raw company data
     * @return string|null Company logo URL/ID
     */
    protected function extractCompanyLogo(array $item) : ?string
    {
        return $item['companyLogo'] ?? null;
    }

    /**
     * Extract industry information from raw data
     *
     * @param array $item Raw company data
     * @return array|null Industry information
     */
    protected function extractIndustry(array $item) : array|string|null
    {
        return $item['sicCodes'] ?? null;
    }

    /**
     * Extract location information from raw data
     *
     * @param array $item Raw company data
     * @return array Location information with state, city, postCode and country
     */
    protected function extractLocation(array $item) : array|null
    {
        return [
            'stateProvince' => $item['hqLocation']['stateProvince'] ?? null,
            'city' => $item['hqLocation']['city'] ?? null,
            'postCode' => $item['hqLocation']['postCode'] ?? null,
            'country' => $item['hqLocation']['country'] ?? null,
        ];
    }

    /**
     * Extract revenue information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Revenue information
     */
    protected function extractRevenueRange(array $item)
    {
        return $item['revenue'] ?? null;
    }

    protected function extractMinRevenue(array $item)
    {
        return $item['revenue'] ?? null;
    }

    protected function extractMaxRevenue(array $item)
    {
        return $item['revenue'] ?? null;
    }

    /**
     * Extract employee size information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Employee size information
     */
    protected function extractEmployeeSize(array $item)
    {
        return $item['employees'] ?? null;
    }

    /**
     * Extract net profit margin from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Net profit margin
     */
    protected function extractNetProfitMargin(array $item)
    {
        return $item['netProfitMargin'] ?? null;
    }

    /**
     * Extract revenue growth from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Revenue growth
     */
    protected function extractRevenueGrowth(array $item)
    {
        return $item['revenueGrowth'] ?? null;
    }

    /**
     * Extract contact email from raw data
     *
     * @param array $item Raw company data
     * @return string|null Recipient email
     */
    protected function extractRecipientEmail(array $item) : array|string|null
    {
        return $item['contactEmail'] ?? null;
    }

    /**
     * Extract EBITDA information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null EBITDA information
     */
    protected function extractEbitda(array $item)
    {
        return $item['ebitda'] ?? null;
    }

    /**
     * Extract FCFF information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null FCFF information
     */
    protected function extractFcff(array $item)
    {
        return $item['fcff'] ?? null;
    }

    /**
     * Extract DE information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null DE information
     */
    protected function extractDe(array $item)
    {
        return $item['de'] ?? null;
    }

    /**
     * Extract current ratio information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Current ratio information
     */
    protected function extractCurrentRatio(array $item)
    {
        return $item['current_ratio'] ?? null;
    }

    /**
     * Extract earning per share information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Earning per share information
     */
    protected function extractEarningPerShare(array $item)
    {
        return $item['earning_per_share'] ?? null;
    }

    /**
     * Extract status information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Status information
     */
    protected function extractStatus(array $item)
    {
        return $item['financingStatus'] ?? null;
    }

    /**
     * Extract website URL from raw data
     *
     * @param array $item Raw company data
     * @return string|null Website URL
     */
    protected function extractWebsiteUrl(array $item) : ?string
    {
        return $item['website'] ?? null;
    }

    /**
     * Extract currency information from raw data
     *
     * @param array $item Raw company data
     * @return string|null Currency
     */
    protected function extractCurrency(array $item) : ?string
    {
        return $item['currency'] ?? null;
    }

    /**
     * Extract net profit information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Net profit
     */
    protected function extractNetProfit(array $item)
    {
        return $item['net_profit'] ?? null;
    }

    /**
     * Extract recipient name information from raw data
     *
     * @param array $item Raw company data
     * @return string|null Recipient name
     */
    protected function extractRecipientName(array $item) : array|string|null
    {
        return $item['recipient_name'] ?? null;
    }

    /**
     * Extract last updated information from raw data
     *
     * @param array $item Raw company data
     * @return string|null Last updated
     */
    protected function extractLastUpdated(array $item) : ?string
    {
        return $item['last_updated'] ?? null;
    }
}
