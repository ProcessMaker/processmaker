<?php

namespace ProcessMaker\Services\DataSourceIntegrations\Integrations;

use Illuminate\Support\LazyCollection;
use ProcessMaker\Services\DataSourceIntegrations\Integrations\BaseIntegrationService;
use ProcessMaker\Services\DataSourceIntegrations\Integrations\IntegrationsInterface;
use ProcessMaker\Services\DataSourceIntegrations\Schemas\CrunchbaseApiSchema;

class CrunchbaseService extends BaseIntegrationService implements IntegrationsInterface
{
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
        // TODO: Implement real API call to Crunchbase
        return [
            'count' => 20088,
            'entities' => [
                [
                    'uuid' => '0ab984e9-7413-addd-0b09-7fd5fdef4150',
                    'properties' => [
                        'identifier' => [
                            'permalink' => 'national-institutes-of-health',
                            'image_id' => 'v1479301412/qdfrsmsuqu8m22jaaoor.png',
                            'uuid' => '0ab984e9-7413-addd-0b09-7fd5fdef4150',
                            'entity_def_id' => 'organization',
                            'value' => 'National Institutes of Health',
                        ],
                        'short_description' => 'National Institutes of Health is a biomedical research facility in the United States that focuses on biomedical and health-related research.',
                        'categories' => [
                            [
                                'entity_def_id' => 'category',
                                'permalink' => 'biotechnology',
                                'uuid' => '58842728-7ab9-5bd1-bb67-e8e55f6520a0',
                                'value' => 'Biotechnology',
                            ],
                            [
                                'entity_def_id' => 'category',
                                'permalink' => 'financial-services',
                                'uuid' => '90b4194f-1d4f-ff5c-d7a6-6b6f32ae4892',
                                'value' => 'Financial Services',
                            ],
                        ],
                        'rank_org' => 3,
                    ],
                ],
                [
                    'uuid' => '20135206-96eb-8be0-9ac4-670b257e532c',
                    'properties' => [
                        'identifier' => [
                            'permalink' => 'stanford-university',
                            'image_id' => 'kgi349quyogvrathgoxw',
                            'uuid' => '20135206-96eb-8be0-9ac4-670b257e532c',
                            'entity_def_id' => 'organization',
                            'value' => 'Stanford University',
                        ],
                        'short_description' => "Stanford University is one of the world's leading teaching and research universities.",
                        'categories' => [
                            [
                                'entity_def_id' => 'category',
                                'permalink' => 'biotechnology',
                                'uuid' => '58842728-7ab9-5bd1-bb67-e8e55f6520a0',
                                'value' => 'Biotechnology',
                            ],
                            [
                                'entity_def_id' => 'category',
                                'permalink' => 'communities',
                                'uuid' => 'bc4ee7e1-d4a1-c228-c551-29d716ba971f',
                                'value' => 'Communities',
                            ],
                        ],
                        'rank_org' => 241358,
                    ],
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
     * Fetch company details from Crunchbase API
     *
     * @param string $companyId The company ID
     * @return array The raw API response
     */
    private function fetchCompanyDetailsFromApi(string $companyId) : array
    {
        // TODO: Implement real API call to Crunchbase
        return [
            'properties'=> [
                'identifier'=> [
                    'uuid'=> '1dcf3d60-e7a2-95f0-0fbb-0c7a307184c0',
                    'value'=> 'goBaby',
                    'image_id'=> 'v1485305805/htbednnsfgdbm8zhd0zp.png',
                    'permalink'=> 'gobaby-2',
                    'entity_def_id'=> 'organization',
                ],
            ],
            'cards'=> [
                'press_references'=> [
                    [
                        'identifier'=> [
                            'uuid'=> '89016b44-5723-4486-b102-5dd697bfa16e',
                            'value'=> "The Global Association of Baby Equipment Rental Companies Partners with goBaby\n",
                            'entity_def_id'=> 'press_reference',
                        ],
                        'url'=> [
                            'value'=> "http://www.prweb.com/releases/2018/02/prweb15165216.htm\n",
                        ],
                        'posted_on'=> '2018-02-06',
                        'publisher'=> 'PRWeb',
                        'created_at'=> '2018-02-06T19:02:54Z',
                        'updated_at'=> '2018-02-13T00:24:54Z',
                    ],
                    [
                        'author'=> 'Tishin Donkersley',
                        'identifier'=> [
                            'uuid'=> '33539065-4f21-469c-8f00-607c705be105',
                            'value'=> 'Travel Made Easier with GoBaby, the Airbnb for Baby Gear',
                            'entity_def_id'=> 'press_reference',
                        ],
                        'url'=> [
                            'value'=> 'https://tech.co/travel-easier-gobaby-airbnb-baby-gear-2017-11',
                        ],
                        'posted_on'=> '2017-11-16',
                        'publisher'=> 'Tech Cocktail',
                        'thumbnail_url'=> 'https://tech.co/wp-content/uploads/2017/11/pexels-photo-265987-baby-e1510847602971.jpeg',
                        'created_at'=> '2017-11-17T02:19:53Z',
                        'updated_at'=> '2018-02-13T00:06:39Z',
                    ],
                    [
                        'author'=> 'Dave Baldwin',
                        'identifier'=> [
                            'uuid'=> '234383e5-2f1d-43dc-8b56-2ab389429ace',
                            'value'=> "7 Apps to Save Mad Money on Toys, Kids' Clothes, and Baby Gear",
                            'entity_def_id'=> 'press_reference',
                        ],
                        'url'=> [
                            'value'=> "https://www.fatherly.com/gear/apps-to-save-money-on-toys-kids-clothing-and-baby-gear/\n",
                        ],
                        'posted_on'=> '2017-09-21',
                        'publisher'=> 'Fatherly',
                        'thumbnail_url'=> 'https://images.fatherly.com/wp-content/uploads/2017/09/money-saving-apps.jpg?q=65&w=1200',
                        'created_at'=> '2017-10-30T16:51:44Z',
                        'updated_at'=> '2018-02-13T00:03:35Z',
                    ],
                ],
                'event_appearances'=> [
                    [
                        'event_identifier'=> [
                            'uuid'=> '17ab7994-5b11-3664-6200-3a2622db2a7d',
                            'value'=> 'Tech.Co Startup of the Year Competition',
                            'image_id'=> 'v1493156711/aiyngbfuwk3luugmytjs.jpg',
                            'permalink'=> "tech-co-startup-of-the-year-competition-2017425\n",
                            'entity_def_id'=> 'event',
                        ],
                        'name'=> "Tech.Co Startup of the Year Competition's contestant - goBaby",
                        'identifier'=> [
                            'uuid'=> '7db06baa-6290-43ab-8569-778066ee3d2e',
                            'value'=> "Tech.Co Startup of the Year Competition's contestant - goBaby",
                            'permalink'=> "gobaby-2-contestant-tech-co-startup-of-the-year-competition-2017425--7db06baa\n",
                            'entity_def_id'=> 'event_appearance',
                        ],
                        'event_starts_on'=> '2017-04-25',
                        'participant_identifier'=> [
                            'uuid'=> '1dcf3d60-e7a2-95f0-0fbb-0c7a307184c0',
                            'value'=> 'goBaby',
                            'image_id'=> 'v1485305805/htbednnsfgdbm8zhd0zp.png',
                            'permalink'=> 'gobaby-2',
                            'entity_def_id'=> 'organization',
                        ],
                        'created_at'=> '2017-10-30T16:37:30Z',
                        'updated_at'=> '2018-02-13T00:03:34Z',
                        'appearance_type'=> 'contestant',
                        'event_location_identifiers'=> [
                            [
                                'uuid'=> '528f5e3c-90d1-1111-5d1c-2e4ff979d58e',
                                'value'=> 'San Francisco',
                                'permalink'=> 'san-francisco-california',
                                'entity_def_id'=> 'location',
                            ],
                            [
                                'uuid'=> 'eb879a83-c91a-121e-0bb8-829782dbcf04',
                                'value'=> 'California',
                                'permalink'=> 'california-united-states',
                                'entity_def_id'=> 'location',
                            ],
                            [
                                'uuid'=> 'f110fca2-1055-99f6-996d-011c198b3928',
                                'value'=> 'United States',
                                'permalink'=> 'united-states',
                                'entity_def_id'=> 'location',
                            ],
                            [
                                'uuid'=> 'b25caef9-a1b8-3a5d-6232-93b2dfb6a1d1',
                                'value'=> 'North America',
                                'permalink'=> 'north-america',
                                'entity_def_id'=> 'location',
                            ],
                        ],
                    ],
                    [
                        'event_identifier'=> [
                            'uuid'=> '4d6eab78-fb25-dac5-33ec-51fd8f329551',
                            'value'=> 'TechDay New York 2016',
                            'image_id'=> 'v1457601178/ei5chv8zhylujwetml9x.png',
                            'permalink'=> 'techday-new-york-2016-2016421',
                            'entity_def_id'=> 'event',
                        ],
                        'name'=> "TechDay New York 2016's exhibitor - goBaby",
                        'identifier'=> [
                            'uuid'=> '44ae6b7a-2a2c-4f24-a36b-66ee99084c08',
                            'value'=> "TechDay New York 2016's exhibitor - goBaby",
                            'permalink'=> "gobaby-2-exhibitor-techday-new-york-2016-2016421--44ae6b7a\n",
                            'entity_def_id'=> 'event_appearance',
                        ],
                        'event_starts_on'=> '2016-04-21',
                        'participant_identifier'=> [
                            'uuid'=> '1dcf3d60-e7a2-95f0-0fbb-0c7a307184c0',
                            'value'=> 'goBaby',
                            'image_id'=> 'v1485305805/htbednnsfgdbm8zhd0zp.png',
                            'permalink'=> 'gobaby-2',
                            'entity_def_id'=> 'organization',
                        ],
                        'created_at'=> '2016-03-10T09:00:04Z',
                        'updated_at'=> '2018-02-13T01:15:18Z',
                        'appearance_type'=> 'exhibitor',
                        'event_location_identifiers'=> [
                            [
                                'uuid'=> 'd64b7615-985c-fbf4-4aff-aa89d70c4050',
                                'value'=> 'New York',
                                'permalink'=> 'new-york-new-york',
                                'entity_def_id'=> 'location',
                            ],
                            [
                                'uuid'=> '83ead471-332b-d02e-67b7-67279aed075b',
                                'value'=> 'New York',
                                'permalink'=> 'new-york-united-states',
                                'entity_def_id'=> 'location',
                            ],
                            [
                                'uuid'=> 'f110fca2-1055-99f6-996d-011c198b3928',
                                'value'=> 'United States',
                                'permalink'=> 'united-states',
                                'entity_def_id'=> 'location',
                            ],
                            [
                                'uuid'=> 'b25caef9-a1b8-3a5d-6232-93b2dfb6a1d1',
                                'value'=> 'North America',
                                'permalink'=> 'north-america',
                                'entity_def_id'=> 'location',
                            ],
                        ],
                    ],
                ],
                'participated_investments'=> [],
                'headquarters_address'=> [
                    [
                        'identifier'=> [
                            'uuid'=> '2ebfa3b6-2e3c-0959-c358-bfe7405417e4',
                            'value'=> 'Main Office',
                            'entity_def_id'=> 'address',
                        ],
                        'street_1'=> '68 3rd Street',
                        'postal_code'=> '11231',
                        'created_at'=> '2016-03-10T09:02:59Z',
                        'location_identifiers'=> [
                            [
                                'uuid'=> 'd64b7615-985c-fbf4-4aff-aa89d70c4050',
                                'value'=> 'New York',
                                'permalink'=> 'new-york-new-york',
                                'entity_def_id'=> 'location',
                            ],
                            [
                                'uuid'=> '83ead471-332b-d02e-67b7-67279aed075b',
                                'value'=> 'New York',
                                'permalink'=> 'new-york-united-states',
                                'entity_def_id'=> 'location',
                            ],
                            [
                                'uuid'=> 'f110fca2-1055-99f6-996d-011c198b3928',
                                'value'=> 'United States',
                                'permalink'=> 'united-states',
                                'entity_def_id'=> 'location',
                            ],
                            [
                                'uuid'=> 'b25caef9-a1b8-3a5d-6232-93b2dfb6a1d1',
                                'value'=> 'North America',
                                'permalink'=> 'north-america',
                                'entity_def_id'=> 'location',
                            ],
                        ],
                        'updated_at'=> '2018-02-13T00:03:34Z',
                    ],
                ],
                'child_organizations'=> [],
                'raised_investments'=> [],
                'participated_funds'=> [],
                'raised_funding_rounds'=> [
                    [
                        'identifier'=> [
                            'uuid'=> '25d419b7-acb2-47da-9cbd-cd6d949b40f9',
                            'value'=> 'Seed Round - goBaby',
                            'image_id'=> 'v1485305805/htbednnsfgdbm8zhd0zp.png',
                            'permalink'=> 'gobaby-2-seed--25d419b7',
                            'entity_def_id'=> 'funding_round',
                        ],
                        'rank_funding_round'=> 147908,
                        'announced_on'=> '2017-05-10',
                        'money_raised'=> [
                            'value'=> 40000,
                            'currency'=> 'USD',
                            'value_usd'=> 40000,
                        ],
                        'short_description'=> "goBaby raised $40000 on 2017-05-10 in Seed Round\n",
                        'investment_stage'=> 'seed',
                        'is_equity'=> true,
                        'investment_type'=> 'seed',
                        'funded_organization_identifier'=> [
                            'role'=> 'investee',
                            'uuid'=> '1dcf3d60-e7a2-95f0-0fbb-0c7a307184c0',
                            'value'=> 'goBaby',
                            'image_id'=> 'v1485305805/htbednnsfgdbm8zhd0zp.png',
                            'permalink'=> 'gobaby-2',
                            'entity_def_id'=> 'organization',
                        ],
                        'created_at'=> '2017-10-30T16:35:41Z',
                        'updated_at'=> '2018-02-13T00:38:13Z',
                    ],
                ],
                'child_ownerships'=> [],
                'founders'=> [
                    [
                        'first_name'=> 'Ksenia',
                        'rank_delta_d30'=> -0.1,
                        'num_founded_organizations'=> 1,
                        'identifier'=> [
                            'uuid'=> 'f3f8d11c-ebd5-4399-aa2b-8f7328ad0544',
                            'value'=> 'Ksenia Bolobine',
                            'image_id'=> 'qhgudccw9hvwk9o4zqzh',
                            'permalink'=> 'ksenia-bolobine',
                            'entity_def_id'=> 'person',
                        ],
                        'short_description'=> 'Ksenia Bolobine',
                        'num_jobs'=> 1,
                        'last_name'=> 'Bolobine',
                        'rank_person'=> 565938,
                        'created_at'=> '2017-10-30T16:39:26Z',
                        'num_current_jobs'=> 1,
                        'rank_delta_d90'=> -0.1,
                        'updated_at'=> '2018-02-12T22:25:43Z',
                        'rank_delta_d7'=> -0.1,
                        'gender'=> 'female',
                    ],
                    [
                        'location_group_identifiers'=> [
                            [
                                'uuid'=> 'b16ebe2d-033d-4d01-abfd-e5fbf811dc2f',
                                'value'=> 'Greater New York Area',
                                'permalink'=> 'greater-new-york-area',
                                'entity_def_id'=> 'location',
                            ],
                            [
                                'uuid'=> '6c6b9ca5-966a-448b-8480-29b5a753f9ee',
                                'value'=> 'East Coast',
                                'permalink'=> 'east-coast-united-states',
                                'entity_def_id'=> 'location',
                            ],
                            [
                                'uuid'=> '75989641-ef66-456e-b7cd-dfa57b34e881',
                                'value'=> 'Northeastern US',
                                'permalink'=> 'northeastern-united-states',
                                'entity_def_id'=> 'location',
                            ],
                        ],
                        'first_name'=> 'Natalie',
                        'rank_delta_d30'=> 3.4,
                        'num_founded_organizations'=> 3,
                        'identifier'=> [
                            'uuid'=> 'df620aec-b41f-ec54-6987-6a66d1cbd7a7',
                            'value'=> 'Natalie Kaminski',
                            'image_id'=> 'zqopsjws4n0iipoffdw1',
                            'permalink'=> 'natalie-kaminski',
                            'entity_def_id'=> 'person',
                        ],
                        'primary_job_title'=> 'Head of US Operations, Partner',
                        'linkedin'=> [
                            'value'=> "https://www.linkedin.com/in/nataliekaminski/\n",
                        ],
                        'short_description'=> 'Natalie Kaminski - Head of US Operations, Partner @ JetRockets',
                        'num_jobs'=> 2,
                        'last_name'=> 'Kaminski',
                        'primary_organization'=> [
                            'uuid'=> '8c25b15f-4d03-408d-90a5-a4aa40978747',
                            'value'=> 'JetRockets',
                            'image_id'=> 'jxurw6o3lkzahrm2xqxg',
                            'permalink'=> 'jetrockets',
                            'entity_def_id'=> 'organization',
                        ],
                        'rank_person'=> 182476,
                        'created_at'=> '2011-01-27T01:56:41Z',
                        'location_identifiers'=> [
                            [
                                'uuid'=> 'd64b7615-985c-fbf4-4aff-aa89d70c4050',
                                'value'=> 'New York',
                                'permalink'=> 'new-york-new-york',
                                'entity_def_id'=> 'location',
                            ],
                            [
                                'uuid'=> '83ead471-332b-d02e-67b7-67279aed075b',
                                'value'=> 'New York',
                                'permalink'=> 'new-york-united-states',
                                'entity_def_id'=> 'location',
                            ],
                            [
                                'uuid'=> 'f110fca2-1055-99f6-996d-011c198b3928',
                                'value'=> 'United States',
                                'permalink'=> 'united-states',
                                'entity_def_id'=> 'location',
                            ],
                            [
                                'uuid'=> 'b25caef9-a1b8-3a5d-6232-93b2dfb6a1d1',
                                'value'=> 'North America',
                                'permalink'=> 'north-america',
                                'entity_def_id'=> 'location',
                            ],
                        ],
                        'num_articles'=> 1,
                        'num_current_jobs'=> 2,
                        'rank_delta_d90'=> 3.4,
                        'updated_at'=> '2019-06-26T20:46:43Z',
                        'rank_delta_d7'=> -0.8,
                        'gender'=> 'female',
                    ],
                ],
                'ipos'=> [],
                'raised_funds'=> [],
                'parent_organization'=> [],
                'acquirer_acquisitions'=> [],
                'parent_ownership'=> [],
                'fields'=> [
                    'company_type'=> 'for_profit',
                    'location_group_identifiers'=> [
                        [
                            'uuid'=> 'b16ebe2d-033d-4d01-abfd-e5fbf811dc2f',
                            'value'=> 'Greater New York Area',
                            'permalink'=> 'greater-new-york-area',
                            'entity_def_id'=> 'location',
                            'location_type'=> 'group',
                        ],
                        [
                            'uuid'=> '6c6b9ca5-966a-448b-8480-29b5a753f9ee',
                            'value'=> 'East Coast',
                            'permalink'=> 'east-coast-united-states',
                            'entity_def_id'=> 'location',
                            'location_type'=> 'group',
                        ],
                        [
                            'uuid'=> '75989641-ef66-456e-b7cd-dfa57b34e881',
                            'value'=> 'Northeastern US',
                            'permalink'=> 'northeastern-united-states',
                            'entity_def_id'=> 'location',
                            'location_type'=> 'group',
                        ],
                    ],
                    'rank_delta_d30'=> 0.1,
                    'founded_on'=> [
                        'value'=> '2015-09-15',
                        'precision'=> 'day',
                    ],
                    'website'=> [
                        'value'=> 'http://www.gobaby.co',
                    ],
                    'equity_funding_total'=> [
                        'value'=> 40000,
                        'currency'=> 'USD',
                        'value_usd'=> 40000,
                    ],
                    'identifier'=> [
                        'uuid'=> '1dcf3d60-e7a2-95f0-0fbb-0c7a307184c0',
                        'value'=> 'goBaby',
                        'image_id'=> 'v1485305805/htbednnsfgdbm8zhd0zp.png',
                        'permalink'=> 'gobaby-2',
                        'entity_def_id'=> 'organization',
                    ],
                    'founder_identifiers'=> [
                        [
                            'uuid'=> 'f3f8d11c-ebd5-4399-aa2b-8f7328ad0544',
                            'value'=> 'Ksenia Bolobine',
                            'image_id'=> 'qhgudccw9hvwk9o4zqzh',
                            'permalink'=> 'ksenia-bolobine',
                            'entity_def_id'=> 'person',
                        ],
                        [
                            'uuid'=> 'df620aec-b41f-ec54-6987-6a66d1cbd7a7',
                            'value'=> 'Natalie Kaminski',
                            'image_id'=> 'zqopsjws4n0iipoffdw1',
                            'permalink'=> 'natalie-kaminski',
                            'entity_def_id'=> 'person',
                        ],
                    ],
                    'description'=> "goBaby, the Airbnb for Baby-Gear-on-the-Go, is a peer-to-peer rental marketplace and on-demand app that helps families reduce the stress of traveling by leaving their cumbersome strollers, baby seats, and Pack 'n Plays at home.\n\nHow? By renting from goBaby's trusted community of local parents!\n",
                    'category_groups'=> [
                        [
                            'uuid'=> '26833aa6-0585-2aa7-8c69-63b4b14727c5',
                            'value'=> 'Apps',
                            'permalink'=> 'apps-2683',
                            'entity_def_id'=> 'category_group',
                        ],
                        [
                            'uuid'=> '3805abe5-5b70-a4a1-a51e-bcda8257aca0',
                            'value'=> 'Commerce and Shopping',
                            'permalink'=> 'commerce-and-shopping',
                            'entity_def_id'=> 'category_group',
                        ],
                        [
                            'uuid'=> 'ec09d1af-e88f-6a8d-1db8-1dd5e3d49ea0',
                            'value'=> 'Mobile',
                            'permalink'=> 'mobile-ec09',
                            'entity_def_id'=> 'category_group',
                        ],
                        [
                            'uuid'=> '85b6bca9-930a-11bc-a608-a513b76fb637',
                            'value'=> 'Software',
                            'permalink'=> 'software-85b6',
                            'entity_def_id'=> 'category_group',
                        ],
                        [
                            'uuid'=> '2c1d0b56-8f0e-4d94-a102-53d32a969d35',
                            'value'=> 'Travel and Tourism',
                            'permalink'=> 'travel-and-tourism',
                            'entity_def_id'=> 'category_group',
                        ],
                    ],
                    'linkedin'=> [
                        'value'=> 'https://www.linkedin.com/company/gobaby',
                    ],
                    'short_description'=> 'The Airbnb for baby gear rentals.',
                    'num_current_positions'=> 2,
                    'operating_status'=> 'active',
                    'rank_org'=> 59778,
                    'facebook'=> [
                        'value'=> 'https://www.facebook.com/goBaby.co',
                    ],
                    'num_employees_enum'=> 'c_00001_00010',
                    'status'=> 'operating',
                    'funding_total'=> [
                        'value'=> 40000,
                        'currency'=> 'USD',
                        'value_usd'=> 40000,
                    ],
                    'num_funding_rounds'=> 1,
                    'last_equity_funding_type'=> 'seed',
                    'last_funding_type'=> 'seed',
                    'categories'=> [
                        [
                            'uuid'=> '5eec8ef3-9a35-3b26-1184-9dff459291fd',
                            'value'=> 'Family',
                            'permalink'=> 'family',
                            'entity_def_id'=> 'category',
                        ],
                        [
                            'uuid'=> '772da8fe-26d7-ea09-00ff-0fc7c8368b50',
                            'value'=> 'Marketplace',
                            'permalink'=> 'marketplace-772d',
                            'entity_def_id'=> 'category',
                        ],
                        [
                            'uuid'=> '78f709ae-d8fb-cd28-218a-f090789f628f',
                            'value'=> 'Mobile Apps',
                            'permalink'=> 'mobile-apps',
                            'entity_def_id'=> 'category',
                        ],
                        [
                            'uuid'=> 'e69d7dbc-b29e-a56f-6dfa-f664b545fd29',
                            'value'=> 'Sharing Economy',
                            'permalink'=> 'sharing-economy',
                            'entity_def_id'=> 'category',
                        ],
                        [
                            'uuid'=> '8672f521-ce9a-e851-adff-c35d2441a0ad',
                            'value'=> 'Travel',
                            'permalink'=> 'travel',
                            'entity_def_id'=> 'category',
                        ],
                    ],
                    'created_at'=> '2016-03-10T09:00:04Z',
                    'location_identifiers'=> [
                        [
                            'uuid'=> 'd64b7615-985c-fbf4-4aff-aa89d70c4050',
                            'value'=> 'New York',
                            'permalink'=> 'new-york-new-york',
                            'entity_def_id'=> 'location',
                            'location_type'=> 'city',
                        ],
                        [
                            'uuid'=> '83ead471-332b-d02e-67b7-67279aed075b',
                            'value'=> 'New York',
                            'permalink'=> 'new-york-united-states',
                            'entity_def_id'=> 'location',
                            'location_type'=> 'region',
                        ],
                        [
                            'uuid'=> 'f110fca2-1055-99f6-996d-011c198b3928',
                            'value'=> 'United States',
                            'permalink'=> 'united-states',
                            'entity_def_id'=> 'location',
                            'location_type'=> 'country',
                        ],
                        [
                            'uuid'=> 'b25caef9-a1b8-3a5d-6232-93b2dfb6a1d1',
                            'value'=> 'North America',
                            'permalink'=> 'north-america',
                            'entity_def_id'=> 'location',
                            'location_type'=> 'continent',
                        ],
                    ],
                    'num_articles'=> 9,
                    'last_funding_at'=> '2017-05-10',
                    'num_event_appearances'=> 2,
                    'twitter'=> [
                        'value'=> 'https://twitter.com/gobabyco',
                    ],
                    'rank_delta_d90'=> 1,
                    'updated_at'=> '2019-06-24T21:47:56Z',
                    'last_equity_funding_total'=> [
                        'value'=> 40000,
                        'currency'=> 'USD',
                        'value_usd'=> 40000,
                    ],
                    'contact_email'=> 'hello@gobaby.co',
                    'funding_stage'=> 'seed',
                    'rank_delta_d7'=> -0.4,
                    'last_funding_total'=> [
                        'value'=> 40000,
                        'currency'=> 'USD',
                        'value_usd'=> 40000,
                    ],
                    'num_founders'=> 2,
                ],
            ],
            'investors'=> [],
            'acquiree_acquisitions'=> [],
            'participated_funding_rounds'=> [],
        ];
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
        return $item['uuid'] ?? null;
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
     * @return string|null Contact email
     */
    protected function extractContactEmail(array $item) : ?string
    {
        return $item['properties']['contact_email'] ?? null;
    }
}
