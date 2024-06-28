<?php

namespace ProcessMaker\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Models\Screen;
use ProcessMaker\Packages\Connectors\Email\ScreenRenderer;

class TaskActionByEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $emailConfig;
    public $mailTo;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($emailConfig, $to)
    {
        $this->emailConfig = $emailConfig;
        $this->mailTo = $to;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->emailConfig->subject)
                    ->to($this->mailTo)
                    ->html($this->generateBody());
    }

    protected function generateBody()
    {
        $screen = Screen::findOrFail($this->emailConfig->screenEmailRef);
        return ScreenRenderer::render($screen->config, []);
    }
}
