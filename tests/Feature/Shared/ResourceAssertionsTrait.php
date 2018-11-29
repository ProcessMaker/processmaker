<?php
namespace Tests\Feature\Shared;

use Illuminate\Foundation\Testing\TestResponse;

/**
 * This trait add assertions to test a Resource Controller
 *
 */
trait ResourceAssertionsTrait
{

    protected static $DO_NOT_SEND = 'DO_NOT_SEND';

    protected $errorStructure = [
        'message',
        'errors'
    ];

    /**
     * Verify the list returned by the index API.
     *
     * @param type $query
     * @param type $expectedMeta
     *
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    protected function assertCorrectModelListing($query, $expectedMeta = [])
    {
        $route = route('api.' . $this->resource . '.index');
        $response = $this->apiCall('GET', $route . $query);
        //Verify the status
        $this->assertStatus(200, $response);
        //Verify the structure
        $response->assertJsonStructure(['data' => ['*' => $this->structure]]);
        $data = $response->json('data');
        $meta = $response->json('meta');
        // Verify the meta values
        $this->assertArraySubset($expectedMeta, $meta);
        //Verify the data size
        $this->assertCount($meta['count'], $data);
        return $response;
    }

    protected function assertModelSorting($query, $expectedFirstRow)
    {
        $data = $this->assertCorrectModelListing($query)
            ->json('data');
        $firstRow = $this->getDataAttributes($data[0]);
        $this->assertArraySubset($expectedFirstRow, $firstRow);
    }

    /**
     * Verify the creation of a model using valid attributes.
     *
     * @param string $modelClass
     * @param array $attributes
     *
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    protected function assertCorrectModelCreation($modelClass, array $attributes = [])
    {
        $route = route('api.' . $this->resource . '.store');
        $base = factory($modelClass)->make($attributes);
        $array = array_diff($base->toArray(), [static::$DO_NOT_SEND]);
        $response = $this->apiCall('POST', $route, $array);
        $this->assertStatus(201, $response);
        $response->assertJsonStructure($this->structure);
        $data = $response->json();
        $this->assertArraySubset($array, $data);
        return $response;
    }

    /**
     * Verify the creation of a model fails using invalid attributes.
     *
     * @param string $modelClass
     * @param array $attributes
     * @param array $errors
     *
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    protected function assertModelCreationFails($modelClass, array $attributes = [], array $errors = [])
    {
        $route = route('api.' . $this->resource . '.store');
        $base = factory($modelClass)->make($attributes);
        $array = array_diff($base->toArray(), [static::$DO_NOT_SEND]);
        $response = $this->apiCall('POST', $route, $array);
        $response->assertStatus(422);
        $response->assertJsonStructure($this->errorStructure);
        $response->assertJsonStructure(['errors' => $errors]);
        return $response;
    }

    /**
     * Verify model update.
     *
     * @param string $id
     * @param array $includes
     *
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    protected function assertModelShow($id, array $includes = [])
    {
        $route = route('api.' . $this->resource . '.show', [$id]);
        $structure = $this->structure;
        if ($includes) {
            $structure = array_merge($structure, $includes);
            $route .= '?include=' . implode(',', $includes);
        }
        $response = $this->apiCall('GET', $route);
        $this->assertStatus(200, $response);
        $response->assertJsonStructure($structure);
        return $response;
    }

    /**
     * Verify the deletion of a model.
     *
     * @param type $id
     *
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    protected function assertCorrectModelDeletion($id)
    {
        $route = route('api.'. $this->resource . '.destroy', [$id]);
        $response = $this->apiCall('DELETE', $route);
        $response->assertStatus(204);
        $this->assertEmpty($response->getContent());
        return $response;
    }

    /**
     * Verify the deletion of a model.
     *
     * @param type $id
     *
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    protected function assertModelDeletionFails($id, array $errors = [])
    {
        $route = route('api.' . $this->resource . '.destroy', [$id]);
        $response = $this->apiCall('DELETE', $route);
        $response->assertStatus(422);
        $response->assertJsonStructure($this->errorStructure);
        $response->assertJsonStructure(['errors' => $errors]);
        return $response;
    }

    /**
     * Verify update of a model using valid attributes
     *
     * @param $modelClass
     * @param array $attributes
     */
    protected function assertModelUpdate($modelClass, array $attributes = [])
    {

        $yesterday = \Carbon\Carbon::now()->subDay();
        $base = factory($modelClass)->create([
            "created_at" => $yesterday,
        ]);
        $original_attributes = $base->getAttributes();

        $route = route('api.' . $this->resource . '.update', [$base->id]);
        $fields = array_diff($attributes, [static::$DO_NOT_SEND]);
        $response = $this->apiCall('PUT', $route, $fields);
        //validate status
        $this->assertStatus(200, $response);
        $response->assertJsonStructure($this->structure);
        $this->assertArraySubset($fields, $response->json());
        
        // assert it creates a script version
        $base->refresh();
        $version = $base->versions()->first();
        $this->assertEquals($version->process_category_id, $original_attributes['process_category_id']);
        $this->assertEquals($version->user_id, $original_attributes['user_id']);
        $this->assertEquals($version->name, $original_attributes['name']);
        $this->assertEquals($version->description, $original_attributes['description']);
        $this->assertEquals((string) $version->created_at, (string) $yesterday);
        $this->assertEquals((string) $version->updated_at, (string) $base->updated_at);
    }

    /**
     * Verify update of a model using invalid attributes
     *
     * @param $modelClass
     * @param array $attributes
     * @param array $errors
     */
    protected function assertModelUpdateFails($modelClass, array $attributes = [], array $errors = [])
    {

        $base = factory($modelClass)->create();

        $route = route('api.' . $this->resource . '.update', [$base->id]);
        $fields = array_diff($attributes, [static::$DO_NOT_SEND]);
        $response = $this->apiCall('PUT', $route, $fields);
        //validate status
        $response->assertStatus(422);
        $response->assertJsonStructure($this->errorStructure);
        $response->assertJsonStructure(['errors' => $errors]);
        return $response;
    }

    /**
     * Return true is the structure has a JSON API format.
     *
     * @return bool
     */
    private function isJsonApi()
    {
        return isset($this->structure['attributes']);
    }

    /**
     * Get the attributes of the response.
     *
     * @param type $row
     *
     * @return array
     */
    private function getDataAttributes($row)
    {
        return $this->isJsonApi() ? $row['attributes'] : $row;
    }

    /**
     * Assert that the response has the given status code.
     *
     * @param string $expected
     * @param \Illuminate\Foundation\Testing\TestResponse $response
     */
    protected function assertStatus($expected, TestResponse $response)
    {
        $status = $response->getStatusCode();
        $this->assertEquals(
            $expected, $status, "Expected status code {$expected} but received {$status}.\n"
            . $response->getContent()
        );
    }
}
