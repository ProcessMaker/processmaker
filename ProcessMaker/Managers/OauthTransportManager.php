<?php 

namespace ProcessMaker\Managers;

use Swift_Mime_SimpleMessage;
use Illuminate\Mail\TransportManager;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Packages\Connectors\Email\EmailConfig;

class OauthTransportManager extends TransportManager
{
    protected $config = null;
    
    private $token = null;


    public function __construct($config)
    {   
        $this->config = (object) $config;
        // TODO:: Can maybe pass in the server index here
        $this->token = [
            "client_id" => $config->get('services.gmail.key'),
            "client_secret" => $config->get('services.gmail.secret'),
            "access_token" => $config->get('services.gmail.access_token'),
            "refresh_token" => $config->get('services.gmail.refresh_token'),
            "expires_in" => $config->get('services.gmail.expires_in'),
        ];
    }
    
    protected function createSmtpDriver()
    {
        $transport = parent::createSmtpDriver();

        $authIndex = $this->config->get('mail.auth_method');
        if (isset($authIndex)) {
            $authMethod = EmailConfig::authentication_methods[$authIndex];
            switch ($authMethod) {
                case 'google':
                    $serverIndex = $this->config->get('mail.server_index');
                    $accessToken = $this->checkForExpiredAccessToken($serverIndex);
                    $fromAddress = $this->config->get('mail.from.address');
                    // Update Authencation Mode
                    $transport->setAuthMode('XOAUTH2')
                    ->setUsername($fromAddress)
                    ->setPassword($accessToken);
                    break;
                
                default:
                    # code...
                    break;
            }
        }
        return $transport;
    }

    public function checkForExpiredAccessToken($index) 
    {
        $client = new \Google\Client();
        $authConfig = array(
            "web" => array(
                'client_id' => $this->token['client_id'], 
                'client_secret' => $this->token['client_secret']
            )
        );
        $client->setAuthConfig($authConfig);
        $client->setAccessToken($this->token);
        $accessToken = $this->token['access_token'];

        if ($client->isAccessTokenExpired()) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($this->token['refresh_token']);
            $client->setAccessToken($newToken['access_token']);
            $accessToken = $newToken['access_token'];
            $refreshToken = $newToken['refresh_token'];
            $expiresIn = $newToken['expires_in'];

            $this->setEnvVar('EMAIL_CONNECTOR_GMAIL_API_ACCESS_TOKEN_' . $index, $accessToken);
            $this->setEnvVar('EMAIL_CONNECTOR_GMAIL_API_REFRESH_TOKEN_' . $index, $refreshToken);
            $this->setEnvVar('EMAIL_CONNECTOR_GMAIL_API_EXPIRES_IN_' . $index, $expiresIn);
        }
        
        return $accessToken;
    }

    private function setEnvVar($name, $value)
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

