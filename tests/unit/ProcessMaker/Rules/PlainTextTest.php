<?php

namespace Tests\Unit\ProcessMaker\Rules;

use Illuminate\Support\Facades\Validator;
use ProcessMaker\Rules\PlainText;
use Tests\TestCase;

class PlainTextTest extends TestCase
{
    public function testItRejectsHtmlMarkup(): void
    {
        $payloads = [
            '<<img>img src=x onerror=alert(document.domain)>',
            '<script>alert(document.domain)</script>',
            '<svg onload=alert(document.domain)>',
            '<iframe src=javascript:alert(document.domain)>',
            '<img src=x onerror=alert(document.domain)>',
            '<!-- comment -->',
            '<?php echo "unsafe"; ?>',
        ];

        foreach ($payloads as $payload) {
            $validator = Validator::make(['value' => $payload], ['value' => [new PlainText()]]);

            $this->assertTrue($validator->fails(), $payload);
        }
    }

    public function testItAcceptsPlainText(): void
    {
        $values = [
            'Éléazar Reséndez',
            'Research & Development',
            'VP < Sales',
            '&lt;img src=x&gt;',
            'javascript:alert(document.domain)',
        ];

        foreach ($values as $value) {
            $validator = Validator::make(['value' => $value], ['value' => [new PlainText()]]);

            $this->assertFalse($validator->fails(), $value);
        }
    }
}
