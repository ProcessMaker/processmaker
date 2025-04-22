<?php

namespace ProcessMaker\Services\DataSourceIntegrations\Integrations;

use Illuminate\Support\LazyCollection;

abstract class BaseIntegrationService implements IntegrationsInterface
{
    /**
     * Transform companies data to unified format
     *
     * @param array $companies The raw companies data
     * @return array The transformed companies data
     */
    protected function transformCompaniesData(array $companies) : array
    {
        return LazyCollection::make($companies)
            ->map(function ($item) {
                return $this->mapCompanyData($item);
            })
            ->values()
            ->all();
    }

    /**
     * Map a single company item to unified format
     *
     * @param array $item The company data
     * @return array The mapped company data
     */
    protected function mapCompanyData(array $item) : array
    {
        return [
            'id' => $this->extractId($item),
            'company_name' => $this->extractCompanyName($item),
            'company_logo' => $this->extractCompanyLogo($item),
            'industry' => $this->extractIndustry($item),
            'location' => $this->extractLocation($item),
            'revenue' => $this->extractRevenue($item),
            'employee_size' => $this->extractEmployeeSize($item),
            'net_profit_margin' => $this->extractNetProfitMargin($item),
            'revenue_growth' => $this->extractRevenueGrowth($item),
            'contact_email' => $this->extractContactEmail($item),
            'source' => $this->getSourceName(),
        ];
    }

    /**
     * Get the name of the data source
     *
     * @return string The source name
     */
    abstract protected function getSourceName() : string;

    /**
     * Extract company ID from raw data
     *
     * @param array $item Raw company data
     * @return string|null Company ID
     */
    abstract protected function extractId(array $item) : ?string;

    /**
     * Extract company name from raw data
     *
     * @param array $item Raw company data
     * @return string|null Company name
     */
    abstract protected function extractCompanyName(array $item) : ?string;

    /**
     * Extract company logo from raw data
     *
     * @param array $item Raw company data
     * @return string|null Company logo URL/ID
     */
    abstract protected function extractCompanyLogo(array $item) : ?string;

    /**
     * Extract industry information from raw data
     *
     * @param array $item Raw company data
     * @return array|null Industry information
     */
    abstract protected function extractIndustry(array $item) : ?array;

    /**
     * Extract location information from raw data
     *
     * @param array $item Raw company data
     * @return array Location information with state, city, postCode and country
     */
    abstract protected function extractLocation(array $item) : array;

    /**
     * Extract revenue information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Revenue information
     */
    abstract protected function extractRevenue(array $item);

    /**
     * Extract employee size information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Employee size information
     */
    abstract protected function extractEmployeeSize(array $item);

    /**
     * Extract net profit margin from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Net profit margin
     */
    abstract protected function extractNetProfitMargin(array $item);

    /**
     * Extract revenue growth from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Revenue growth
     */
    abstract protected function extractRevenueGrowth(array $item);

    /**
     * Extract contact email from raw data
     *
     * @param array $item Raw company data
     * @return string|null Contact email
     */
    abstract protected function extractContactEmail(array $item) : ?string;
}
