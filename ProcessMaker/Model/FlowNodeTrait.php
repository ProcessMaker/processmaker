<?php

namespace ProcessMaker\Model;

/**
 * It is a node that can be used to define the flow of a process.
 *
 */
trait FlowNodeTrait
{
    use BaseElementTrait;

    /**
     * Owner diagram.
     *
     * @return Diagram
     */
    public function getDiagramAttribute()
    {
        return $this->shape->diagram;
    }

    /**
     * Incoming flows.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function incoming()
    {
        return $this->morphMany(Flow::class, 'outgoing', 'FLO_ELEMENT_DEST_TYPE', 'FLO_ELEMENT_DEST');
    }

    /**
     * Outgoing flows.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function outgoing()
    {
        return $this->morphMany(Flow::class, 'incomming', 'FLO_ELEMENT_ORIGIN_TYPE', 'FLO_ELEMENT_ORIGIN');
    }

    /**
     * Create a flow to a target node.
     *
     * @param \ProcessMaker\Model\FlowNodeInterface $target
     * @param array $options
     * @return $this
     */
    public function createFlowTo(FlowNodeInterface $target, array $options = [])
    {
        $this->diagram->createFlow($this, $target, $options);
        return $this;
    }
}
