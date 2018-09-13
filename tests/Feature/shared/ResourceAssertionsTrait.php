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
     * Verify the creation of a model using valid attributes.
     *
     * @param string $modelClass
     * @param array $attributes
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
    }

    /**
     * Verify the creation of a model fails using invalid attributes.
     *
     * @param string $modelClass
     * @param array $attributes
     * @param array $errors
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
    }
}
