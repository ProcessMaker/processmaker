<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\Flow;

/**
 * Flow transformer, used to prepare the JSON response returned in the
 * designer's endpoints.
 *
 * @package ProcessMaker\Transformer
 */
class FlowTransformer extends TransformerAbstract
{

    /**
     * Transform the flow.
     *
     * @param Flow $flow
     *
     * @return array
     */
    public function transform(Flow $flow)
    {
        return [
            'flo_uid'                 => $flow->FLO_UID,
            'flo_type'                => $flow->FLO_TYPE,
            'flo_name'                => $flow->FLO_NAME,
            'flo_element_origin'      => $flow->FLO_ELEMENT_ORIGIN,
            'flo_element_origin_type' => $flow->FLO_ELEMENT_ORIGIN_TYPE,
            'flo_element_dest'        => $flow->FLO_ELEMENT_DEST,
            'flo_element_dest_type'   => $flow->FLO_ELEMENT_DEST_TYPE,
            'flo_is_inmediate'        => $flow->FLO_IS_INMEDIATE,
            'flo_condition'           => $flow->FLO_CONDITION,
            'flo_x1'                  => $flow->FLO_X1,
            'flo_y1'                  => $flow->FLO_Y1,
            'flo_x2'                  => $flow->FLO_X2,
            'flo_y2'                  => $flow->FLO_Y2,
            'flo_state'               => $flow->FLO_STATE,
            'flo_position'            => $flow->FLO_POSITION,
        ];
    }
}
