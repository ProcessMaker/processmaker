<?php

namespace ProcessMaker\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Config\Repository as RepositoryContract;

class SettingsLoaded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    public $repository;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(RepositoryContract $repository)
    {
        $this->repository = &$repository;
    }
}
