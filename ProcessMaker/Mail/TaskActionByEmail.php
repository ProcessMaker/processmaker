<?php

namespace ProcessMaker\Mail;

use Illuminate\Support\Facades\Log;
use Mustache_Engine;
use ProcessMaker\Models\Screen;
use ProcessMaker\Packages\Connectors\ActionsByEmail\EmailProvider;

class TaskActionByEmail
{
    private $emailProvider;

    public function __construct()
    {
        $this->emailProvider = new EmailProvider();
    }

    /**
     * Send an email based on the provided configuration and data.
     *
     * This method constructs and sends an email using the provided configuration,
     * recipient email address, and additional data for rendering the email content.
     *
     * @param array $config Configuration object containing email settings, including:
     *                       - emailServer: The email server ID (integer, optional, default: 0).
     *                       - subject: The subject of the email (string, optional, default: '').
     *                       - screenEmailRef: Reference ID to a custom email screen (integer, optional, default: 0).
     * @param string $to Recipient email address.
     * @param array $data Additional data for rendering the email content (optional).
     *
     * @return void
     */
    public function sendAbeEmail($config, $to, $data)
    {
        try {
            // Get parameters to send the email
            $emailServer = $config['emailServer'] ?? 0;
            $subject = $config['subject'] ?? '';
            $emailScreenRef = $config['screenEmailRef'] ?? 0;
    
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
                // Default message if no custom screen is configured
                $emailConfig['body'] = __('No screen configured');
            }
    
            // Send the email using emailProvider
            $this->emailProvider->send($emailConfig);
    
        } catch (\Exception $e) {
            Log::error('Error sending ABE email', [
                'to' => $to,
                'emailServer' => $emailServer,
                'subject' => $subject,
                'screen' => $emailScreenRef,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function mustache($str, $data)
    {
        return (new Mustache_Engine())->render($str, $data);
    }
}
