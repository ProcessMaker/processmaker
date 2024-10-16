<?php

namespace Tests\Feature\Api;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Jobs\ImportV2;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use ProcessMaker\Package\Translations\Models\Translatable;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class TranslationTest extends TestCase
{
    use RequestHelper;

    public function testTranslationWithDefaultLanguage()
    {
        $defaultAdminUser = User::factory()->create(['is_administrator' => true]);
        $portugueseUser = User::factory()->create(['is_administrator' => true, 'language' => 'pt']);
        $spanishUser = User::factory()->create(['is_administrator' => true, 'language' => 'es']);

        $this->user = $defaultAdminUser;

        $fileName = __DIR__ . '/../../Fixtures/translation_test.json';
        $file = new UploadedFile($fileName, 'translation_test.json', null, null, true);
        Storage::putFileAs('import', $file, 'payload.json');
        Storage::put('import/options.json', '{}');
        $hash = md5_file(Storage::path(ImportV2::FILE_PATH));
        ImportV2::dispatchSync($this->user->id, null, $hash, false);

        $process = Process::orderBy('id', 'desc')->first();

        WorkflowManager::triggerStartEvent(
            $process,
            $process->getDefinitions()->getEvent('node_1'),
            []
        );

        $task = ProcessRequestToken::orderBy('id', 'desc')->first();

        // The first task should be created
        $this->assertNotEmpty($task);

        $url = route('api.tasks.show', [$task->id]);
        $response = $this->apiCall('GET', "$url?include=screen");
        $response->assertStatus(200);

        // Add a translation for the screen en
        $screen = $response->json()['screen'];

        $translatable = new Translatable();
        $translatable->translatable_id = $screen['screen_id'];
        $translatable->translatable_type = Screen::class;
        $translatable->language_code = 'en';
        $translatable->translations = [
            'First Name' => 'First Name',
            'Last Name' => 'Last Name',
            'Age' => 'Age',
        ];
        $translatable->save();

        // Add a translation for the screen pt
        $screen = $response->json()['screen'];

        $translatable = new Translatable();
        $translatable->translatable_id = $screen['screen_id'];
        $translatable->translatable_type = Screen::class;
        $translatable->language_code = 'pt';
        $translatable->translations = [
            'First Name' => 'First Name',
            'Last Name' => 'Last Name',
            'Age' => 'Age',
        ];
        $translatable->save();

        // Add a translation for the screen es
        $translatable = new Translatable();
        $translatable->translatable_id = $screen['screen_id'];
        $translatable->translatable_type = Screen::class;
        $translatable->language_code = 'es';
        $translatable->translations = [
            'First Name' => 'Nombre',
            'Last Name' => 'Apellido',
            'Age' => 'Edad',
        ];
        $translatable->save();

        $responseData = $response->json();
        $items = collect($responseData['screen']['config'][0]['items']);
        $firstNameItem = $items->firstWhere('config.name', 'first_name');
        $lastNameItem = $items->firstWhere('config.name', 'last_name');
        $ageItem = $items->firstWhere('config.name', 'age');

        $this->assertEquals('First Name', $firstNameItem['config']['label']);
        $this->assertEquals('Last Name', $lastNameItem['config']['label']);
        $this->assertEquals('Age', $ageItem['config']['label']);

        // As portuguese is not translated, english must be used
        $this->user = $portugueseUser;
        $response = $this->apiCall('GET', "$url?include=screen");
        $responseData = $response->json();
        $items = collect($responseData['screen']['config'][0]['items']);
        $firstNameItem = $items->firstWhere('config.name', 'first_name');
        $lastNameItem = $items->firstWhere('config.name', 'last_name');
        $ageItem = $items->firstWhere('config.name', 'age');

        $this->assertEquals('First Name', $firstNameItem['config']['label']);
        $this->assertEquals('Last Name', $lastNameItem['config']['label']);
        $this->assertEquals('Age', $ageItem['config']['label']);

        // Spanish has translation, so it should be used for the screen:
        $this->user = $spanishUser;
        $response = $this->apiCall('GET', "$url?include=screen");
        $responseData = $response->json();
        $items = collect($responseData['screen']['config'][0]['items']);
        $firstNameItem = $items->firstWhere('config.name', 'first_name');
        $lastNameItem = $items->firstWhere('config.name', 'last_name');
        $ageItem = $items->firstWhere('config.name', 'age');

        $this->assertEquals('Nombre', $firstNameItem['config']['label']);
        $this->assertEquals('Apellido', $lastNameItem['config']['label']);
        $this->assertEquals('Edad', $ageItem['config']['label']);
    }

