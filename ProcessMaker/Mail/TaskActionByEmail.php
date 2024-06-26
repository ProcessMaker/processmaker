<?php

namespace ProcessMaker\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TaskActionByEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $emailConfig;

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
                    ->view('test.email');
    }
}
