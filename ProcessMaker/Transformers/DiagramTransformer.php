<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\Diagram;

/**
 * Diagram transformer, used to prepare the JSON response returned in the
 * designer's endpoints.
 *
 * @package ProcessMaker\Transformer
 */
class DiagramTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $defaultIncludes = [
        'activities', 'events', 'gateways', 'flows', 'artifacts', 'laneset', 'lanes'
    ];

    /**
     * Transform the diagram.
     *
     * @param Diagram $diagram
     *
     * @return array
     */
    public function transform(Diagram $diagram)
    {
        return [
            "dia_uid"         => $diagram->DIA_UID,
            "prj_uid"         => $diagram->process->PRO_UID,
            "dia_name"        => $diagram->DIA_NAME,
            "dia_is_closable" => (bool) $diagram->DIA_IS_CLOSABLE,
            //since the bpmn_process is merged to process has the same uid.
            "pro_uid"         => $diagram->process->PRO_UID
        ];
    }

    /**
     * Includes the collection of activities.
     *
     * @param Diagram $diagram
     *
     * @return \League\Fractal\Resource\Collection
     */
    public function includeActivities(Diagram $diagram)
    {
        return $this->collection($diagram->activities, new ActivityTransformer);
    }

    /**
     * Includes the collection of events.
     *
     * @param Diagram $diagram
     *
     * @return \League\Fractal\Resource\Collection
     */
    public function includeEvents(Diagram $diagram)
    {
        return $this->collection($diagram->events, new EventTransformer);
    }

    /**
     * Includes the collection of gateways.
     *
     * @param Diagram $diagram
     *
     * @return \League\Fractal\Resource\Collection
     */
    public function includeGateways(Diagram $diagram)
    {
        return $this->collection($diagram->gateways, new GatewayTransformer);
    }

    /**
     * Includes the collection of flows.
     *
     * @param Diagram $diagram
     *
     * @return \League\Fractal\Resource\Collection
     */
    public function includeFlows(Diagram $diagram)
    {
        return $this->collection($diagram->flows, new FlowTransformer);
    }

    /**
     * Includes the collection of artifacts.
     *
     * @param Diagram $diagram
     *
     * @return \League\Fractal\Resource\Collection
     */
    public function includeArtifacts(Diagram $diagram)
    {
        return $this->collection($diagram->process->artifacts, new ArtifactTransformer);
    }

    /**
     * Includes the collection of lane sets.
     *
     * @param Diagram $diagram
     *
     * @return \League\Fractal\Resource\Collection
     */
    public function includeLaneset(Diagram $diagram)
    {
        return $this->collection($diagram->process->lanesets, new LanesetTransformer);
    }

    /**
     * Includes the collection of lanes.
     *
     * @param Diagram $diagram
     *
     * @return \League\Fractal\Resource\Collection
     */
    public function includeLanes(Diagram $diagram)
    {
        return $this->collection($diagram->process->lanes, new LaneTransformer);
    }
}
