<?php
namespace Tests\Feature\Shared;

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
        $route = route($this->resource . '.index');
        $response = $this->json('GET', $route . $query);
        //Verify the status
        $response->assertStatus(200);
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
        $route = route($this->resource . '.store');
        $base = factory($modelClass)->make($attributes);
        $array = array_diff($base->toArray(), [static::$DO_NOT_SEND]);
        $response = $this->json('POST', $route, $array);
        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => $this->structure]);
        $data = $response->json('data');
        $this->assertArraySubset($array, $data['attributes']);
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
        $route = route($this->resource . '.store');
        $base = factory($modelClass)->make($attributes);
        $array = array_diff($base->toArray(), [static::$DO_NOT_SEND]);
        $response = $this->json('POST', $route, $array);
        $response->assertStatus(422);
        $response->assertJsonStructure($this->errorStructure);
        $response->assertJsonStructure(['errors' => $errors]);
        return $response;
    }

    /**
     * Verify model update.
     *
     * @param string $modelClass
     * @param array $attributes
     *
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    protected function assertModelShow($modelClass, array $attributes = [])
    {
        $base = factory($modelClass)->create();
        //$base = factory($modelClass)->make($attributes);
        $route = route($this->resource . '.show', [$base->uuid_text]);
        $response = $this->json('GET', $route);
        $response->assertStatus(200);
        $response->assertJsonStructure(['data' =>  $this->structure]);
        return $response;
    }

    /**
     * Verify the deletion of a model.
     *
     * @param type $uuid
     *
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    protected function assertCorrectModelDeletion($uuid)
    {
        $route = route($this->resource . '.destroy', [$uuid]);
        $response = $this->json('DELETE', $route);
        $response->assertStatus(204);
        $this->assertEmpty($response->getContent());
        return $response;
    }

    /**
     * Verify the deletion of a model.
     *
     * @param type $uuid
     *
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    protected function assertModelDeletionFails($uuid, array $errors = [])
    {
        $route = route($this->resource . '.destroy', [$uuid]);
        $response = $this->json('DELETE', $route);
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

        $base = factory($modelClass)->create();

        $route = route($this->resource . '.update', [$base->uuid_text]);
        $fields = array_diff($attributes, [static::$DO_NOT_SEND]);
        $response = $this->json('PUT', $route, $fields);
        //validate status
        $response->assertStatus(204);

        $data = $modelClass::where('uuid', $base->uuid)->first()->toArray();

        foreach ($fields as $key => $value) {
            $this->assertEquals($value, $data[$key]);
        }
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

        $route = route($this->resource . '.update', [$base->uuid_text]);
        $fields = array_diff($attributes, [static::$DO_NOT_SEND]);
        $response = $this->json('PUT', $route, $fields);
        //validate status
        $response->assertStatus(422);
        $response->assertJsonStructure($this->errorStructure);
        $response->assertJsonStructure(['errors' => $errors]);

    }
}
