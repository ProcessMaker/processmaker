<?php

namespace Tests\Feature\ImportExport;

use MJS\TopSort\CircularDependencyException;
use ProcessMaker\ImportExport\Dependent;
use ProcessMaker\ImportExport\Exporters\ScreenExporter;
use ProcessMaker\ImportExport\Manifest;
use Tests\TestCase;

class ManifestTest extends TestCase
{
    public function testOrderForImport()
    {
        $test = [
            'A' => $this->mockExporter(['C']),
            'B' => $this->mockExporter(['C', 'A']),
            'C' => $this->mockExporter(['D']),
            'D' => $this->mockExporter([]),
        ];

        $manifest = new Manifest();
        $manifest->set($test);
        $result = $manifest->orderForImport();
        $this->assertEquals(['D', 'C', 'A', 'B'], $result);
    }

    public function testOrderForImportCircularDependency()
    {
        $test = [
            'A' => $this->mockExporter(['C']),
            'B' => $this->mockExporter(['A']),
            'C' => $this->mockExporter(['B']),
        ];
        $manifest = new Manifest();
        $manifest->set($test);
        $this->expectException(CircularDependencyException::class);
        $manifest->orderForImport();
    }

    private function mockExporter($dependents)
    {
        return $this->mock(ScreenExporter::class, function ($mock) use ($dependents) {
            $dependentMocks = [];
            foreach ($dependents as $dependent) {
                $dependentMocks[] = $this->mock(Dependent::class, function ($dependentMock) use ($dependent) {
                    $dependentMock->uuid = $dependent;
                });
            }
            $mock->dependents = $dependentMocks;
        });
    }
}
