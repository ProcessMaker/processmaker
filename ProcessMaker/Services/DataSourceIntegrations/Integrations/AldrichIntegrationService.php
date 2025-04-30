<?php

namespace ProcessMaker\Services\DataSourceIntegrations\Integrations;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use ProcessMaker\Models\DataSourceIntegrations;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use ProcessMaker\Plugins\Collections\Models\Collection;
use ProcessMaker\Plugins\Collections\Models\Record;
use ProcessMaker\Services\DataSourceIntegrations\Integrations\BaseIntegrationService;
use ProcessMaker\Services\DataSourceIntegrations\Integrations\IntegrationsInterface;
use ProcessMaker\Services\DataSourceIntegrations\Schemas\AldrichApiSchema;

class AldrichIntegrationService extends BaseIntegrationService implements IntegrationsInterface
{
    protected $credentials;

    protected $base_url;

    protected $collection;

    protected $admin_user_id;

    public function __construct()
    {
        try {
            $integration = DataSourceIntegrations::select(['credentials', 'base_url'])->where('key', 'aldrich')->first();
            if (!$integration) {
                Log::error('Aldrich integration not found');

                return;
            }
            $this->credentials = $integration->credentials;
            $this->base_url = $integration->base_url;
            $this->admin_user_id = User::where('is_system', true)->first()->id;

            $this->ensureCollectionExists();
        } catch (\Exception $e) {
            Log::error('Error initializing Aldrich integration service: ' . $e->getMessage());

            return;
        }
    }

    // Ensure the collection for storing data exists
    protected function ensureCollectionExists()
    {
        try {
            $createScreen = Screen::firstOrCreate([
                'title' => 'Aldrich Integration - Create Screen',
                'description' => 'Aldrich Integration - Create Screen',
                'asset_type'=> 'DATA_SOURCE_INTEGRATION',
            ]);
            if ($createScreen) {
                $createScreenId = $createScreen->id;
            }

            $editScreen = Screen::firstOrCreate([
                'title' => 'Aldrich Integration - Edit Screen',
                'description' => 'Aldrich Integration - Edit Screen',
                'asset_type'=> 'DATA_SOURCE_INTEGRATION',
            ]);
            if ($editScreen) {
                $editScreenId = $editScreen->id;
            }

            $viewScreen = Screen::firstOrCreate([
                'title' => 'Aldrich Integration - View Screen',
                'description' => 'Aldrich Integration - View Screen',
                'asset_type'=> 'DATA_SOURCE_INTEGRATION',
            ]);
            if ($viewScreen) {
                $viewScreenId = $viewScreen->id;
            }

            $this->collection = Collection::firstOrCreate([
                'name' => 'Aldrich Data Collection',
                'description' => 'Aldrich Integration',
                'create_screen_id' => $createScreenId,
                'update_screen_id' => $editScreenId,
                'read_screen_id' => $viewScreenId,
                'created_by_id' => $this->admin_user_id,
                'updated_by_id' => $this->admin_user_id,
                'asset_type' => 'DATA_SOURCE_INTEGRATION',
            ]);
        } catch (\Exception $e) {
            Log::error('Error ensuring collection exists: ' . $e->getMessage());

            return;
        }
    }

    public function getParameters() : array
    {
        return AldrichApiSchema::getSchema();
    }

    public function getCompanies(array $params = []) : array
    {
        $records = $this->queryCollectionRecords($params);

        return $this->transformCompaniesData($records);
    }

    // Query collection records based on parameters
    protected function queryCollectionRecords(array $params = []) : array
    {
        return array_map(function ($item) {
            return (array) $item;
        }, Record::select('data')->fromCollection($this->collection)->get()->pluck('data')->toArray());
    }

    public function syncData(): array
    {
        $stats = [
            'fetched' => 0,
            'stored' => 0,
            'updated' => 0,
            'errors' => 0,
        ];

        try {
            // Fetch data from the different endpoints
            $companiesData = $this->fetchCompaniesFromApi();
            $stats['fetched'] += count($companiesData);

            $this->ensureCollectionExists();
            // Store each company in the collection
            foreach ($companiesData as $company) {
                $this->storeOrUpdateRecord($company);
                $stats['stored']++;
            }

            return $stats;
        } catch (\Exception $e) {
            $stats['errors']++;
            \Log::error('Error syncing data from Your Integration API: ' . $e->getMessage());
            throw $e;
        }
    }

