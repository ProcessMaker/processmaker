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
        $this->token = $config['access_token'];
    }
    
    public function send(Swift_Mime_SimpleMessage $mime, &$failedRecipients = null)
    {
        $client = new \Google\Client();
        try {
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

