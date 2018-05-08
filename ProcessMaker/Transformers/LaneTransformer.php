<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\Lane;

/**
 * Lane transformer, used to prepare the JSON response returned in the
 * designer's endpoints.
 *
 * @package ProcessMaker\Transformers
 */
class LaneTransformer extends TransformerAbstract
{

    /**
     * Transform the lane.
     *
     * @param Lane $lane
     *
     * @return array
     */
    public function transform(Lane $lane)
    {
        return [
            'lan_uid'           => $lane->LAN_UID,
            'pro_id'            => $lane->PRO_ID,
            'lns_uid'           => $lane->LNS_UID,
            'lan_name'          => $lane->LAN_NAME,
            'lan_child_laneset' => $lane->LAN_CHILD_LANESET,
            'lan_is_horizontal' => $lane->LAN_IS_HORIZONTAL,
            "bou_x"             => $lane->shape->BOU_X,
            "bou_y"             => $lane->shape->BOU_Y,
            "bou_width"         => $lane->shape->BOU_WIDTH,
            "bou_height"        => $lane->shape->BOU_HEIGHT,
            "bou_container"     => $lane->shape->BOU_CONTAINER,
            "bou_element"       => $lane->shape->BOU_ELEMENT,
            "bou_rel_position"  => $lane->shape->BOU_REL_POSITION,
            "dia_uid"           => $lane->shape->diagram->DIA_UID,
            "element_uid"       => $lane->shape->diagram->ELEMENT_UID,
        ];
    }
}
