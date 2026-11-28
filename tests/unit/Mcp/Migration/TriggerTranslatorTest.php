<?php

namespace Tests\unit\Mcp\Migration;

use PHPUnit\Framework\TestCase;
use ProcessMaker\Mcp\Migration\TriggerTranslator;

class TriggerTranslatorTest extends TestCase
{
    public function testTranslatesPm3VariablesAndApiCalls(): void
    {
        $translator = new TriggerTranslator();

        $result = $translator->translate('<?php @@approved = "YES"; PMFNewCase(');

        $this->assertStringContainsString("\$data['approved'] = \"YES\"", $result);
        $this->assertStringContainsString('$api->newCase(', $result);
    }
}
