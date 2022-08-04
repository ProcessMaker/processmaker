<?php 

namespace ProcessMaker\Managers;

use Swift_Mime_SimpleMessage;
use Illuminate\Mail\TransportManager;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Packages\Connectors\Email\EmailConfig;
use Google\Client as GoogleClient;
use Microsoft\Graph\Graph;

class OauthTransportManager extends TransportManager
{
    protected $config = null;
    
    private $token = null;

    public function __construct($config)
    {   
        $this->config = (object) $config;
        $authMethod = EmailConfig::authentication_methods[$config->get('mail.auth_method')];
        switch ($authMethod) {
            case 'google':
                $this->token = [
                    "client_id" => $config->get('services.gmail.key'),
                    "client_secret" => $config->get('services.gmail.secret'),
                    "access_token" => $config->get('services.gmail.access_token'),
                    "refresh_token" => $config->get('services.gmail.refresh_token'),
                    "expires_in" => $config->get('services.gmail.expires_in'),
                    "created" => $config->get('services.gmail.created'),
                ];
                break;
            case 'office365':
                $this->token = [
                    "tenant_id" => $config->get('services.office365.tenant_id'),
                    "client_id" => $config->get('services.office365.key'),
                    "client_secret" => $config->get('services.office365.secret'),
                    "access_token" => $config->get('services.office365.access_token'),
                    "refresh_token" => $config->get('services.office365.refresh_token'),
                    "expires_in" => $config->get('services.office365.expires_in'),
                ];
                break;
            
            default:
                break;
        }
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
                    $accessToken = $this->checkForExpiredGoogleAccessToken($serverIndex);
                    $fromAddress = $this->config->get('mail.from.address');
                    // Update Authencation Mode
                    $transport->setAuthMode('XOAUTH2')
                    ->setUsername($fromAddress)
                    ->setPassword($accessToken);
                    break;
                case 'office365':
                    $serverIndex = $this->config->get('mail.server_index');
                    $accessToken = $this->checkForExpiredOffice365AccessToken($serverIndex);
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

    public function checkForExpiredGoogleAccessToken($index) 
    {
        $index = $index ?  "_{$index}" : '';

        $client = new GoogleClient();
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

            $this->updateEnvVar("EMAIL_CONNECTOR_GMAIL_API_ACCESS_TOKEN{$index}", $accessToken);
            $this->updateEnvVar("EMAIL_CONNECTOR_GMAIL_API_REFRESH_TOKEN{$index}", $newToken['refresh_token']);
            $this->updateEnvVar("EMAIL_CONNECTOR_GMAIL_API_EXPIRES_IN{$index}", $newToken['expires_in']);
            $this->updateEnvVar("EMAIL_CONNECTOR_GMAIL_API_TOKEN_CREATED{$index}", $newToken['created']);
        }
        
        return $accessToken;
    }

    public function checkForExpiredOffice365AccessToken($index) 
    {
        $now = new \DateTime();
        $now->format('Y-m-d H:i:s');
        $expireDate = gmdate('Y-m-d H:i:s', strtotime($this->token['expires_in']));
        if ($now->format('Y-m-d H:i:s') > $expireDate ) {
            dd('ACCESS TOKEN IS EXPIRED');
            // TODO: Handle expired access token
        }

        $accessToken = $this->token['access_token'];
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
