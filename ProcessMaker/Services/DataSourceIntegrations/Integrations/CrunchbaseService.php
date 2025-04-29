<?php

namespace ProcessMaker\Services\DataSourceIntegrations\Integrations;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use ProcessMaker\Exception\DataSourceIntegrationException\ApiRequestException;
use ProcessMaker\Models\DataSourceIntegrations;
use ProcessMaker\Services\DataSourceIntegrations\Integrations\BaseIntegrationService;
use ProcessMaker\Services\DataSourceIntegrations\Integrations\IntegrationsInterface;
use ProcessMaker\Services\DataSourceIntegrations\Schemas\CrunchbaseApiSchema;

class CrunchbaseService extends BaseIntegrationService implements IntegrationsInterface
{
    protected $credentials;

    protected $base_url;

    public function __construct()
    {
        $integration = DataSourceIntegrations::select(['credentials', 'base_url'])->where('key', 'crunchbase')->first();
        $this->credentials = $integration->credentials;
        $this->base_url = $integration->base_url;
    }

    public function getParameters() : array
    {
        return CrunchbaseApiSchema::getSchema();
    }

    public function getCompanies(array $params = []) : array
    {
        $response = $this->fetchCompaniesFromApi($params);

        if (empty($response['entities'])) {
            return [];
        }

        return $this->transformCompaniesData($response['entities']);
    }

    /**
     * Fetch companies data from Crunchbase API
     *
     * @param array $params The query parameters
     * @return array The raw API response
     */
    private function fetchCompaniesFromApi(array $params = []) : array
    {
        $url = $this->base_url . '/searches/organizations';

        $requestBody = [
            'field_ids' => [
                'identifier',
                'location_identifiers',
                'short_description',
                'rank_org',
                'name',
                'created_at',
                'updated_at',
                'image_id',
                'image_url',
                'permalink',
                'uuid',
                'website_url',
            ],
            'order' => $params['order'] ?? [
                [
                    'field_id' => 'rank_org',
                    'sort' => 'asc',
                ],
            ],
            'query' => $params['query'] ?? [],
            'limit' => $params['limit'] ?? 50,
        ];

        try {
            $response = Http::withHeaders([
                'X-cb-user-key' => $this->credentials,
                'Content-Type' => 'application/json',
                'accept' => 'application/json',
            ])->post($url, $requestBody);

            return $response->json();
        } catch (Exception $e) {
            Log::error('Crunchbase API request failed', [
                'error' => $e->getMessage(),
            ]);

            throw new ApiRequestException('Crunchbase API request failed: ' . $e->getMessage());
        }
    }

    public function fetchCompanyDetails(string $companyId) : array
    {
        $response = $this->fetchCompanyDetailsFromApi($companyId);

        return $this->mapCompanyData($response);
    }

    /**
     * Fetch company details from Crunchbase API
     *
     * @param string $companyId The company ID
     * @return array The raw API response
     */
    private function fetchCompanyDetailsFromApi(string $companyId) : array
    {
        $url = $this->base_url . '/entities/organizations/' . $companyId . '?field_ids={field_ids}';

        $requestParams = [
            'field_ids' => $params['field_ids'] ?? [
                'identifier',
                'location_identifiers',
                'short_description',
                'rank_org',
                'name',
                'created_at',
                'updated_at',
                'image_id',
                'image_url',
                'permalink',
                'uuid',
                'website_url',
            ],
        ];

        try {
            $response = Http::withHeaders([
                'X-cb-user-key' => $this->credentials,
                'accept' => 'application/json',
            ])->withUrlParameters(['field_ids' => implode(',', $requestParams['field_ids'])])->get($url);
        } catch (Exception $e) {
            Log::error('Crunchbase API request failed', [
                'error' => $e->getMessage(),
            ]);

            throw new ApiRequestException('Crunchbase API request failed: ' . $e->getMessage());
        }

        return $response->json();
    }

    /**
     * Get the name of the data source
     *
     * @return string The source name
     */
    protected function getSourceName() : string
    {
        return 'crunchbase';
    }

    /**
     * Extract company ID from raw data
     *
     * @param array $item Raw company data
     * @return string|null Company ID
     */
    protected function extractId(array $item) : ?string
    {
        return $item['properties']['uuid'] ?? null;
    }