    // Store or update record in the collection
    protected function storeOrUpdateRecord(array $data): void
    {
        try {
            // Use a unique identifier from the data as the external_id
            $externalId = $data['id'] ?? md5(json_encode($data));

            // Try to find existing record
            $record = Record::fromCollection($this->collection)->where('id', $externalId)->first();

            if ($record) {
                // Update existing record
                $record->update($data);
            } else {
                // Create new record
                $record = $this->collection->createRecord([
                    'data' => $data,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error storing or updating record: ' . $e->getMessage());

            return;
        }
    }

    /**
     * Fetch companies data from Aldrich API
     *
     * @param array $params The query parameters
     * @return array The raw API response
     */
    private function fetchCompaniesFromApi(array $params = []) : array
    {
        // TODO: Implement real API call to Aldrich
        $jsonPath = database_path('factories/sample_companies.json');
        if (file_exists($jsonPath)) {
            $companiesData = json_decode(file_get_contents($jsonPath), true);

            return $companiesData; // Return all companies instead of filtering
        }

        return [];
    }

    public function fetchCompanyDetails(string $companyId) : array
    {
        $response = $this->fetchCompanyDetailsFromApi($companyId);

        return $this->mapCompanyData($response);
    }

    /**
     * Fetch company details from Aldrich API
     *
     * @param string $companyId The company ID
     * @return array The raw API response
     */
    private function fetchCompanyDetailsFromApi(string $companyId) : array
    {
        // Read the sample data from JSON file
        $jsonPath = database_path('factories/sample_company_details.json');
        if (file_exists($jsonPath)) {
            $companiesData = json_decode(file_get_contents($jsonPath), true);

            // Find the company that matches the companyId
            foreach ($companiesData as $company) {
                if (isset($company['company_id']) && (string) $company['company_id'] === $companyId) {
                    return $company;
                }
            }
        }

        // Return empty array if company not found or file doesn't exist
        return [];
    }

    /**
     * Get the name of the data source
     *
     * @return string The source name
     */
    protected function getSourceName() : string
    {
        return 'aldrich';
    }

    /**
     * Extract company ID from raw data
     *
     * @param array $item Raw company data
     * @return string|null Company ID
     */
    protected function extractId(array $item) : ?string
    {
        return $item['company_id'] ?? null;
    }

    /**
     * Extract company name from raw data
     *
     * @param array $item Raw company data
     * @return string|null Company name
     */
    protected function extractCompanyName(array $item) : ?string
    {
        return $item['company_name'] ?? null;
    }

    /**
     * Extract company logo from raw data
     *
     * @param array $item Raw company data
     * @return string|null Company logo URL/ID
     */
    protected function extractCompanyLogo(array $item) : ?string
    {
        return $item['logo_url'] ?? null;
    }

    /**
     * Extract industry information from raw data
     *
     * @param array $item Raw company data
     * @return array|null Industry information
     */
    protected function extractIndustry(array $item) : array|string|null
    {
        if (isset($item['keywords'])) {
            $keywords = explode(',', $item['keywords']);

            return $keywords;
        }

        return null;
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
            'stateProvince' => $item['state'] ?? null,
            'city' => $item['city'] ?? null,
            'postCode' => $item['post_code'] ?? null,
            'country' => $item['country'] ?? null,
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
        return $item['revenue_range'] ?? null;
    }

    protected function extractMinRevenue(array $item)
    {
        $revenueRange = $item['revenue_range'];
        $minRevenue = explode('M', $revenueRange)[0];
        $minRevenue = str_replace('$', '', $minRevenue);

        return $minRevenue;
    }

    protected function extractMaxRevenue(array $item)
    {
        $revenueRange = $item['revenue_range'];
        $maxRevenue = explode('M', $revenueRange)[1];
        $maxRevenue = str_replace('-', '', $maxRevenue);
        $maxRevenue = str_replace('$', '', $maxRevenue);

        return $maxRevenue;
    }

    /**
     * Extract employee size information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Employee size information
     */
    protected function extractEmployeeSize(array $item)
    {
        return $item['employee_count'] ?? null;
    }

    /**
     * Extract net profit margin from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Net profit margin
     */
    protected function extractNetProfitMargin(array $item)
    {
        return $item['net_profit_margin'] ?? null;
    }

    /**
     * Extract revenue growth from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Revenue growth
     */
    protected function extractRevenueGrowth(array $item)
    {
        return $item['revenue_growth'] ?? null;
    }

    /**
     * Extract contact email from raw data
     *
     * @param array $item Raw company data
     * @return string|null Recipient email
     */
    protected function extractRecipientEmail(array $item) : array|string|null
    {
        return $item['decision_makers'] ?? null;
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
        return $item['status'] ?? null;
    }

    /**
     * Extract website URL from raw data
     *
     * @param array $item Raw company data
     * @return string|null Website URL
     */
    protected function extractWebsiteUrl(array $item) : ?string
    {
        return $item['company_website'] ?? null;
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
        return $item['decision_makers'] ?? null;
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
