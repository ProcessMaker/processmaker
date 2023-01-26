<?php

namespace Tests\Feature\ImportExport;

use Database\Seeders\SignalSeeder;
use ProcessMaker\ImportExport\ExportEncrypted;
use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Managers\SignalManager;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\SignalData;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ExportEncryptedTest extends TestCase
{
    use RequestHelper;

    public function testExportEncrypted()
    {
        $password = '3KctomfPgE';
        $export = [
            'type' => 'process_package',
            'version' => 2,
            'export' => [
                'processes' => ['foo'],
                'screens' => [],
                'scripts' => [],
            ],
        ];

        $encrypter = new ExportEncrypted($password);
        $exportEncrypted = $encrypter->call($export);

        $this->assertArrayHasKey('encrypted', $exportEncrypted);
        $this->assertIsString($exportEncrypted['export']);

        $encrypter = new ExportEncrypted($password);
        $exportEncrypted = $encrypter->decrypt($exportEncrypted);

        // make sure the payload has an empty processes array
        $this->assertEquals(['foo'], $exportEncrypted['export']['processes']);
    }

    public function testExportSensitiveAssetWithNoPassword()
    {
        // Add global signal
        ProcessCategory::factory()->create(['is_system' => true]);
        (new SignalSeeder())->run();
        $globalSignal = new SignalData('test_global', 'test_global', '');
        SignalManager::addSignal($globalSignal);

        // Create process with a script containing a environment variable
        $bpmn = file_get_contents(__DIR__ . '/fixtures/process-with-task-script.bpmn.xml');
        $process = Process::factory()->create([
            'name' => 'process test',
            'status' => 'ACTIVE',
            'bpmn' => $bpmn,
        ]);
        $environmentVariable = EnvironmentVariable::factory()->create(['name' => 'MY_VAR_1']);
        $scriptUser = User::factory()->create(['username' => 'scriptuser']);
        $script = Script::factory()->create([
            'title' => 'test',
            'code' => '<?php $var1 = getenv(\'MY_VAR_1\'); return [];',
            'run_as_user_id' => $scriptUser->id,
        ]);

        // Assign script to process
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:scriptTask', 'pm:scriptRef', $script->id);
        $process->save();

        $url = route('api.export.manifest', ['type' => 'process', 'id' => $process->id]);
        $result = $this->apiCall('GET', $url);

        $options = [];
        foreach ($result->json()['export'] as $key => $asset) {
            $options[$key] = [
                'mode' => null,
            ];
        }

        $payload = [
            'options' => $options,
            'password' => '',
        ];

        $url = route('api.export.download', ['type' => 'process', 'id' => $process->id]);
        $response = $this->apiCall('POST', $url, $payload);
        $response->assertStatus(400);
        $this->assertEquals('Password protection required.', $response->json()['message']);
    }

    public function testExportSensitiveAssetWithPassword()
    {
        // Add global signal
        ProcessCategory::factory()->create(['is_system' => true]);
        (new SignalSeeder())->run();
        $globalSignal = new SignalData('test_global', 'test_global', '');
        SignalManager::addSignal($globalSignal);

        // Create process with a script containing a environment variable
        $bpmn = file_get_contents(__DIR__ . '/fixtures/process-with-task-script.bpmn.xml');
        $process = Process::factory()->create([
            'name' => 'process test',
            'status' => 'ACTIVE',
            'bpmn' => $bpmn,
        ]);
        $environmentVariable = EnvironmentVariable::factory()->create(['name' => 'MY_VAR_1']);
        $scriptUser = User::factory()->create(['username' => 'scriptuser']);
        $script = Script::factory()->create([
            'title' => 'test',
            'code' => '<?php $var1 = getenv(\'MY_VAR_1\'); return [];',
            'run_as_user_id' => $scriptUser->id,
        ]);

        // Assign script to process
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:scriptTask', 'pm:scriptRef', $script->id);
        $process->save();

        $url = route('api.export.manifest', ['type' => 'process', 'id' => $process->id]);
        $result = $this->apiCall('GET', $url);

        $options = [];
        foreach ($result->json()['export'] as $key => $asset) {
            $options[$key] = [
                'mode' => null,
            ];
        }

        $payload = [
            'options' => $options,
            'password' => '123',
        ];

        $url = route('api.export.download', ['type' => 'process', 'id' => $process->id]);
        $response = $this->apiCall('POST', $url, $payload);
        $response->assertStatus(200);
    }
}
