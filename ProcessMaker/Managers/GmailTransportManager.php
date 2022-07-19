<?php 

namespace ProcessMaker\Managers;

use Swift_Mime_SimpleMessage;
use Illuminate\Mail\Transport\Transport;
use ProcessMaker\Models\EnvironmentVariable;

class GmailTransportManager extends Transport
{
    private $token = null;


    public function __construct($config)
    {
        $this->token = [
          "client_id" => $config['key'],
          "client_secret" => $config['secret'],
          "access_token" => $config['access_token'],
          "refresh_token" => $config['refresh_token'],
        ];
    }
    
    public function send(Swift_Mime_SimpleMessage $mime, &$failedRecipients = null)
    {
        $client = new \Google\Client();
        try {
            $authConfig = array(
                "web" => array(
                    'client_id' => $this->token['client_id'], 
                    'client_secret' => $this->token['client_secret']
                )
            );
            $client->setAuthConfig($authConfig);
            $client->setAccessToken($this->token);
            
        
            $gmailService = new \Google\Service\Gmail($client);
            $message = new \Google_Service_Gmail_Message();

            $mime = rtrim(strtr(base64_encode($mime), '+/', '-_'), '=');
            $message->setRaw($mime);
            $gmailService->users_messages->send('me', $message); 
        } catch (\Throwable $ex) {
            throw $ex;
        }
    }
}


