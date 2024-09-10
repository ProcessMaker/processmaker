<?php

namespace Tests\Model;

use ProcessMaker\Console\Commands\BuildScriptExecutors;
use ProcessMaker\Models\ScriptExecutor;
use Tests\TestCase;

class BuildScriptExecutorsTest extends TestCase
{

    public function testBuildScriptExecutorsDockerfile()
    {
        $scriptExecutor = ScriptExecutor::factory()->create([
            'language' => 'php-nayra',
            'config' => 'RUN apt-get update && apt-get install -y libxml2-dev',
        ]);
        $builder = new BuildScriptExecutors();
        $code = $builder->getDockerfileContent($scriptExecutor);

        // Check the $code contains 'WORKDIR /opt/executor/src'
        $expectedCode = <<<EOF
        WORKDIR /opt/executor/src
        RUN apt-get update && apt-get install -y libxml2-dev
        WORKDIR /app
        EOF;
        $this->assertStringContainsString($expectedCode, $code);
    }
}
