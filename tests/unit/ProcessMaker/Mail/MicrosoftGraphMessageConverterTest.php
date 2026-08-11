<?php

namespace Tests\Unit\ProcessMaker\Mail;

use ProcessMaker\Mail\MicrosoftGraphMessageConverter;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class MicrosoftGraphMessageConverterTest extends TestCase
{
    public function testToSendMailPayloadIncludesFileAttachments()
    {
        $email = (new Email())
            ->to('recipient@example.com')
            ->subject('With attachment')
            ->html('<p>Hello</p>')
            ->attach('file-contents', 'report.txt', 'text/plain');

        $payload = MicrosoftGraphMessageConverter::toSendMailPayload($email);

        $this->assertSame('With attachment', $payload['message']['subject']);
        $this->assertSame('HTML', $payload['message']['body']['contentType']);
        $this->assertCount(1, $payload['message']['attachments']);
        $this->assertSame([
            '@odata.type' => '#microsoft.graph.fileAttachment',
            'name' => 'report.txt',
            'contentType' => 'text/plain',
            'contentBytes' => base64_encode('file-contents'),
        ], $payload['message']['attachments'][0]);
    }

    public function testToSendMailPayloadOmitsAttachmentsKeyWhenNonePresent()
    {
        $email = (new Email())
            ->to('recipient@example.com')
            ->subject('No attachment')
            ->text('Hello');

        $payload = MicrosoftGraphMessageConverter::toSendMailPayload($email);

        $this->assertArrayNotHasKey('attachments', $payload['message']);
    }
}
