<?php

namespace ProcessMaker\Services\DataSourceIntegrations\Schemas;

class CrunchbaseApiSchema
{
    /**
     * Get the schema definition for Crunchbase API
     *
     * @return array Schema definition
     */
    public static function getSchema(): array
    {
        return [
            'parameters' => [
                [
                    'key' => 'acquirer_identifier',
                    'description' => 'Name of the organization that made the acquisition',
                    'accepted_values' => 'open',
                    'searchable' => true,
                    'search_operators' => ['blank', 'contains', 'eq', 'includes', 'not_contains', 'not_eq', 'not_includes', 'starts'],
                ],
                [
                    'key' => 'aliases',
                    'description' => 'Alternate or previous names for the organization',
                    'accepted_values' => 'open',
                    'searchable' => true,
                    'search_operators' => ['blank', 'contains', 'eq', 'not_contains', 'not_eq', 'starts'],
                ],
                [
                    'key' => 'categories',
                    'description' => 'Descriptive keyword for an Organization (e.g. SaaS, Android, Cloud Computing, Medical Device)',
                    'accepted_values' => 'open',
                    'searchable' => true,
                    'search_operators' => ['blank', 'includes', 'includes_all', 'not_includes', 'not_includes_all'],
                ],
                [
                    'key' => 'category_groups',
                    'description' => 'Superset of Industries (e.g. Software, Mobile, Health Care)',
                    'accepted_values' => 'open',
                    'searchable' => true,
                    'search_operators' => ['blank', 'includes', 'includes_all', 'not_includes', 'not_includes_all'],
                ],
                [
                    'key' => 'closed_on',
                    'description' => 'The date when the organization is closed',
                    'accepted_values' => 'open',
                    'searchable' => true,
                    'search_operators' => ['between', 'blank', 'eq', 'gte', 'lte'],
                ],
                [
                    'key' => 'company_type',
                    'description' => 'Whether an Organization is for profit or non-profit',
                    'accepted_values' => [
                        ['key' => 'for_profit', 'description' => 'For Profit', 'required' => false],
                        ['key' => 'non_profit', 'description' => 'Non-profit', 'required' => false],
                    ],
                    'searchable' => true,
                    'search_operators' => ['blank', 'eq', 'includes', 'not_eq', 'not_includes'],
                ],
                [
                    'key' => 'contact_email',
                    'description' => 'General contact email for the organization',
                    'accepted_values' => 'open',
                    'searchable' => false,
                    'search_operators' => [],
                ],
                [
                    'key' => 'created_at',
                    'description' => 'Creation date',
                    'accepted_values' => 'open',
                    'searchable' => true,
                    'search_operators' => ['between', 'blank', 'eq', 'gte', 'lte'],
                ],
                [
                    'key' => 'delisted_on',
                    'description' => 'The date when the Organization removed its stock from the stock exchange',
                    'accepted_values' => 'open',
                    'searchable' => true,
                    'search_operators' => ['between', 'blank', 'eq', 'gte', 'lte'],
                ],
                [
                    'key' => 'demo_days',
                    'description' => 'Whether an accelerator hosts any demo days',
                    'accepted_values' => 'open',
                    'searchable' => true,
                    'search_operators' => ['blank', 'eq'],
                ],
                [
                    'key' => 'description',
                    'description' => 'Organization Description, Industries, Industry Groups',
                    'accepted_values' => 'open',
                    'searchable' => true,
                    'search_operators' => ['blank', 'contains', 'not_contains'],
                ],
                [
                    'key' => 'diversity_spotlights',
                    'description' => 'Types of diversity represented in an organization',
                    'accepted_values' => 'open',
                    'searchable' => true,
                    'search_operators' => ['blank', 'includes', 'includes_all', 'not_includes', 'not_includes_all'],
                ],
                [
                    'key' => 'entity_def_id',
                    'description' => 'Entity definition ID',
                    'accepted_values' => [
                        ['key' => 'organization', 'description' => 'Organization', 'required' => false],
                    ],
                    'searchable' => true,
                    'search_operators' => ['blank', 'eq', 'includes', 'not_eq', 'not_includes'],
                ],
                [
                    'key' => 'equity_funding_total',
                    'description' => 'Total funding amount raised across all Funding Rounds excluding debt',
                    'accepted_values' => 'open',
                    'searchable' => true,
                    'search_operators' => ['between', 'blank', 'eq', 'gt', 'gte', 'lt', 'lte', 'not_eq'],
                ],
                [
                    'key' => 'exited_on',
                    'description' => 'Date the organization was acquired or went public',
                    'accepted_values' => 'open',
                    'searchable' => true,
                    'search_operators' => ['between', 'blank', 'eq', 'gte', 'lte'],
                ],
                [
                    'key' => 'facebook',
                    'description' => 'Link to Organization\'s Facebook page',
                    'accepted_values' => 'open',
                    'searchable' => false,
                    'search_operators' => [],
                ],
                [
                    'key' => 'facet_ids',
                    'description' => 'Facet IDs',
                    'accepted_values' => [
                        ['key' => 'company', 'description' => 'Company', 'required' => false],
                        ['key' => 'investor', 'description' => 'Investor', 'required' => false],
                        ['key' => 'school', 'description' => 'School', 'required' => false],
                    ],
                    'searchable' => true,
                    'search_operators' => ['blank', 'includes', 'includes_all', 'not_includes', 'not_includes_all'],
                ],
                [
                    'key' => 'founded_on',
                    'description' => 'Date the Organization was founded',
                    'accepted_values' => 'open',
                    'searchable' => true,
                    'search_operators' => ['between', 'blank', 'eq', 'gte', 'lte'],
                ],
                [
                    'key' => 'founder_identifiers',
                    'description' => 'Founders of the organization',
                    'accepted_values' => 'open',
                    'searchable' => true,
                    'search_operators' => ['blank', 'includes', 'includes_all', 'not_includes', 'not_includes_all'],
                ],
                [
                    'key' => 'funding_stage',
                    'description' => 'This field describes an organization\'s most recent funding status',
                    'accepted_values' => [
                        ['key' => 'early_stage_venture', 'description' => 'Early Stage Venture', 'required' => false],
                        ['key' => 'ipo', 'description' => 'IPO', 'required' => false],
                        ['key' => 'late_stage_venture', 'description' => 'Late Stage Venture', 'required' => false],
                        ['key' => 'm_and_a', 'description' => 'M&A', 'required' => false],
                        ['key' => 'private_equity', 'description' => 'Private Equity', 'required' => false],
                        ['key' => 'seed', 'description' => 'Seed', 'required' => false],
                    ],
                    'searchable' => true,
                    'search_operators' => ['blank', 'eq', 'includes', 'not_eq', 'not_includes'],
                ],
                [
                    'key' => 'funding_total',
                    'description' => 'Total amount raised across all funding rounds',
                    'accepted_values' => 'open',
                    'searchable' => true,
                    'search_operators' => ['between', 'blank', 'eq', 'gt', 'gte', 'lt', 'lte', 'not_eq'],
                ],
                [
                    'key' => 'funds_total',
                    'description' => 'Total funding amount raised across all Fund Raises',
                    'accepted_values' => 'open',
                    'searchable' => true,
                    'search_operators' => ['between', 'blank', 'eq', 'gt', 'gte', 'lt', 'lte', 'not_eq'],
                ],
                [
                    'key' => 'hub_tags',
                    'description' => 'Tags are labels assigned to organizations, which identify their belonging to a group with that shared label',
                    'accepted_values' => [
                        ['key' => 'cbvp', 'description' => 'Crunchbase Venture Program', 'required' => false],
                        ['key' => 'emerging_unicorn', 'description' => 'Emerging Unicorn', 'required' => false],
                        ['key' => 'exited_unicorn', 'description' => 'Exited Unicorn', 'required' => false],
                        ['key' => 'pledge_one_percent', 'description' => 'Pledge 1%', 'required' => false],
                        ['key' => 'unicorn', 'description' => 'Unicorn', 'required' => false],
                    ],
                    'searchable' => true,
                    'search_operators' => ['blank', 'includes', 'includes_all', 'not_includes', 'not_includes_all'],
                ],
                [
                    'key' => 'identifier',
                    'description' => 'Name of the Organization',
                    'accepted_values' => 'open',
                    'searchable' => true,
                    'search_operators' => ['blank', 'contains', 'eq', 'includes', 'not_contains', 'not_eq', 'not_includes', 'starts'],
                ],
                [
                    'key' => 'image_id',
                    'description' => 'The profile image of the organization on Crunchbase',
                    'accepted_values' => 'open',
                    'searchable' => false,
                    'search_operators' => [],
                ],
                [
                    'key' => 'image_url',
                    'description' => 'The cloudinary url of the profile image',
                    'accepted_values' => 'open',
                    'searchable' => false,
                    'search_operators' => [],
                ],
                [
                    'key' => 'investor_identifiers',
                    'description' => 'The top 5 investors with investments in this company, ordered by Crunchbase Rank',
                    'accepted_values' => 'open',
                    'searchable' => true,
                    'search_operators' => ['blank', 'includes', 'includes_all', 'not_includes', 'not_includes_all'],
                ],
                [
                    'key' => 'num_employees_enum',
                    'description' => 'Total number of employees',
                    'accepted_values' => [
                        ['key' => 'c_00001_00010', 'description' => '1-10', 'required' => false],
                        ['key' => 'c_00011_00050', 'description' => '11-50', 'required' => false],
                        ['key' => 'c_00051_00100', 'description' => '51-100', 'required' => false],
                        ['key' => 'c_00101_00250', 'description' => '101-250', 'required' => false],
                        ['key' => 'c_00251_00500', 'description' => '251-500', 'required' => false],
                        ['key' => 'c_00501_01000', 'description' => '501-1000', 'required' => false],
                        ['key' => 'c_01001_05000', 'description' => '1001-5000', 'required' => false],
                        ['key' => 'c_05001_10000', 'description' => '5001-10000', 'required' => false],
                        ['key' => 'c_10001_max', 'description' => '10001+', 'required' => false],
                    ],
                    'searchable' => true,
                    'search_operators' => ['blank', 'eq', 'includes', 'not_eq', 'not_includes'],
                ],
                [
                    'key' => 'operating_status',
                    'description' => 'Operating Status of Organization e.g. Active, Closed',
                    'accepted_values' => [
                        ['key' => 'active', 'description' => 'Active', 'required' => false],
                        ['key' => 'closed', 'description' => 'Closed', 'required' => false],
                    ],
                    'searchable' => true,
                    'search_operators' => ['blank', 'eq', 'includes', 'not_eq', 'not_includes'],
                ],
                [
                    'key' => 'revenue_range',
                    'description' => 'Estimated revenue range for organization',
                    'accepted_values' => [
                        ['key' => 'r_00000000', 'description' => 'Less than $1M', 'required' => false],
                        ['key' => 'r_00001000', 'description' => '$1M to $10M', 'required' => false],
                        ['key' => 'r_00010000', 'description' => '$10M to $50M', 'required' => false],
                        ['key' => 'r_00050000', 'description' => '$50M to $100M', 'required' => false],
                        ['key' => 'r_00100000', 'description' => '$100M to $500M', 'required' => false],
                        ['key' => 'r_00500000', 'description' => '$500M to $1B', 'required' => false],
                        ['key' => 'r_01000000', 'description' => '$1B to $10B', 'required' => false],
                        ['key' => 'r_10000000', 'description' => '$10B+', 'required' => false],
                    ],
                    'searchable' => true,
                    'search_operators' => ['blank', 'eq', 'includes', 'not_eq', 'not_includes'],
                ],
                [
                    'key' => 'short_description',
                    'description' => 'Text of Organization Description, Industries, and Industry Groups',
                    'accepted_values' => 'open',
                    'searchable' => true,
                    'search_operators' => ['blank', 'contains', 'not_contains'],
                ],
                [
                    'key' => 'website',
                    'description' => 'Link to homepage. note: website_url has replaced this field; this field will be deprecated in the near future',
                    'accepted_values' => 'open',
                    'searchable' => false,
                    'search_operators' => [],
                ],
                [
                    'key' => 'website_url',
                    'description' => 'Link to homepage',
                    'accepted_values' => 'open',
                    'searchable' => true,
                    'search_operators' => ['domain_blank', 'domain_eq', 'domain_includes', 'not_domain_eq', 'not_domain_includes'],
                ],
            ],
            'order' => [
                [
                    'key' => 'field_id',
                    'description' => 'Name of the field to sort on',
                    'required' => true,
                ],
                [
                    'key' => 'sort',
                    'description' => 'Direction of sorting',
                    'required' => true,
                    'accepted_values' => [
                        ['key' => 'asc', 'description' => 'Ascending'],
                        ['key' => 'desc', 'description' => 'Descending'],
                    ],
                ],
                [
                    'key' => 'nulls',
                    'description' => 'Whether to include null values at the beginning or end of the sort. Defaults to end.',
                    'required' => false,
                    'accepted_values' => [
                        ['key' => 'first', 'description' => 'First'],
                        ['key' => 'last', 'description' => 'Last'],
                    ],
                ],
            ],
            'operator_id'=> [
                [
                    'key' => 'blank',
                    'description' => 'Blank',
                ],
                [
                    'key' => 'eq',
                    'description' => 'Equal',
                ],
                [
                    'key' => 'not_eq',
                    'description' => 'Not equal',
                ],
                [
                    'key' => 'gt',
                    'description' => 'Greater than',
                ],
                [
                    'key' => 'gte',
                    'description' => 'Greater than or equal',
                ],
                [
                    'key' => 'lt',
                    'description' => 'Less than',
                ],
                [
                    'key' => 'lte',
                    'description' => 'Less than or equal',
                ],
                [
                    'key' => 'starts',
                    'description' => 'Starts',
                ],
                [
                    'key' => 'contains',
                    'description' => 'Contains',
                ],
                [
                    'key' => 'between',
                    'description' => 'Between',
                ],
                [
                    'key' => 'includes',
                    'description' => 'Includes',
                ],
                [
                    'key' => 'not_includes',
                    'description' => 'Does not include',
                ],
                [
                    'key' => 'includes_all',
                    'description' => 'Includes all',
                ],
                [
                    'key' => 'not_includes_all',
                    'description' => 'Does not include all',
                ],
                [
                    'key' => 'domain_eq',
                    'description' => 'Domain equals',
                ],
                [
                    'key' => 'domain_blank',
                    'description' => 'Domain is blank',
                ],
            ],
            'type'=> [
                'required' => true,
                'description' => 'The type of query',
                'accepted_values' => [
                    ['key' => 'predicate'],
                ],
            ],
            'limit' => [
                'key' => 'limit',
                'description' => 'Number of rows to return. Default is 100, min is 1, max is 2000.',
            ],
        ];
    }
}
