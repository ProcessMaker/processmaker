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
            'name' => $this->extractCompanyName($item),
            'logo_url' => $this->extractCompanyLogo($item),
            'industry' => $this->extractIndustry($item),
            'location' => $this->extractLocation($item),
            'revenue' => $this->extractRevenue($item),
            'employee_size' => $this->extractEmployeeSize($item),
            'ebitda' => $this->extractEbitda($item),
            'fcff' => $this->extractFcff($item),
            'd_e' => $this->extractDe($item),
            'current_ratio' => $this->extractCurrentRatio($item),
            'earning_per_share' => $this->extractEarningPerShare($item),
            'status' => $this->extractStatus($item),
            'website_url' => $this->extractWebsiteUrl($item),
            'currency' => $this->extractCurrency($item),
            'net_profit_margin' => $this->extractNetProfitMargin($item),
            'revenue_growth' => $this->extractRevenueGrowth($item),
            'net_profit' => $this->extractNetProfit($item),
            'recipient_name' => $this->extractRecipientName($item),
            'recipient_email' => $this->extractRecipientEmail($item),
            'last_updated' => $this->extractLastUpdated($item),
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
     * Extract EBITDA information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null EBITDA information
     */
    abstract protected function extractEbitda(array $item);

    /**
     * Extract FCFF information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null FCFF information
     */
    abstract protected function extractFcff(array $item);

    /**
     * Extract DE information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null DE information
     */
    abstract protected function extractDe(array $item);

    /**
     * Extract current ratio information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Current ratio information
     */
    abstract protected function extractCurrentRatio(array $item);

    /**
     * Extract earning per share information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Earning per share information
     */
    abstract protected function extractEarningPerShare(array $item);

    /**
     * Extract status information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Status information
     */
    abstract protected function extractStatus(array $item);

    /**
     * Extract website URL from raw data
     *
     * @param array $item Raw company data
     * @return string|null Website URL
     */
    abstract protected function extractWebsiteUrl(array $item) : ?string;

    /**
     * Extract currency information from raw data
     *
     * @param array $item Raw company data
     * @return string|null Currency
     */
    abstract protected function extractCurrency(array $item) : ?string;

    /**
     * Extract net profit information from raw data
     *
     * @param array $item Raw company data
     * @return mixed|null Net profit
     */
    abstract protected function extractNetProfit(array $item);

    /**
     * Extract recipient name information from raw data
     *
     * @param array $item Raw company data
     * @return string|null Recipient name
     */
    abstract protected function extractRecipientName(array $item) : ?string;

    /**
     * Extract last updated information from raw data
     *
     * @param array $item Raw company data
     * @return string|null Last updated
     */
    abstract protected function extractLastUpdated(array $item) : ?string;

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
     * @return string|null Recipient email
     */
    abstract protected function extractRecipientEmail(array $item) : ?string;
}
