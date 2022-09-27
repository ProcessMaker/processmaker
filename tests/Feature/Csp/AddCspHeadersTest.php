<?php

namespace ProcessMaker;

use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class AddCspHeadersTest extends TestCase
{
    use RequestHelper;

    public function testWillOutputCspHeadersWithDefaultConfig()
    {
        $defaultPolicy = "script-src * 'unsafe-inline' 'unsafe-eval'; object-src 'self' blob: data:;";
        $string = $this->metaTagString('Content-Security-Policy', $defaultPolicy);

        $response = $this->webCall('GET', route('test.csp'));

        $this->assertStringContainsString($string, $response->getContent());
    }

    private function metaTagString(string $headerName = 'Content-Security-Policy', $content = '.*')
    {
        return "<meta http-equiv=\"{$headerName}\" content=\"{$content}\">";
    }
}
