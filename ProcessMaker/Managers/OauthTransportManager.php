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
                    $accessToken = EnvironmentVariable::select('value')->where('name', 'EMAIL_CONNECTOR_GMAIL_API_ACCESS_TOKEN_' . $serverIndex)->firstOrFail()->value;
                    $fromAddress = $this->config->get('mail.from.address');
                    
                    // TODO:: Check if access token has expired
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

