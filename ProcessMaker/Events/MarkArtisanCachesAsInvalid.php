<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;

class MarkArtisanCachesAsInvalid
{
    use Dispatchable;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
}
