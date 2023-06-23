<?php

namespace ProcessMaker\Jobs;

use Closure;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Queue\SerializableClosure;

abstract class ClosureJobInterface extends CallQueuedClosure
{

    /**
     * Create a new job instance.
     *
     * @param \Illuminate\Queue\SerializableClosure $closure
     */
    public function __construct(Closure $closure)
    {
        parent::__construct(new SerializableClosure($closure));
    }
}
