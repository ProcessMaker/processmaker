<?php

namespace Tests\Unit\Mail;

use ProcessMaker\Mail\TaskActionByEmail;
use Tests\TestCase;

class TaskActionByEmailLinkRewriteTest extends TestCase
{
    public function testAppendsSignedParamsOnlyToAbeUpdateVariableLinks(): void
    {
        $mailer = new TaskActionByEmail();
        $html = <<<'HTML'
<a href="https://pm.test/tasks/update_variable/abc-123?varName=res&varValue=yes">Approve</a>
<a href="https://pm.test/tasks/123">Open task</a>
HTML;
        $signed = 'abe_email=a%40b.com&abe_user_id=1&abe_groups=&abe_sig=deadbeef';

        $result = $mailer->appendSignedParamsToAbeLinks($html, $signed);

        $this->assertStringContainsString(
            'https://pm.test/tasks/update_variable/abc-123?varName=res&varValue=yes&' . $signed,
            $result
        );
        $this->assertStringContainsString('href="https://pm.test/tasks/123"', $result);
        $this->assertSame(1, substr_count($result, 'abe_sig='));
    }

    public function testDoesNotDoubleAppendSignature(): void
    {
        $mailer = new TaskActionByEmail();
        $html = '<a href="https://pm.test/tasks/update_variable/abc?varName=x&varValue=1&abe_sig=already">Go</a>';

        $result = $mailer->appendSignedParamsToAbeLinks($html, 'abe_sig=new');

        $this->assertSame($html, $result);
    }
}
