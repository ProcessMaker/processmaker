<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\Laneset;

/**
 * Laneset transformer, used to prepare the JSON response returned in the
 * designer's endpoints.
 *
 * @package ProcessMaker\Transformers
 */
class LanesetTransformer extends TransformerAbstract
{

    /**
     * Transform the laneset.
     *
     * @param Laneset $laneset
     *
     * @return array
     */
    public function transform(Laneset $laneset)
    {
        return [
            'lns_uid'           => $laneset->LNS_UID,
            'pro_id'            => $laneset->PRO_ID,
            'lns_name'          => $laneset->LNS_NAME,
            'lns_parent_lane'   => $laneset->LNS_PARENT_LANE,
            'lns_is_horizontal' => $laneset->LNS_IS_HORIZONTAL,
            'lns_state'         => $laneset->LNS_STATE,
            "bou_x"             => $laneset->shape->BOU_X,
            "bou_y"             => $laneset->shape->BOU_Y,
            "bou_width"         => $laneset->shape->BOU_WIDTH,
            "bou_height"        => $laneset->shape->BOU_HEIGHT,
            "bou_container"     => $laneset->shape->BOU_CONTAINER,
            "bou_element"       => $laneset->shape->BOU_ELEMENT,
            "bou_rel_position"  => $laneset->shape->BOU_REL_POSITION,
            "dia_uid"           => $laneset->shape->diagram->DIA_UID,
            "element_uid"       => $laneset->shape->diagram->ELEMENT_UID,
            "pro_uid"           => $laneset->process->PRO_UID,
        ];
    }
}
