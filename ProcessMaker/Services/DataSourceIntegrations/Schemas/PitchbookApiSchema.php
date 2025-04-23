<?php

namespace ProcessMaker\Services\DataSourceIntegrations\Schemas;

class PitchbookApiSchema
{
    /**
     * Get the schema definition for Pitchbook API
     *
     * Schema structure:
     * [
     *   [
     *     'key' => (string) Parameter identifier,
     *     'description' => (string) Human-readable description,
     *     'accepted_values' => (string|array) Either 'open' for any value or array of allowed options,
     *   ],
     *   ...
     * ]
     *
     * @return array Schema definition
     */
    public static function getSchema(): array
    {
        return [
            [
                'key' => 'companyNames',
                'description' => 'Pass a list of company names, ids, websites or tickers. Will return results for all companies with matching input. Use a comma to separate value.',
                'accepted_values' => 'open',
            ],
            [
                'key' => 'ownershipStatus',
                'description' => 'Companies can be found by their ownership status code.',
                'accepted_values' => [
                    ['key' => 'PVTB', 'description' => 'Private with backing'],
                    ['key' => 'ACQ', 'description' => 'Acquired/Merged'],
                    ['key' => 'PVTNB', 'description' => 'Privately Held (no backing)'],
                ],
            ],
            [
                'key' => 'backingStatus',
                'description' => 'Companies can be found by their backing status code.',
                'accepted_values' => [
                    ['key' => 'PEB', 'description' => 'PE-backed'],
                    ['key' => 'VCB', 'description' => 'VC-backed'],
                    ['key' => 'ACCIB', 'description' => 'Accelerator/incubator-backed'],
                ],
            ],
            [
                'key' => 'businessStatus',
                'description' => 'Companies can be found by their business status code.',
                'accepted_values' => [
                    ['key' => 'STAR', 'description' => 'Startup'],
                ],
            ],
            [
                'key' => 'city',
                'description' => 'Companies can be found by their city location.',
                'accepted_values' => 'open',
            ],
            [
                'key' => 'stateProvince',
                'description' => 'Companies can be found by their state location.',
                'accepted_values' => 'open',
            ],
            [
                'key' => 'country',
                'description' => 'Companies can be found by their country location.',
                'accepted_values' => 'open',
            ],
            [
                'key' => 'postCode',
                'description' => 'Companies can be found by their post code for both U.S. and global.',
                'accepted_values' => 'open',
            ],
            [
                'key' => 'locationType',
                'description' => 'Used in conjunction with stateProvince, country, and postCode to require the location to be a company\'s headquarters.',
                'accepted_values' => [
                    ['key' => 'HQ_ONLY', 'description' => 'Headquarters Only'],
                    ['key' => 'NON_HQ_ONLY', 'description' => 'Non-Headquarters Only'],
                    ['key' => 'ANY', 'description' => 'Any Location'],
                ],
            ],
            [
                'key' => 'dateFounded',
                'description' => 'Search for companies founded after a certain date using the > operator, companies founded before a certain date using the < operator and companies founded between 2 dates using the ^ operator. Format: YYYY-MM-DD.',
                'accepted_values' => 'open',
            ],
            [
                'key' => 'keywords',
                'description' => 'Search for companies by keywords they are associated with or keywords appearing in their business description.',
                'accepted_values' => 'open',
            ],
            [
                'key' => 'industry',
                'description' => 'Companies can be found by industry code.',
                'accepted_values' => 'open',
            ],
            [
                'key' => 'verticals',
                'description' => 'Companies can be found by vertical code.',
                'accepted_values' => 'open',
            ],
            [
                'key' => 'industryAndVertical',
                'description' => 'When using both industry and vertical parameters, "OR" logic is used by default. To use "AND" logic, set this parameter to True. Set this parameter in pair with industry and vertical options.',
                'accepted_values' => [
                    ['key' => 'True', 'description' => 'Company must be found in both industry AND vertical'],
                    ['key' => 'False', 'description' => 'Use OR logic between industry and vertical'],
                ],
            ],
            [
                'key' => 'emergingSpaces',
                'description' => 'Emerging Spaces are defined by PitchBook analysts for specific products or technological innovations that are growing in popularity. These spaces are dynamic and change over time. Companies can be found by using an emerging space code.',
                'accepted_values' => [
                    ['key' => '308', 'description' => 'Lidar'],
                    ['key' => '330', 'description' => 'Fusion energy'],
                ],
            ],
            [
                'key' => 'dealType',
                'description' => 'Find companies with a specific type of deal.',
                'accepted_values' => [
                    ['key' => 'EVC', 'description' => 'Early stage VC'],
                    ['key' => 'LVC', 'description' => 'Late stage VC'],
                    ['key' => 'BYSTG', 'description' => 'All VC stages'],
                    ['key' => 'LBO_', 'description' => 'Leveraged buyout'],
                ],
            ],
            [
                'key' => 'dealStatus',
                'description' => 'Distinguish between failed, upcoming, completed deals and more.',
                'accepted_values' => [
                    ['key' => 'COMP', 'description' => 'Completed'],
                ],
            ],
            [
                'key' => 'dealSize',
                'description' => 'Find companies who have had a deal of a certain size. Search for companies with a deal larger than an amount using the > operator, companies with a deal smaller than a certain amount using the < operator and companies with a deal size in a range using the ^ operator. Amounts in millions.',
                'accepted_values' => 'open',
            ],
            [
                'key' => 'includeDealsWithoutDealSize',
                'description' => 'Include deals without a known deal size. Set this parameter as True only when Deal Size parameter is applied. False by default.',
                'accepted_values' => [
                    ['key' => 'TRUE', 'description' => 'Include deals without known size'],
                    ['key' => 'FALSE', 'description' => 'Exclude deals without known size'],
                ],
            ],
            [
                'key' => 'excludeDealsWithoutDealSize',
                'description' => 'Exclude deals without a known deal size. Set this parameter as True only when Deal Size parameter is not applied. False by default.',
                'accepted_values' => [
                    ['key' => 'TRUE', 'description' => 'Exclude deals without known size'],
                    ['key' => 'FALSE', 'description' => 'Include deals without known size'],
                ],
            ],
            [
                'key' => 'dealDate',
                'description' => 'Search for companies with a deal after a certain date using the > operator, companies with a deal before a certain date using the < operator and companies with a deal between 2 dates using the ^ operator. Format: YYYY-MM-DD.',
                'accepted_values' => 'open',
            ],
            [
                'key' => 'totalRaised',
                'description' => 'Find companies by the total amount of money they have raised to date in millions. Use the > operator to find companies that have raised more than a certain value, use the < to find companies who have raised less than a certain value and the ^ operator to search within a range. Amounts in millions.',
                'accepted_values' => 'open',
            ],
            [
                'key' => 'investorNames',
                'description' => 'Pass a list of investor names, ids, websites or tickers. Will return results for companies by who is invested in them. Use a comma to separate values.',
                'accepted_values' => 'open',
            ],
            [
                'key' => 'partialExit',
                'description' => 'Find companies who have had a deal with partial investor\'s exit within them. To use this parameter set it as True.',
                'accepted_values' => [
                    ['key' => 'TRUE', 'description' => 'Include companies with partial exits'],
                    ['key' => 'FALSE', 'description' => 'Exclude companies with partial exits'],
                ],
            ],
            [
                'key' => 'fullExit',
                'description' => 'Find companies who have had a deal with full investor\'s exit within them. To use this parameter set it as True.',
                'accepted_values' => [
                    ['key' => 'TRUE', 'description' => 'Include companies with full exits'],
                    ['key' => 'FALSE', 'description' => 'Exclude companies with full exits'],
                ],
            ],
            [
                'key' => 'exitType',
                'description' => 'Find companies who have had a deal with full investor\'s exit within them. To use this parameter set it as True.',
                'accepted_values' => 'open',
            ],
            [
                'key' => 'exitStatus',
                'description' => 'Distinguish between failed, upcoming, completed exits and more.',
                'accepted_values' => [
                    ['key' => 'COMP', 'description' => 'Completed'],
                ],
            ],
            [
                'key' => 'exitSize',
                'description' => 'Find companies who have had a deal with investor\'s exit within them of a certain size. Search for companies with an exit larger than an amount using the > operator, companies with an exit smaller than a certain amount using the < operator and companies with an exit size in a range using the ^ operator. Amounts in millions.',
                'accepted_values' => 'open',
            ],
            [
                'key' => 'exitDate',
                'description' => 'Find companies who have had a deal with investor\'s exit within them of a certain time frame. Search for companies with an exit after a certain date using the > operator, companies with an exit before a certain date using the < operator and companies with an exit between 2 dates using the ^ operator. Format: YYYY-MM-DD.',
                'accepted_values' => 'open',
            ],
            [
                'key' => 'revenue',
                'description' => 'Find companies that have a certain amount of revenue. Search for companies with more revenue than an amount using the > operator, companies with less revenue than an amount using the < operator or companies within a range using the ^ operator. Amounts in millions.',
                'accepted_values' => 'open',
            ],
            [
                'key' => 'onlyMostRecentTransaction',
                'description' => 'Find companies by their most recent deal. To use this parameter set it as True.',
                'accepted_values' => [
                    ['key' => 'TRUE', 'description' => 'Only include most recent transaction'],
                    ['key' => 'FALSE', 'description' => 'Include all transactions'],
                ],
            ],
            [
                'key' => 'employeeCount',
                'description' => 'Find companies by number of employees. Use > for larger, < for smaller, ^ for range.',
                'accepted_values' => 'open',
            ],
            [
                'key' => 'currency',
                'description' => 'Specify the currency that your other parameters, such as dealSize, are entered as. It depends on the currency in user preferences.',
                'accepted_values' => 'open',
            ],
            [
                'key' => 'page',
                'description' => 'Results are returned so that they can be paged through. Set this parameter to increment the page.',
                'accepted_values' => 'open',
            ],
            [
                'key' => 'perPage',
                'description' => 'How many returned results show on page.',
                'accepted_values' => 'open',
            ],
        ];
    }
}
