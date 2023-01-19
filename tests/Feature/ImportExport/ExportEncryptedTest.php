<?php

namespace Tests\Feature\ImportExport;

use ProcessMaker\ImportExport\ExportEncrypted;
use Tests\TestCase;

class ExportEncryptedTest extends TestCase
{
    public function testExportEncrypted()
    {
        $password = '3KctomfPgE';
        $export = [
            'type' => 'process_package',
            'version' => 2,
            'export' => [
                'processes' => [],
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
        $this->assertEquals([], $exportEncrypted['export']['processes']);
    }
}
