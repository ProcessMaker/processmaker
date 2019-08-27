<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Shared\RequestHelper;

class DataSourceApiTest extends TestCase
{

    use RequestHelper;

    const API_URL = '/datasources';

    const STRUCTURE = [
        'name',
        'description',
        'endpoints',
        'mappings',
        'authtype',
        'credentials',
        'status',
        'data_source_category_id',
        'created_at',
        'updated_at',
    ];    

    /**
     * Basic test to get a list of sources
     *
     * @return void
     */
    public function testAuthGetAllSources()
    {

        $response = $this->get('/api/1.0'.self::API_URL);

        $response->assertUnauthorized();        

    }

    public function testGetAllSources()
    {
        // Basic listing assertions
        $response = $this->apiCall('GET', self::API_URL);

        // Validate the header status code
        $response->assertSuccessful();
    }

}
