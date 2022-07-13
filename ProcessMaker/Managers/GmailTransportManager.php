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
          "access_token" => $config['access_token'],
          "expires_in" => $config['expires_in'],
          "refresh_token" => $config['refresh_token'],
          "scope" => $config['scope'],
          "token_type" => $config['token_type'],
          "created" => $config['created'],
        ];
    }
    
    public function send(Swift_Mime_SimpleMessage $mime, &$failedRecipients = null)
    {
        $client = new \Google\Client();
        try {
            $client->setAccessToken($this->token);
            
            // TODO::TEST IF REFRESH TOKEN IS WORKING PROPERLY
            // if ($client->isAccessTokenExpired()) {
            //     // save refresh token to some variable
            //     $refreshTokenSaved = $client->getRefreshToken();
            //     dd($refreshTokenSaved);
            //     // update access token
            //     $client->fetchAccessTokenWithRefreshToken($refreshTokenSaved);
            //     // pass access token to some variable
            //     $accessTokenUpdated = $client->getAccessToken();

            //     //Set the new acces token
            //     $accessToken = $refreshTokenSaved;
            //     $client->setAccessToken($accessToken);
            //     // dd($accessTokenUpdated);
            //     //UPDATE CONFIG WITH NEW ACCESS TOKEN
            //     EmailController::setEnvVar("EMAIL_CONNECTOR_GMAIL_API_ACCESS_TOKEN_2", $accessToken['access_token']);
            //     // dd($client->isAccessTokenExpired());
            // }
            
        
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

