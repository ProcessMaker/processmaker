<?php

namespace Tests\Unit\Mail;

use Mockery;
use ProcessMaker\Mail\TaskActionByEmail;
use ProcessMaker\Models\Screen;
use ProcessMaker\Packages\Connectors\ActionsByEmail\EmailProvider;
use Tests\TestCase;

class TaskActionByEmailSharedRenderTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Personalized ABE batches must render the email screen once, then rewrite links per recipient.
     */
    public function testRendersEmailScreenOnceForMultiplePersonalizedRecipients(): void
    {
        $screen = Screen::factory()->create([
            'config' => [['items' => []]],
        ]);

        $body = '<a href="https://pm.test/tasks/update_variable/abc?varName=res&varValue=yes">Approve</a>';
        $provider = Mockery::mock(EmailProvider::class);
        $provider->shouldReceive('screenRenderer')
            ->once()
            ->andReturn(['body' => $body, 'styles' => '/* shared */']);

        $dispatched = [];
        $provider->shouldReceive('sendAsync')
            ->twice()
            ->andReturnUsing(function (array $emailConfig, int $delaySeconds = 0) use (&$dispatched) {
                $dispatched[] = [
                    'to' => $emailConfig['email'] ?? null,
                    'body' => $emailConfig['body'] ?? '',
                    'styles' => $emailConfig['styles'] ?? null,
                    'delay' => $delaySeconds,
                ];
            });

        $mailer = new TaskActionByEmail($provider);
        $config = [
            'subject' => 'ABE {{token_abe}}',
            'emailServer' => 0,
            'screenEmailRef' => $screen->id,
        ];
        $data = ['token_abe' => 'tok-1'];

        $mailer->sendAbeEmail($config, 'a@example.com', $data, 'abe_sig=one', 0);
        $mailer->sendAbeEmail($config, 'b@example.com', $data, 'abe_sig=two', 3);

        $this->assertCount(2, $dispatched);
        $this->assertSame('a@example.com', $dispatched[0]['to']);
        $this->assertSame('b@example.com', $dispatched[1]['to']);
        $this->assertSame(0, $dispatched[0]['delay']);
        $this->assertSame(3, $dispatched[1]['delay']);
        $this->assertSame('/* shared */', $dispatched[0]['styles']);
        $this->assertSame('/* shared */', $dispatched[1]['styles']);
        $this->assertStringContainsString('abe_sig=one', $dispatched[0]['body']);
        $this->assertStringContainsString('abe_sig=two', $dispatched[1]['body']);
        $this->assertStringNotContainsString('abe_sig=two', $dispatched[0]['body']);
        $this->assertStringNotContainsString('abe_sig=one', $dispatched[1]['body']);
    }
}
