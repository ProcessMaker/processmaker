<?php

namespace ProcessMaker\Managers;

use Google\Client;
use Illuminate\Mail\MailManager;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Packages\Connectors\Email\EmailConfig;
use Swift_Mime_SimpleMessage;
use Swift_SmtpTransport as SmtpTransport;

class OauthMailManager extends MailManager
{
    protected $app;

    private $token = null;

    public function __construct($app)
    {
        $this->app = $app;
        $this->token = [
            'client_id' => $app->config->get('services.gmail.key'),
            'client_secret' => $app->config->get('services.gmail.secret'),
            'access_token' => $app->config->get('services.gmail.access_token'),
            'refresh_token' => $app->config->get('services.gmail.refresh_token'),
            'expires_in' => $app->config->get('services.gmail.expires_in'),
            'created' => $app->config->get('services.gmail.created'),
        ];
    }

    protected function createSmtpTransport($config)
    { 
        $transport = parent::createSmtpTransport($config);
        
        $authMethod = $config['auth_method'];

        switch ($authMethod) {
            case 'google':
                $serverIndex = $config['server_index'];
                $accessToken = $this->checkForExpiredAccessToken($serverIndex);
                $fromAddress = $config['from']['address'];
                // Update Authencation Mode
                $transport->setAuthMode('XOAUTH2')
                ->setUsername($fromAddress)
                ->setPassword($accessToken);
                break;
        }
        return $transport;
    }

    public function checkForExpiredAccessToken($index)
    {
        $index = $index ? "_{$index}" : '';

        $client = new Client();
        $authConfig = [
            'web' => [
                'client_id' => $this->token['client_id'],
                'client_secret' => $this->token['client_secret'],
            ],
        ];
        $client->setAuthConfig($authConfig);
        $client->setAccessToken($this->token);
        $accessToken = $this->token['access_token'];

        if ($client->isAccessTokenExpired()) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($this->token['refresh_token']);
            $client->setAccessToken($newToken['access_token']);
            $accessToken = $newToken['access_token'];

            $this->updateEnvVar("EMAIL_CONNECTOR_GMAIL_API_ACCESS_TOKEN{$index}", $accessToken);
            $this->updateEnvVar("EMAIL_CONNECTOR_GMAIL_API_REFRESH_TOKEN{$index}", $newToken['refresh_token']);
            $this->updateEnvVar("EMAIL_CONNECTOR_GMAIL_API_EXPIRES_IN{$index}", $newToken['expires_in']);
            $this->updateEnvVar("EMAIL_CONNECTOR_GMAIL_API_TOKEN_CREATED{$index}", $newToken['created']);
        }

        return $accessToken;
    }

    private function updateEnvVar($name, $value)
    {
        $env = EnvironmentVariable::updateOrCreate(
            [
                'name' => $name,
            ],
            [
                'description' => $name,
                'value' => $value,
            ]
        );
    }
}
