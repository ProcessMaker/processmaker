<?php

namespace ProcessMaker\Events;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Events\Dispatchable;

class SettingsLoaded
{
    use Dispatchable;

    /**
     * @implements Illuminate\Contracts\Config\Repository::class
     */
    public $configuration;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $configuration
     *
     * @return void
     */
    public function __construct(Repository $configuration)
    {
        $this->configuration = &$configuration;
    }
}
