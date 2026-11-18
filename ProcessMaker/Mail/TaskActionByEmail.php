<?php

namespace ProcessMaker\Mail;

use Illuminate\Support\Facades\Log;
use Mustache_Engine;
use ProcessMaker\Models\Screen;
use ProcessMaker\Packages\Connectors\ActionsByEmail\EmailProvider;

class TaskActionByEmail
{
    private $emailProvider;

    /**
     * Shared screen/subject render for a single personalized-send batch.
     * Personalized ABE sends reuse this and only rewrite signed links per recipient.
     *
     * @var array{subject:string,body:string,styles:string,emailServer:mixed}|null
     */
    private $sharedContent = null;

    public function __construct(?EmailProvider $emailProvider = null)
    {
        $this->emailProvider = $emailProvider ?? new EmailProvider();
    }

    /**
     * Send an email based on the provided configuration and data.
     *
     * This method constructs and sends an email using the provided configuration,
     * recipient email address, and additional data for rendering the email content.
     *
     * When the same TaskActionByEmail instance sends to multiple personalized
     * recipients, the email screen is rendered once and reused; only signed
     * action-link params are applied per recipient.
     *
     * @param array $config Configuration object containing email settings, including:
     *                       - emailServer: The email server ID (integer, optional, default: 0).
     *                       - subject: The subject of the email (string, optional, default: '').
     *                       - screenEmailRef: Reference ID to a custom email screen (integer, optional, default: 0).
     * @param string $to Recipient email address.
     * @param array $data Additional data for rendering the email content (optional).
     * @param string|null $signedQuery Optional signed ABE query string to append to action links.
     * @param int $delaySeconds Queue delay before the job runs (throttle bulk personalized sends).
     *
     * @return void
     */
    public function sendAbeEmail($config, $to, $data, ?string $signedQuery = null, int $delaySeconds = 0)
    {
        try {
            $shared = $this->sharedEmailContent($config, $data);
            $emailServer = $shared['emailServer'];

            $body = $signedQuery
                ? $this->appendSignedParamsToAbeLinks($shared['body'], $signedQuery)
                : $shared['body'];

            $emailConfig = [
                'subject' => $shared['subject'],
                'addEmails' => $to,
                'email' => $to,
                'type' => 'html',
                'json_data' => '{}',
                'emailServer' => $emailServer,
                'body' => $body,
            ];

            if ($shared['styles'] !== '') {
                $emailConfig['styles'] = $shared['styles'];
            }

            // Personalized ABE sends are one message per recipient (unique signed link).
            // Stagger queue dispatch so SMTP test providers (e.g. Mailtrap) are not rate-limited.
            $this->emailProvider->sendAsync($emailConfig, $delaySeconds);
        } catch (\Exception $e) {
            Log::error('Error sending ABE email', [
                'to' => $to,
                'emailServer' => $emailServer ?? ($config['emailServer'] ?? 0),
                'subject' => $config['subject'] ?? '',
                'screen' => $config['screenEmailRef'] ?? 0,
                'error' => $e->getMessage(),
            ]);
        }

        return true;
    }

    /**
     * Render subject/body/styles once per mailer instance for a recipient batch.
     *
     * @return array{subject:string,body:string,styles:string,emailServer:mixed}
     */
    private function sharedEmailContent(array $config, array $data): array
    {
        if ($this->sharedContent !== null) {
            return $this->sharedContent;
        }

        $emailServer = $config['emailServer'] ?? 0;
        $subject = $this->mustache($config['subject'] ?? '', $data);
        $emailScreenRef = $config['screenEmailRef'] ?? 0;
        $body = __('No screen configured');
        $styles = '';

        if (!empty($emailScreenRef)) {
            $customScreen = Screen::findOrFail($emailScreenRef);
            $infoRenderer = $this->emailProvider->screenRenderer($customScreen->config, $data, $customScreen);
            $body = $infoRenderer['body'] ?? __('No body configured');
            $styles = $infoRenderer['styles'] ?? '';
        }

        $this->sharedContent = [
            'subject' => $subject,
            'body' => $body,
            'styles' => $styles,
            'emailServer' => $emailServer,
        ];

        return $this->sharedContent;
    }

    /**
     * Append signed recipient params to known ABE action URLs only.
     */
    public function appendSignedParamsToAbeLinks(string $html, string $signedQuery): string
    {
        if ($signedQuery === '' || $html === '') {
            return $html;
        }

        return preg_replace_callback(
            '/(\bhref=["\'])([^"\']*\/tasks\/update_variable\/[^"\']*)(["\'])/i',
            static function (array $matches) use ($signedQuery) {
                $url = html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5);
                if (str_contains($url, 'abe_sig=')) {
                    return $matches[0];
                }
                $separator = str_contains($url, '?') ? '&' : '?';

                return $matches[1] . $url . $separator . $signedQuery . $matches[3];
            },
            $html
        ) ?? $html;
    }

    private function mustache($str, $data)
    {
        return (new Mustache_Engine())->render($str, $data);
    }
}
