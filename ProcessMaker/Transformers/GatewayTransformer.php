<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\Gateway;

/**
 * Gateway transformer, used to prepare the JSON response returned in the
 * designer's endpoints.
 *
 * @package ProcessMaker\Transformer
 */
class GatewayTransformer extends TransformerAbstract
{

    /**
     * Transform the gateway.
     *
     * @param Gateway $gateway
     *
     * @return array
     */
    public function transform(Gateway $gateway)
    {
        return [
            'gat_uid'                => $gateway->GAT_UID,
            'gat_name'               => $gateway->GAT_NAME,
            'gat_type'               => $gateway->GAT_TYPE,
            'gat_direction'          => $gateway->GAT_DIRECTION,
            'gat_instantiate'        => $gateway->GAT_INSTANTIATE,
            'gat_event_gateway_type' => $gateway->GAT_EVENT_GATEWAY_TYPE,
            'gat_activation_count'   => $gateway->GAT_ACTIVATION_COUNT,
            'gat_waiting_for_start'  => $gateway->GAT_WAITING_FOR_START,
            'gat_default_flow'       => $gateway->GAT_DEFAULT_FLOW,
            'bou_x'                  => $gateway->shape->BOU_X,
            'bou_y'                  => $gateway->shape->BOU_Y,
            'bou_width'              => $gateway->shape->BOU_WIDTH,
            'bou_height'             => $gateway->shape->BOU_HEIGHT,
            'bou_container'          => $gateway->shape->BOU_CONTAINER,
            "bou_element"            => $gateway->shape->BOU_ELEMENT,
        ];
    }
}