    /**
     * Extract company name from raw data
     *
     * @param array $item Raw company data
     * @return string|null Company name
     */
    protected function extractCompanyName(array $item) : ?string
    {
        return isset($item['properties']['identifier']['value'])
            ? $item['properties']['identifier']['value']
            : null;
    }

    /**
     * Extract company logo from raw data
     *
     * @param array $item Raw company data
     * @return string|null Company logo URL/ID
     */
    protected function extractCompanyLogo(array $item) : ?string
    {
        return $item['properties']['image_url'] ?? null;
    }

    /**
     * Extract industry information from raw data
     *
     * @param array $item Raw company data
     * @return array|null Industry information
     */
    protected function extractIndustry(array $item) : array|string
    {
        return $item['properties']['categories'] ?? null;
    }

    /**
     * Extract location information from raw data
     *
     * @param array $item Raw company data
     * @return array Location information with state, city, postCode and country
     */
    protected function extractLocation(array $item) : array
    {
        return [
            'city' => $item['properties']['location_identifiers'][0]['value'] ?? null,
            'region' => $item['properties']['location_identifiers'][1]['value'] ?? null,
            'country' => $item['properties']['location_identifiers'][2]['value'] ?? null,
            'continent' => $item['properties']['location_identifiers'][3]['value'] ?? null,
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
        return $item['properties']['revenue_range'] ?? null;
    }

    /**
     * Extract employee size information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Employee size information
     */
    protected function extractEmployeeSize(array $item)
    {
        return $item['properties']['num_employees_enum'] ?? null;
    }

    /**
     * Extract net profit margin from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Net profit margin
     */
    protected function extractNetProfitMargin(array $item)
    {
        return $item['properties']['net_profit_margin'] ?? null;
    }

    /**
     * Extract revenue growth from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Revenue growth
     */
    protected function extractRevenueGrowth(array $item)
    {
        return $item['properties']['revenue_growth'] ?? null;
    }

    /**
     * Extract contact email from raw data
     *
     * @param array $item Raw company data
     * @return string|null Recipient email
     */
    protected function extractRecipientEmail(array $item) : array|string
    {
        return $item['properties']['contact_email'] ?? null;
    }

    /**
     * Extract EBITDA information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null EBITDA information
     */
    protected function extractEbitda(array $item)
    {
        return $item['properties']['ebitda'] ?? null;
    }

    /**
     * Extract FCFF information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null FCFF information
     */
    protected function extractFcff(array $item)
    {
        return $item['properties']['fcff'] ?? null;
    }

    /**
     * Extract DE information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null DE information
     */
    protected function extractDe(array $item)
    {
        return $item['properties']['de'] ?? null;
    }

    /**
     * Extract current ratio information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Current ratio information
     */
    protected function extractCurrentRatio(array $item)
    {
        return $item['properties']['current_ratio'] ?? null;
    }

    /**
     * Extract earning per share information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Earning per share information
     */
    protected function extractEarningPerShare(array $item)
    {
        return $item['properties']['earning_per_share'] ?? null;
    }

    /**
     * Extract status information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Status information
     */
    protected function extractStatus(array $item)
    {
        return $item['properties']['status'] ?? null;
    }

    /**
     * Extract website URL from raw data
     *
     * @param array $item Raw company data
     * @return string|null Website URL
     */
    protected function extractWebsiteUrl(array $item) : ?string
    {
        return $item['properties']['website_url'] ?? null;
    }

    /**
     * Extract currency information from raw data
     *
     * @param array $item Raw company data
     * @return string|null Currency
     */
    protected function extractCurrency(array $item) : ?string
    {
        return $item['properties']['currency'] ?? null;
    }

    /**
     * Extract net profit information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Net profit
     */
    protected function extractNetProfit(array $item)
    {
        return $item['properties']['net_profit'] ?? null;
    }

    /**
     * Extract recipient name information from raw data
     *
     * @param array $item Raw company data
     * @return string|null Recipient name
     */
    protected function extractRecipientName(array $item) : array|string
    {
        return $item['properties']['recipient_name'] ?? null;
    }

    /**
     * Extract last updated information from raw data
     *
     * @param array $item Raw company data
     * @return string|null Last updated
     */
    protected function extractLastUpdated(array $item) : ?string
    {
        return $item['properties']['updated_at'] ?? null;
    }
}
