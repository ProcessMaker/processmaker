<?php 

namespace ProcessMaker\Managers;

use DateTime;
use DateInterval;
use GuzzleHttp\Client;
use Microsoft\Graph\Graph;
use Swift_Mime_SimpleMessage;
use Google\Client as GoogleClient;
use Illuminate\Mail\TransportManager;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Packages\Connectors\Email\EmailConfig;

class OauthTransportManager extends TransportManager
{
    protected $config = null;
    
    private $token = null;

    private $authMethodIndex = null;

    private $authMethod = null;

    private $emailServerIndex = null;

    private $fromAddress = null;

    public function __construct($config)
    {   
        $this->config = (object) $config;
        $this->authMethodIndex = $config->get('mail.auth_method');
        $this->authMethod = EmailConfig::authentication_methods[$this->authMethodIndex];
        $this->emailServerIndex = $this->config->get('mail.server_index');
        $this->fromAddress = $this->config->get('mail.from.address');

        $this->setTokenVariable();
    }

    private function setTokenVariable() 
    {
        $this->token = (object)[];
        switch ($this->authMethod) {
            case 'google':
                $this->token->client_id = $this->config->get('services.gmail.key');
                $this->token->client_secret = $this->config->get('services.gmail.secret');
                $this->token->access_token = $this->config->get('services.gmail.access_token');
                $this->token->refresh_token = $this->config->get('services.gmail.refresh_token');
                $this->token->expires_in = $this->config->get('services.gmail.expires_in');
                break;
            case 'office365':
                $this->token->tenant_id = $this->config->get('services.office365.tenant_id');
                $this->token->client_id = $this->config->get('services.office365.key');
                $this->token->client_secret = $this->config->get('services.office365.secret');
                $this->token->access_token = $this->config->get('services.office365.access_token');
                $this->token->refresh_token = $this->config->get('services.office365.refresh_token');
                $this->token->expires_in = $this->config->get('services.office365.expires_in');
                break;
            default:
                break;
        }
    }
    
    protected function createSmtpDriver()
    {
        $transport = parent::createSmtpDriver();

        if (isset($this->authMethodIndex)) {
            switch ($this->authMethod) {
                case 'google':
                    $accessToken = $this->checkForExpiredGoogleAccessToken($this->emailServerIndex);
                    $fromAddress = $this->config->get('mail.from.address');
                    // Update Authencation Mode
                    $transport->setAuthMode('XOAUTH2')
                    ->setUsername($this->fromAddress)
                    ->setPassword($accessToken);
                    break;
                case 'office365':
                    $accessToken = $this->checkForExpiredOffice365AccessToken($this->emailServerIndex);
                    // Update Authencation Mode
                    $transport->setAuthMode('XOAUTH2')
                    ->setUsername($this->fromAddress)
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
            $this->updateEnvVar("EMAIL_CONNECTOR_GMAIL_API_ACCESS_TOKEN_EXPIRE_DATE{$index}", $newToken['expires_in']);
            $this->updateEnvVar("EMAIL_CONNECTOR_GMAIL_API_TOKEN_CREATED{$index}", $newToken['created']);
        }
        
        return $accessToken;
    }

    public function checkForExpiredOffice365AccessToken($index) 
    {
        $now = new DateTime();
        $now->format('Y-m-d H:i:s');
        $expireDate = gmdate('Y-m-d H:i:s', strtotime($this->token->expires_in));
        $accessToken = $this->token->access_token;

        if ($now->format('Y-m-d H:i:s') > $expireDate ) {
            $accessToken = $this->refreshAccessToken();
        }
        return $accessToken;
    }

    private function refreshAccessToken() 
    {
        try {
            $guzzle = new Client();
            $url = 'https://login.microsoftonline.com/' . $this->token->tenant_id . '/oauth2/v2.0/token';
            $newToken = json_decode($guzzle->post($url, [
                'form_params' => [
                    'client_id' =>  $this->token->client_id,
                    'client_secret' => $this->token->client_secret,
                    'scope' => 'https://outlook.office.com/IMAP.AccessAsUser.All https://outlook.office.com/POP.AccessAsUser.All https://outlook.office.com/SMTP.Send offline_access',
                    'refresh_token' => $this->token->refresh_token,
                    'grant_type' => 'refresh_token',
                ],
            ])->getBody()->getContents());
            
            $now = new DateTime();
            $expirationTime = $now->add(new DateInterval('PT' . $newToken->expires_in . 'S'));

            $this->updateEnvVar("EMAIL_CONNECTOR_OFFICE_365_ACCESS_TOKEN{$this->emailServerIndex}", $newToken->access_token);
            $this->updateEnvVar("EMAIL_CONNECTOR_OFFICE_365_REFRESH_TOKEN{$this->emailServerIndex}", $newToken->refresh_token);
            $this->updateEnvVar("EMAIL_CONNECTOR_OFFICE_365_ACCESS_TOKEN_EXPIRE_DATE{$this->emailServerIndex}", $expirationTime);
        return $newToken->access_token;

        } catch (Throwable $error) {
            throw $error;
        }   
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
