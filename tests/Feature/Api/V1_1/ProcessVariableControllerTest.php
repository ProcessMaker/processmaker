<?php

namespace Tests\Feature\Api\V1_1;

use ProcessMaker\Models\User;
use ProcessMaker\Package\SavedSearch\Models\SavedSearch;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;

class ProcessVariableControllerTest extends TestCase
{
    use RequestHelper;

    /**
     * Set up test environment by creating a test user and authenticating as them
     *
     * @return void
     */
    public function setupCreateUser()
    {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /**
     * Test successful variables retrieval with pagination
     */
    public function test_can_get_process_variables_with_pagination(): void
    {
        // Make request to the endpoint
        $response = $this->apiCall('GET', '/api/1.1/processes/variables?processIds=1,2,3&page=1&per_page=15');

        // Assert response structure and status
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'process_id',
                        'uuid',
                        'format',
                        'label',
                        'field',
                        'asset' => [
                            'id',
                            'type',
                            'name',
                            'uuid',
                        ],
                        'created_at',
                        'updated_at',
                    ]
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'path',
                    'per_page',
                    'to',
                    'total',
                ]
            ]);

        // Assert pagination works correctly
        $responseData = $response->json();
        $this->assertEquals(15, $responseData['meta']['per_page']);
        $this->assertEquals(1, $responseData['meta']['current_page']);

        // Since we're generating 10 variables per process (3 processes = 30 total)
        $this->assertEquals(30, $responseData['meta']['total']);
    }

    /**
     * Test validation for required processIds parameter
     */
    public function test_process_ids_are_required(): void
    {
        $response = $this->apiCall('GET', '/api/1.1/processes/variables');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['processIds']);
    }

    /**
     * Test validation for per_page parameter
     */
    public function test_per_page_validation(): void
    {
        $response = $this->apiCall('GET', '/api/1.1/processes/variables?processIds=1&per_page=101');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    /**
     * Test data consistency across pages
     */
    public function test_pagination_consistency(): void
    {
        // Get first page
        $firstPage = $this->apiCall('GET', '/api/1.1/processes/variables?processIds=1&page=1&per_page=5')
            ->json();

        // Get second page
        $secondPage = $this->apiCall('GET', '/api/1.1/processes/variables?processIds=1&page=2&per_page=5')
            ->json();

        // Ensure no duplicate IDs between pages
        $firstPageIds = collect($firstPage['data'])->pluck('id');
        $secondPageIds = collect($secondPage['data'])->pluck('id');

        $this->assertEquals(0, $firstPageIds->intersect($secondPageIds)->count());
    }

    /**
     * Test that process IDs filter works correctly
     */
    public function test_process_ids_filtering(): void
    {
        $response = $this->apiCall('GET', '/api/1.1/processes/variables?processIds=1,2&per_page=50');

        $responseData = $response->json();

        // Check that only requested process IDs are returned
        $uniqueProcessIds = collect($responseData['data'])
            ->pluck('process_id')
            ->unique()
            ->values()
            ->all();

        $this->assertEquals([1, 2], $uniqueProcessIds);

        // Since we generate 10 variables per process, total should be 20
        $this->assertEquals(20, $responseData['meta']['total']);
    }

    /**
     * Test filtering with savedSearchId parameter
     */
    public function test_saved_search_id_filtering(): void
    {
        // Create a saved search with specific columns
        $savedSearch = SavedSearch::factory()->create([
            'meta' => [
                'columns' => [
                    [
                        'label' => 'Variable 1',
                        'field' => 'data.var_1_1',
                        'default' => null,
                    ],
                    [
                        'label' => 'Variable 2',
                        'field' => 'data.var_1_2',
                        'default' => null,
                    ],
                ],
            ],
        ]);

        // Make request with savedSearchId
        $response = $this->apiCall('GET', '/api/1.1/processes/variables?processIds=1&savedSearchId=' . $savedSearch->id);

        $responseData = $response->json();

        // Check that the filtered variables do not include the fields from the saved search
        $filteredFields = collect($responseData['data'])->pluck('field');

        $this->assertFalse($filteredFields->contains('data.var_1_1'));
        $this->assertFalse($filteredFields->contains('data.var_1_2'));

        // Check that the total count is reduced by the number of excluded fields
        $this->assertEquals(8, $responseData['meta']['total']); // 10 total - 2 excluded
    }
}
