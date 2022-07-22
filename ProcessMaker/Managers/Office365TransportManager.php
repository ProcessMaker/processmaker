<?php 

namespace ProcessMaker\Managers;

use DateTime;
use Throwable;
use DateInterval;
use GuzzleHttp\Client;
use Microsoft\Graph\Graph;
use Swift_Mime_SimpleMessage;
use Illuminate\Mail\Transport\Transport;
use ProcessMaker\Packages\Connectors\Email\Controllers\EmailController;

class Office365TransportManager extends Transport
{
    private $accessToken = null;
    private $clientId = null;
    private $clientSecret = null;
    private $tenantId = null;
    private $refreshToken = null;
    private $expirationDate = null;


    public function __construct($config)
    {
        $this->accessToken = $config['access_token'];
        $this->clientId = $config['key'];
        $this->clientSecret = $config['secret'];
        $this->tenantId = $config['tenant_id'];
        $this->refreshToken = $config['refresh_token'];
        $this->expirationDate = $config['expires_in']->date;
    }
    
    public function send(Swift_Mime_SimpleMessage $mime, &$failedRecipients = null)
    {
        // Check if the stored access token has expired.
        $now = new DateTime();
        // dd($now, $this->expirationDate);
        if ($now > $this->expirationDate) {
            \Log::debug("!!!!!!!!!!!!!!!!!! ACCESS TOKEN HAS EXPIRED !!!!!!!!!!!!!!!!!", ["EXIPIRATION DATE" => $this->expirationDate, "NOW" => $now]);
            $this->refreshAccessToken($now);
        } 

        try {
            $graph = new Graph();
            $graph->setAccessToken($this->accessToken);

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
        } catch (Throwable $error) {
            throw $error;
        }
    }

    public function refreshAccessToken($now)
    {
        try {
            $guzzle = new Client();
            $url = 'https://login.microsoftonline.com/' . $this->tenantId . '/oauth2/v2.0/token';
            $token = json_decode($guzzle->post($url, [
                'form_params' => [
                    'client_id' =>  $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope' => 'https://graph.microsoft.com/Mail.Send https://graph.microsoft.com/SMTP.Send https://graph.microsoft.com/User.Read offline_access',
                    'refresh_token' => $this->refreshToken,
                    'grant_type' => 'refresh_token',
                ],
            ])->getBody()->getContents());
            dd($token);
            \Log::debug("!!!!!!!!!!!!!!!!!! REFRESH ACCESS TOKEN !!!!!!!!!!!!!!!!!", ["TOKEN" => $token]);
            $expirationTime = $now->add(new DateInterval('PT' . $token->expires_in . 'S'));

            // EmailController::setEnvVar("EMAIL_CONNECTOR_OFFICE_ACCESS_TOKEN{$index}", $token->access_token);
            // EmailController::setEnvVar("EMAIL_CONNECTOR_OFFICE_REFRESH_TOKEN{$index}", $token->refresh_token);
            // EmailController::setEnvVar("EMAIL_CONNECTOR_OFFICE_ACCESS_TOKEN_EXPIRE_TIME{$index}", $expirationTime);
        } catch (Throwable $error) {
            \Log::debug("!!!!!!!!!!!!!!!!!! REFRESH ACCESS TOKEN FAILED !!!!!!!!!!!!!!!!!", ["ERROR" => $error]);
            throw $error;
        }
    }
}


