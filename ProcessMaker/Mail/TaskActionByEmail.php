<?php

namespace ProcessMaker\Mail;

use Mustache_Engine;
use ProcessMaker\Models\Screen;
use ProcessMaker\Packages\Connectors\ActionsByEmail\EmailProvider;
use ProcessMaker\Packages\Connectors\ActionsByEmail\MessageParser;

class TaskActionByEmail
{
    private $emailProvider;

    public function __construct()
    {
        $this->emailProvider = new EmailProvider();
    }

    public function sendAbeEmail($config, $to, $data)
    {
        try {
            // Get parameters to send the email
            $emailServer = $config->emailServer ?? 0;
            $subject = $config->subject ?? '';
            $emailScreenRef = $config->screenEmailRef ?? 0;
    
            $emailConfig = [
                'subject' => $this->mustache($subject, $data),
                'addEmails' => $to,
                'email' => $to,
                'type' => 'html',
                'json_data' => '{}',
                'emailServer' => $emailServer,
            ];
    
            if (!empty($emailScreenRef)) {
                // Retrieve and render custom screen if specified
                $customScreen = Screen::findOrFail($emailScreenRef);
                $emailConfig['body'] = $this->emailProvider->screenRenderer($customScreen->config, $data);
            } else {
                // Default message if no custom screen is selected
                $emailConfig['body'] = __('No screen selected');
            }
    
            // Send the email using emailProvider
            $this->emailProvider->send($emailConfig);
    
        } catch (\Exception $e) {
            Log::error('ABE: Mailer Daemon Response Found As Incoming Mail. Aborting Error Message Reply', [
                'to' => $to,
                'emailServer' => $emailServer,
                'Subjetc' => $subject,
                'screen' => $emailScreenRef,
            ]);
        }
    }

    private function mustache($str, $data)
    {
        return (new Mustache_Engine())->render($str, $data);
    }
}
