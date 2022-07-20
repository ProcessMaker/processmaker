<?php 

namespace ProcessMaker\Managers;

use Swift_Mime_SimpleMessage;
use Illuminate\Mail\Transport\Transport;
use Microsoft\Graph\Graph;

class Office365TransportManager extends Transport
{
    private $token = null;


    public function __construct($config)
    {
        $this->token = [
          "client_id" => $config['key'],
          "client_secret" => $config['secret'],
          "access_token" => $config['access_token'],
          "refresh_token" => $config['refresh_token'],
          "expires_in" => $config['expires_in'],
        ];
    }
    
    public function send(Swift_Mime_SimpleMessage $mime, &$failedRecipients = null)
    {
        // Check if the stored access token has expired.
        if ($this->token['expires_in']->date > new \DateTime()) {
            // TODO: Handle expired access token
            // We need to request and store a new access token.
            dd('its expired need to use refresh token');
        } 

        try {
            $accessToken = $this->token['access_token'];
            $graph = new Graph();
            $graph->setAccessToken($accessToken);

            // Create the message
            $subject = $mime->getSubject();
            $content = $mime->getBody();
            $recipients = $mime->getTo();
            $toRecipients = [];
            foreach ($recipients as $recipient => $item) {
                $obj = (object) array("emailAddress" => array("address" => $recipient));
                array_push($toRecipients, $obj);
            }
           
            $mailBody = array( 
                "message" => array(
                        "subject" => $subject,
                        "body" => array(
                            "contentType" => "html",
                            "content" => $content,
                        ),
                        "toRecipients" => $toRecipients
                )
            );

            $graph->createRequest('POST', '/me/sendMail')
            ->attachBody($mailBody)
            ->execute();
        } catch (\Throwable $error) {
            throw $error;
        }
    }
}


