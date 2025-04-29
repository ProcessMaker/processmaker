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
        //TODO: Maybe call collection endpoint to query records?
        dd('queryCollectionRecords');

        // $query = CollectionRecord::where('collection_id', $this->collection->id);

        // // Apply filters based on params
        // if (!empty($params)) {
        //     // Add query filters based on params
        //     // This will depend on your specific requirements
        //     if (isset($params['identifier'])) {

        //     }

        //     // Add more filters as needed
        // }

        // // Return the records as an array of data
        // return $query->get()->map(function ($record) {
        //     return $record->data;
        // })->toArray();
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
        return [
            [
                'revenue_range' => '$4.8M - $7.2M',
                'employee_count' => 58,
                'country' => 'United States',
                'company_id' => 781354,
                'city' => 'Seattle',
                'company_domain' => 'alphatech.com',
                'company_name' => 'AlphaTech Solutions',
                'company_website' => 'https://www.alphatech.com',
                'thesis_name' => 'Artificial Intelligence',
                'state' => 'WA',
                'last_update_on' => '15-05-2025',
            ],
            [
                'revenue_range' => '$7.5M - $9.8M',
                'employee_count' => 87,
                'country' => 'United States',
                'company_id' => 623571,
                'city' => 'Boston',
                'company_domain' => 'nexusknowledge.com',
                'company_name' => 'Nexus Knowledge Systems',
                'company_website' => 'https://nexusknowledge.com',
                'thesis_name' => 'Artificial Intelligence',
                'state' => 'MA',
                'last_update_on' => '12-05-2025',
            ],
            [
                'revenue_range' => '$3.9M - $5.7M',
                'employee_count' => 48,
                'country' => 'United States',
                'company_id' => 432198,
                'city' => 'Portland',
                'company_domain' => 'digitaldocs.com',
                'company_name' => 'Digital Documentation Inc.',
                'company_website' => 'https://digitaldocs.com',
                'thesis_name' => 'Accounting Solution',
                'state' => 'OR',
                'last_update_on' => '18-05-2025',
            ],
            [
                'revenue_range' => '$5.3M - $8.1M',
                'employee_count' => 42,
                'country' => 'United States',
                'company_id' => 197643,
                'city' => 'Denver',
                'company_domain' => 'dataflow.io',
                'company_name' => 'DataFlow Systems',
                'company_website' => 'https://www.dataflow.io',
                'thesis_name' => 'Accounting Solution',
                'state' => 'CO',
                'last_update_on' => '22-05-2025',
            ],
            [
                'revenue_range' => '$2.9M - $4.3M',
                'employee_count' => 31,
                'country' => 'United States',
                'company_id' => 365824,
                'city' => 'Austin',
                'company_domain' => 'cyberlogic.net',
                'company_name' => 'CyberLogic Solutions',
                'company_website' => 'http://www.cyberlogic.net',
                'thesis_name' => 'Artificial Intelligence',
                'state' => 'TX',
                'last_update_on' => '14-05-2025',
            ],
            [
                'revenue_range' => '$10.5M - $14.2M',
                'employee_count' => 126,
                'country' => 'United States',
                'company_id' => 728945,
                'city' => 'San Francisco',
                'company_domain' => 'futuresight.ai',
                'company_name' => 'FutureSight Analytics',
                'company_website' => 'http://www.futuresight.ai',
                'thesis_name' => 'Artificial Intelligence',
                'state' => 'CA',
                'last_update_on' => '10-05-2025',
            ],
            [
                'revenue_range' => '$5.8M - $7.6M',
                'employee_count' => 67,
                'country' => 'United States',
                'company_id' => 492587,
                'city' => 'Chicago',
                'company_domain' => 'apexfinance.com',
                'company_name' => 'Apex Financial Services',
                'company_website' => 'http://www.apexfinance.com',
                'thesis_name' => 'Accounting Solution',
                'state' => 'IL',
                'last_update_on' => '19-05-2025',
            ],
            [
                'revenue_range' => '$1.8M - $3.2M',
                'employee_count' => 23,
                'country' => 'United States',
                'company_id' => 612478,
                'city' => 'Atlanta',
                'company_domain' => 'clearbooks.com',
                'company_name' => 'ClearBooks Accounting',
                'company_website' => 'https://www.clearbooks.com',
                'thesis_name' => 'Accounting Solution',
                'state' => 'GA',
                'last_update_on' => '16-05-2025',
            ],
            [
                'revenue_range' => '$4.5M - $6.3M',
                'employee_count' => 51,
                'country' => 'United States',
                'company_id' => 347921,
                'city' => 'San Diego',
                'company_domain' => 'culinarysync.io',
                'company_name' => 'CulinarySync',
                'company_website' => 'https://culinarysync.io',
                'thesis_name' => 'Accounting Solution',
                'state' => 'CA',
                'last_update_on' => '20-05-2025',
            ],
            [
                'revenue_range' => '$4.1M - $6.1M',
                'employee_count' => 49,
                'country' => 'United States',
                'company_id' => 531976,
                'city' => 'Dallas',
                'company_domain' => 'swiftpay.tech',
                'company_name' => 'SwiftPay Technologies',
                'company_website' => 'https://www.swiftpay.tech',
                'thesis_name' => 'Accounting Solution',
                'state' => 'TX',
                'last_update_on' => '11-05-2025',
            ],
            [
                'revenue_range' => '$3.6M - $5.7M',
                'employee_count' => 45,
                'country' => 'United States',
                'company_id' => 268349,
                'city' => 'Miami',
                'company_domain' => 'quantumanalytics.co',
                'company_name' => 'Quantum Analytics Systems',
                'company_website' => 'https://www.quantumanalytics.co',
                'thesis_name' => 'Accounting Solution',
                'state' => 'FL',
                'last_update_on' => '17-05-2025',
            ],
            [
                'revenue_range' => '$2.1M - $3.8M',
                'employee_count' => 29,
                'country' => 'United States',
                'company_id' => 875421,
                'city' => 'Phoenix',
                'company_domain' => 'precisionbooks.com',
                'company_name' => 'Precision Bookkeeping',
                'company_website' => 'https://precisionbooks.com',
                'thesis_name' => 'Accounting Solution',
                'state' => 'AZ',
                'last_update_on' => '13-05-2025',
            ],
            [
                'revenue_range' => '$3.7M - $5.3M',
                'employee_count' => 41,
                'country' => 'United States',
                'company_id' => 396524,
                'city' => 'Minneapolis',
                'company_domain' => 'intellibrain.ai',
                'company_name' => 'IntelliBrain',
                'company_website' => 'https://intellibrain.ai',
                'thesis_name' => 'Artificial Intelligence',
                'state' => 'MN',
                'last_update_on' => '21-05-2025',
            ],
            [
                'revenue_range' => '$4.7M - $6.5M',
                'employee_count' => 55,
                'country' => 'United States',
                'company_id' => 765823,
                'city' => 'Nashville',
                'company_domain' => 'fiscaledge.com',
                'company_name' => 'FiscalEdge',
                'company_website' => 'https://www.fiscaledge.com',
                'thesis_name' => 'Accounting Solution',
                'state' => 'TN',
                'last_update_on' => '12-05-2025',
            ],
            [
                'revenue_range' => '$3.2M - $5.1M',
                'employee_count' => 38,
                'country' => 'United States',
                'company_id' => 428731,
                'city' => 'Raleigh',
                'company_domain' => 'cognitivesphere.com',
                'company_name' => 'CognitiveSphere',
                'company_website' => 'https://cognitivesphere.com',
                'thesis_name' => 'Artificial Intelligence',
                'state' => 'NC',
                'last_update_on' => '19-05-2025',
            ],
            [
                'revenue_range' => '$5.5M - $7.8M',
                'employee_count' => 63,
                'country' => 'United States',
                'company_id' => 215897,
                'city' => 'Detroit',
                'company_domain' => 'ledgerpoint.com',
                'company_name' => 'LedgerPoint Solutions',
                'company_website' => 'https://ledgerpoint.com',
                'thesis_name' => 'Accounting Solution',
                'state' => 'MI',
                'last_update_on' => '15-05-2025',
            ],
            [
                'revenue_range' => '$4.1M - $6.2M',
                'employee_count' => 49,
                'country' => 'United States',
                'company_id' => 618534,
                'city' => 'Charlotte',
                'company_domain' => 'halpern-associates.com',
                'company_name' => 'Halpern & Associates',
                'company_website' => 'https://www.halpern-associates.com',
                'thesis_name' => 'Accounting Solution',
                'state' => 'NC',
                'last_update_on' => '20-05-2025',
            ],
            [
                'revenue_range' => '$5.4M - $7.9M',
                'employee_count' => 62,
                'country' => 'United States',
                'company_id' => 753916,
                'city' => 'Cambridge',
                'company_domain' => 'neuralflow.com',
                'company_name' => 'NeuralFlow Tech',
                'company_website' => 'https://www.neuralflow.com',
                'thesis_name' => 'Artificial Intelligence',
                'state' => 'MA',
                'last_update_on' => '14-05-2025',
            ],
            [
                'revenue_range' => '$5.2M - $7.3M',
                'employee_count' => 61,
                'country' => 'United States',
                'company_id' => 382654,
                'city' => 'Newark',
                'company_domain' => 'smartbridge.io',
                'company_name' => 'SmartBridge Financial',
                'company_website' => 'https://smartbridge.io',
                'thesis_name' => 'Accounting Solution',
                'state' => 'NJ',
                'last_update_on' => '17-05-2025',
            ],
            [
                'revenue_range' => '$7.9M - $11.3M',
                'employee_count' => 98,
                'country' => 'United States',
                'company_id' => 591726,
                'city' => 'Houston',
                'company_domain' => 'bluestream.tech',
                'company_name' => 'BlueStream Solutions',
                'company_website' => 'https://bluestream.tech',
                'thesis_name' => 'Accounting Solution',
                'state' => 'TX',
                'last_update_on' => '11-05-2025',
            ],
            [
                'revenue_range' => '$5.8M - $7.9M',
                'employee_count' => 76,
                'country' => 'United States',
                'company_id' => 639871,
                'city' => 'Austin',
                'company_domain' => 'visionscope.com',
                'company_name' => 'VisionScope Technologies',
                'company_website' => 'http://www.visionscope.com',
                'thesis_name' => 'Accounting Solution',
                'state' => 'TX',
                'last_update_on' => '18-05-2025',
            ],
            [
                'revenue_range' => '$1.5M - $3.1M',
                'employee_count' => 24,
                'country' => 'United States',
                'company_id' => 472583,
                'city' => 'Durham',
                'company_domain' => 'numbercrunchers.net',
                'company_name' => 'NumberCrunchers Analytics',
                'company_website' => 'https://www.numbercrunchers.net',
                'thesis_name' => 'Accounting Solution',
                'state' => 'NC',
                'last_update_on' => '16-05-2025',
            ],
            [
                'revenue_range' => '$3.9M - $5.8M',
                'employee_count' => 48,
                'country' => 'United States',
                'company_id' => 826537,
                'city' => 'Irvine',
                'company_domain' => 'techmatrix.com',
                'company_name' => 'TechMatrix Solutions',
                'company_website' => 'https://www.techmatrix.com',
                'thesis_name' => 'Accounting Solution',
                'state' => 'CA',
                'last_update_on' => '13-05-2025',
            ],
        ];
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
        // TODO: Implement real API call to Aldrich
        return
            [
                'country' => 'United States',
                'short_description' => 'Digital Documentation Inc. provides innovative document management solutions with AI-powered data extraction for businesses of all sizes.',
                'company_score' => 87.42,
                'keywords' => 'Document Management,Digital Transformation,Accounting Solution,Cloud Storage,Data Extraction,OCR Technology,Process Automation,Records Management,Business Intelligence,Data Security,Compliance,Paperless Office,Workflow Automation,API Integration',
                'city' => 'Portland',
                'company_website' => 'https://digitaldocs.com',
                'salesforce_account_id' => '0011U00001LM7RPQA1',
                'description' => 'Digital Documentation Inc. offers comprehensive document management solutions that streamline business processes and reduce operational costs. Our services include secure document scanning, intelligent data extraction, cloud storage, workflow automation, and system integration. We serve industries including healthcare, financial services, legal, and manufacturing with tailored solutions for regulatory compliance and operational efficiency. Our flagship products include SmartScan Pro, DataVault Cloud, and WorkflowIQ for enterprise document management.',
                'revenue_range' => '$3.9M - $5.7M',
                'last_funding_date' => '2023-08-15',
                'onshore_employees' => '94.7%',
                'emp_2y_growth' => '27%',
                'company_domain' => 'digitaldocs.com',
                'founded_year' => 2015,
                'state' => 'OR',
                'twitter_url' => 'https://x.com/digitaldocsinc',
                'crunchbase_url' => 'https://www.crunchbase.com/organization/digital-documentation-inc',
                'emp_1y_growth' => '12%',
                'pitchbook_url' => 'https://pitchbook.com/profiles/company/digital-documentation-inc',
                'employee_count' => 48,
                'company_id' => 432198,
                'thesis_name' => 'Accounting Solution',
                'total_funding' => '$5.2M',
                'engagement_score' => 42.87,
                'decision_makers' => [
                    [
                        'full_name' => 'Jason Reynolds',
                        'phone' => '503-892-5555',
                        'linkedin_url' => 'https://www.linkedin.com/in/jason-reynolds-digitaldocs/',
                        'contact_id' => '0031U00001K8LMbQAN',
                        'title' => 'Founder & CEO',
                        'email' => 'jason.reynolds@digitaldocs.com',
                    ],
                    [
                        'full_name' => 'Sarah Chen',
                        'phone' => '503-892-5555',
                        'linkedin_url' => 'https://www.linkedin.com/in/sarah-chen-product/',
                        'contact_id' => '0031U00001K8LMgQAN',
                        'title' => 'CTO',
                        'email' => 'sarah.chen@digitaldocs.com',
                    ],
                    [
                        'full_name' => 'Michael Donnelly',
                        'phone' => '503-892-5555',
                        'linkedin_url' => 'https://www.linkedin.com/in/michael-donnelly-finance/',
                        'contact_id' => '0031U00001K8LMhQAN',
                        'title' => 'CFO',
                        'email' => 'michael.donnelly@digitaldocs.com',
                    ],
                    [
                        'full_name' => 'Anita Patel',
                        'phone' => '503-892-5555',
                        'linkedin_url' => 'https://www.linkedin.com/in/anita-patel-sales/',
                        'contact_id' => '0031U00001K8LMiQAN',
                        'title' => 'VP of Sales',
                        'email' => 'anita.patel@digitaldocs.com',
                    ],
                ],
                'last_funding_type' => 'Series A',
                'company_name' => 'Digital Documentation Inc.',
                'emp_6m_growth' => '5%',
                'linkedin_url' => 'https://www.linkedin.com/company/digital-documentation-inc/',
                'business_model' => 'SaaS',
            ];
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
    protected function extractIndustry(array $item) : ?array
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
            'stateProvince' => $item['cards']['headquarters_address']['location_identifiers'][1]['value'] ?? null,
            'city' => $item['cards']['headquarters_address']['location_identifiers'][0]['value'] ?? null,
            'postCode' => $item['cards']['headquarters_address']['postal_code'] ?? null,
            'country' => $item['cards']['headquarters_address']['location_identifiers'][3]['value'] ?? null,
        ];
    }

    /**
     * Extract revenue information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Revenue information
     */
    protected function extractRevenue(array $item)
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
    protected function extractRecipientEmail(array $item) : ?string
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
    protected function extractRecipientName(array $item) : ?string
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
        return $item['properties']['last_updated'] ?? null;
    }
}
