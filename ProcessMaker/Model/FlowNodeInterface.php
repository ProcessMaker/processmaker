<?php

namespace ProcessMaker\Model;

/**
 * Flow node are those elements that can be used in a process
 * flow, which are: Activities, Gateways and Events.
 *
 */
interface FlowNodeInterface extends ElementInterface
{

    /**
     * Incoming flows.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function incoming();

    /**
     * Outgoing flows.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function outgoing();

    /**
     * Get the value of the model's primary key.
     *
     * @return mixed
     */
    public function getKey();
}
