<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\User;
use Spatie\BinaryUuid\HasBinaryUuid;
use Tests\TestCase;
use Tests\Feature\Shared\ApiCallWithUser;
use Illuminate\Support\Facades\Hash;

class FilesTest extends TestCase
{

  use DatabaseTransactions;
  use ApiCallWithUser;

  const API_TEST_URL = '/api/1.0/files';
  const DEFAULT_PASS = 'password';

  const STRUCTURE = [
      'uuid',
      'model_id',
      'model_type',
      'collection_name',
      'name',
      'file_name',
      'mime_type',
      'disk',
      'size',
      'manipulations',
      'custom_properties',
      'responsive_images',
      'order_column',
      'created_at',
      'updated_at',
  ];

  /**
   * Create user
   */
  protected function setUp()
  {
      parent::setUp();
      $this->user = factory(User::class)->create([
          'password' => Hash::make(self::DEFAULT_PASS),
      ]);
  }

    /**
     * Get a list of Files
     *
     */
  public function testListFiles()
  {
      // We create a fake file to upload
      Storage::fake('public');
      $fileUpload = UploadedFile::fake()->create('my_test_file123.txt', 1);

      // We create a model (in this case a user) and associate to him the file
      $model = factory(User::class)->create();
      $model->addMedia($fileUpload)->toMediaCollection('local');


      // Basic listing assertions
      $response = $this->actingAs($this->user, 'api')->json('GET', self::API_TEST_URL);

      // Validate the header status code
      $response->assertStatus(200);

      // Verify structure
      $response->assertJsonStructure([
          'data' => ['*' => self::STRUCTURE],
          'meta',
      ]);

      // Filtered listing assertions
      $response = $this->actingAs($this->user, 'api')
                        ->json('GET', self::API_TEST_URL . '?filter=123');
      $response->assertStatus(200);
      $response->assertJsonStructure([
          'data' => ['*' => self::STRUCTURE],
          'meta',
      ]);

      // Filtered listing assertions when filter string is not found
      $response = $this->actingAs($this->user, 'api')
          ->json('GET', self::API_TEST_URL . '?filter=xyz9393');
      $response->assertStatus(200);
      $response->assertJsonStructure([
          'data' => [],
          'meta',
      ]);
  }

    /**
     * A file can be get successfully
     */
    public function testGetFile()
  {
      // We create a fake file to upload
      $testFileName = 'test.txt';
      Storage::fake('public');
      $fileUpload = UploadedFile::fake()->create($testFileName, 1);

      // We create a model (in this case a user) and associate to him the file
      $model = factory(User::class)->create();
      $addedMedia = $model->addMedia($fileUpload)->toMediaCollection('local');

      $response = $this->actingAs($this->user, 'api')
          ->json('GET', self::API_TEST_URL . '/' . $addedMedia->uuid_text);

      // Validate the header status code
      $response->assertStatus(200);

      // Verify that a file with the fake file is downloaded
      $this->assertEquals($testFileName, $response->getFile()->getFileName());
  }

    /**
     * Upload a file and associate it to a model
     */
  public function testCreateFile()
  {
      // We create a fake file to upload
      Storage::fake('public');
      $fileUpload = UploadedFile::fake()->create('test.txt', 1);

      // In the data array add the file to upload
      $data = [
          'file' => $fileUpload
      ];

      // We create a model (in this case a user) to whom the file will be associated
      $model = factory(User::class)->create();

      // Verify that if no model data is sent an error is returned
      $response = $this->actingAs($this->user, 'api')
          ->json('POST', self::API_TEST_URL, $data);
      $response->assertStatus(404);

      // Verify that if no model data is sent an error is returned
      $response = $this->actingAs($this->user, 'api')
          ->json('POST', self::API_TEST_URL . '?model=user&model_uuid=NonExistentUuid', $data);
      $response->assertStatus(404);

      $response = $this->actingAs($this->user, 'api')
          ->json('POST', self::API_TEST_URL . '?model=user&model_uuid=' . $model->uuid_text, $data);

      // Validate the header status code
      $response->assertStatus(200);

      // Validate that a file was created in the media directory
      $mediaObj = json_decode($response->getContent());
      Storage::disk('public')->assertExists($mediaObj->uuid . '/test.txt');
  }

    /**
     * Update a media with a new file
     */
    public function testUpdateFile()
  {
      // We create a fake file to upload
      Storage::fake('public');
      $fileUploadInsert = UploadedFile::fake()->create('insertedFile.txt', 1);
      $fileUploadUpdate= UploadedFile::fake()->create('updatedFile.txt', 2);

      // We create a model (in this case a user) to whom the file will be associated
      $model = factory(User::class)->create();
      $addedMedia = $model->addMedia($fileUploadInsert)->toMediaCollection('local');

      // In the data array add the file to update
      $data = [
          'file' => $fileUploadUpdate
      ];

      $response = $this->actingAs($this->user, 'api')
          ->json('PUT', self::API_TEST_URL . '/' . $addedMedia->uuid_text, $data);

      // Validate the header status code
      $response->assertStatus(201);

      // Validate that the file was updated in the directory of the inserted media
      Storage::disk('public')->assertExists($addedMedia->uuid_text . '/updatedFile.txt');

      // Validate that the media table has been updated
      $modelId = HasBinaryUuid::encodeUuid($addedMedia->uuid_text);
      $updatedMediaModel = Media::find($modelId);
      $this->assertEquals('updatedFile.txt', $updatedMediaModel->file_name);
      $this->assertEquals('updatedFile', $updatedMediaModel->name);
  }

    /**
     * Remove a file and its model associations
     */
  public function testDestroyFile()
  {
      // We create a fake file to upload
      Storage::fake('public');
      $fileUpload = UploadedFile::fake()->create('test.txt', 1);

      // We create a model (in this case a user) and associate to him the file
      $model = factory(User::class)->create();
      $addedMedia = $model->addMedia($fileUpload)->toMediaCollection('local');

      $response = $this->actingAs($this->user, 'api')
          ->json('DELETE', self::API_TEST_URL . '/' . $addedMedia->uuid_text);

      // Validate the header status code
      $response->assertStatus(204);

      // Validate that the media file has been removed from the user
      $this->assertEquals(0, $model->getMedia()->count());
  }
}
