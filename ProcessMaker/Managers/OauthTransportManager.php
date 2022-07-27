<?php 

namespace ProcessMaker\Managers;

use Swift_Mime_SimpleMessage;
use Illuminate\Mail\TransportManager;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Packages\Connectors\Email\EmailConfig;

class OauthTransportManager extends TransportManager
{
    protected $config = null;


    public function __construct($config)
    {   
        $this->config = (object) $config;
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
                    $fromAddress = $this->config->get('mail.from.address');
                    
                    $accessToken = EnvironmentVariable::select('value')->where('name', 'EMAIL_CONNECTOR_GMAIL_API_ACCESS_TOKEN_' . $serverIndex)->firstOrFail()->value;
                    $clientId = EnvironmentVariable::select('value')->where('name', 'EMAIL_CONNECTOR_GMAIL_API_CLIENT_ID_' . $serverIndex)->firstOrFail()->value;
                    $clientSecret = EnvironmentVariable::select('value')->where('name', 'EMAIL_CONNECTOR_GMAIL_API_SECRET_' . $serverIndex)->firstOrFail()->value;
                    $refreshToken = EnvironmentVariable::select('value')->where('name', 'EMAIL_CONNECTOR_GMAIL_API_REFRESH_TOKEN_' . $serverIndex)->firstOrFail()->value;
                    
                    $token = [
                        "client_id" => $clientId,
                        "client_secret" => $clientSecret,
                        "access_token" => $accessToken,
                        "refresh_token" => $refreshToken
                    ];

                    // Check if access token has expired
                    $client = new \Google\Client();
                    $authConfig = array(
                        "web" => array(
                            'client_id' => $clientId, 
                            'client_secret' => $clientSecret
                        )
                    );
                    $client->setAuthConfig($authConfig);
                    $client->setAccessToken($token);
                    
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
}

