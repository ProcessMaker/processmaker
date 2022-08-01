<?php

namespace ProcessMaker\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Models\Screen;

class ImportedScreenSaved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var int */
    public $newScreenId;

    /** @var Screen */
    public $screen;

    /**
     * @param  int  $newScreenId
     * @param  Screen  $screen
     */
    public function __construct($newScreenId, $screen)
    {
        $this->newScreenId = $newScreenId;
        $this->screen = $screen;
    }
}
