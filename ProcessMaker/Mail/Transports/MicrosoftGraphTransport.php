<?php

namespace ProcessMaker\Mail\Transports;

use GuzzleHttp\Client;
use ProcessMaker\Mail\MicrosoftGraphMessageConverter;
use ProcessMaker\Mail\MicrosoftGraphTokenProvider;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;

class MicrosoftGraphTransport extends AbstractTransport
{
    public function __construct(
        private MicrosoftGraphTokenProvider $tokenProvider,
        private string $senderEmail,
        private ?Client $client = null
    ) {
        parent::__construct();

        $this->client = $client ?? new Client([
            'base_uri' => 'https://graph.microsoft.com/v1.0/',
        ]);
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $payload = MicrosoftGraphMessageConverter::toSendMailPayload($email);
        $token = $this->tokenProvider->getAccessToken();

        $response = $this->client->post('users/' . rawurlencode($this->senderEmail) . '/sendMail', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
            'http_errors' => false,
        ]);

        if ($response->getStatusCode() >= 400) {
            throw new TransportException(
                'Microsoft Graph send failed: ' . $response->getBody()->getContents()
            );
        }
    }

    public function __toString(): string
    {
        return 'microsoft-graph';
    }
}
