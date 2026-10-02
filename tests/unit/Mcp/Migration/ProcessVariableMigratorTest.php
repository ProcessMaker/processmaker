<?php

namespace Tests\unit\Mcp\Migration;

use ProcessMaker\Mcp\Migration\ProcessVariableMigrator;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use Tests\TestCase;

class ProcessVariableMigratorTest extends TestCase
{
    public function testApplyStoresPm4VariablesAndDefaults(): void
    {
        $user = User::factory()->create();
        $process = Process::factory()->create(['user_id' => $user->id]);

        $result = (new ProcessVariableMigrator())->apply($process, [[
            'var_name' => 'invoice_total',
            'var_label' => 'Invoice Total',
            'var_field_type' => 'FLOAT',
            'var_default' => '10.5',
        ]]);

        $process->refresh();

        $this->assertSame(['invoice_total'], $result['applied']);
        $this->assertSame('invoice_total', $process->properties['variables'][0]['name']);
        $this->assertSame('float', $process->properties['variables'][0]['type']);
        $this->assertSame(10.5, $process->properties['request_data_defaults']['invoice_total']);
    }
}
