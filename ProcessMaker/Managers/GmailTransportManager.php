<?php 

namespace ProcessMaker\Managers;

use Swift_Mime_SimpleMessage;
use Illuminate\Mail\Transport\Transport;
use ProcessMaker\Models\EnvironmentVariable;

class GmailTransportManager extends Transport
{
    private $key = null;
    
    private $secret = null;

    private $redirect = null;


    public function __construct($config)
    {
        $this->key = $config['key'];
        $this->secret = $config['secret'];
        $this->redirect = $config['redirect_uri']; 
    }
    
    public function send(Swift_Mime_SimpleMessage $mime, &$failedRecipients = null)
    {
        $token = EnvironmentVariable::where('name', 'GMAIL_API_ACCESS_TOKEN')->first();
        $client = new \Google\Client();

        try {
            $client->setAccessToken($token->value);
        
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