    public function testTranslationWithLanguageThatDoesNotHaveTranslation()
    {
        $defaultAdminUser = User::factory()->create(['is_administrator' => true]);
        $portugueseUser = User::factory()->create(['is_administrator' => true, 'language' => 'pt']);

        $this->user = $defaultAdminUser;

        $fileName = __DIR__ . '/../../Fixtures/translation_test.json';
        $file = new UploadedFile($fileName, 'translation_test.json', null, null, true);
        Storage::putFileAs('import', $file, 'payload.json');
        Storage::put('import/options.json', '{}');
        $hash = md5_file(Storage::path(ImportV2::FILE_PATH));
        ImportV2::dispatchSync($this->user->id, null, $hash, false);

        $process = Process::orderBy('id', 'desc')->first();

        WorkflowManager::triggerStartEvent(
            $process,
            $process->getDefinitions()->getEvent('node_1'),
            []
        );

        $task = ProcessRequestToken::orderBy('id', 'desc')->first();

        $this->user = $portugueseUser;
        $url = route('api.tasks.show', [$task->id]);
        $response = $this->apiCall('GET', "$url?include=screen");
        $response->assertStatus(200);

        // Add a translation for the screen en
        $screen = $response->json()['screen'];

        $translatable = new Translatable();
        $translatable->translatable_id = $screen['screen_id'];
        $translatable->translatable_type = Screen::class;
        $translatable->language_code = 'en';
        $translatable->translations = [
            'First Name' => 'First Name',
            'Last Name' => 'Last Name',
            'Age' => 'Age',
        ];
        $translatable->save();

        // As portuguese is not translated, english must be used
        $response = $this->apiCall('GET', "$url?include=screen");
        $responseData = $response->json();
        $items = collect($responseData['screen']['config'][0]['items']);
        $firstNameItem = $items->firstWhere('config.name', 'first_name');
        $lastNameItem = $items->firstWhere('config.name', 'last_name');
        $ageItem = $items->firstWhere('config.name', 'age');

        $this->assertEquals('First Name', $firstNameItem['config']['label']);
        $this->assertEquals('Last Name', $lastNameItem['config']['label']);
        $this->assertEquals('Age', $ageItem['config']['label']);
    }

    public function testTranslationWithLanguageThatHasTranslation()
    {
        $spanishUser = User::factory()->create(['is_administrator' => true, 'language' => 'es']);
        $this->user = User::factory()->create(['is_administrator' => true, 'language' => 'es']);

        $fileName = __DIR__ . '/../../Fixtures/translation_test.json';
        $file = new UploadedFile($fileName, 'translation_test.json', null, null, true);
        Storage::putFileAs('import', $file, 'payload.json');
        Storage::put('import/options.json', '{}');
        $hash = md5_file(Storage::path(ImportV2::FILE_PATH));
        ImportV2::dispatchSync($this->user->id, null, $hash, false);

        $process = Process::orderBy('id', 'desc')->first();

        WorkflowManager::triggerStartEvent(
            $process,
            $process->getDefinitions()->getEvent('node_1'),
            []
        );

        $newTask = ProcessRequestToken::orderBy('id', 'desc')->first();
        $url = route('api.tasks.show', [$newTask->id]);
        $response = $this->apiCall('GET', "$url?include=screen");

        $screen = $response->json()['screen'];

        $translatable = new Translatable();
        $translatable->translatable_id = $screen['screen_id'];
        $translatable->translatable_type = Screen::class;
        $translatable->language_code = 'es';
        $translatable->translations = [
            'First Name' => 'Nombre',
            'Last Name' => 'Apellido',
            'Age' => 'Edad',
        ];
        $translatable->save();

        // Spanish has translation, so it should be used for the screen:
        $this->user = $spanishUser;

        $response = $this->apiCall('GET', "$url?include=screen");
        $responseData = $response->json();
        $items = collect($responseData['screen']['config'][0]['items']);

        $firstNameItem = $items->firstWhere('config.name', 'first_name');
        $lastNameItem = $items->firstWhere('config.name', 'last_name');
        $ageItem = $items->firstWhere('config.name', 'age');

        $this->assertEquals('Nombre', $firstNameItem['config']['label']);
        $this->assertEquals('Apellido', $lastNameItem['config']['label']);
        $this->assertEquals('Edad', $ageItem['config']['label']);
    }
}
