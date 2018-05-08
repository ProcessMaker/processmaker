<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\Activity;

/**
 * Activity transformer, used to prepare the JSON response returned in the
 * designer's endpoints.
 *
 * @package ProcessMaker\Transformer
 */
class ActivityTransformer extends TransformerAbstract
{

    /**
     * Transform the activity.
     *
     * @param Activity $activity
     *
     * @return array
     */
    public function transform(Activity $activity)
    {
        return [
            'act_uid'                        => $activity->ACT_UID,
            'act_name'                       => $activity->ACT_NAME,
            'act_type'                       => $activity->ACT_TYPE,
            'act_is_for_compensation'        => $activity->ACT_IS_FOR_COMPENSATION,
            'act_start_quantity'             => $activity->ACT_START_QUANTITY,
            'act_completion_quantity'        => $activity->ACT_COMPLETION_QUANTITY,
            'act_task_type'                  => $activity->ACT_TASK_TYPE,
            'act_implementation'             => $activity->ACT_IMPLEMENTATION,
            'act_instantiate'                => $activity->ACT_INSTANTIATE,
            'act_script_type'                => $activity->ACT_SCRIPT_TYPE,
            'act_script'                     => $activity->ACT_SCRIPT,
            'act_loop_type'                  => $activity->ACT_LOOP_TYPE,
            'act_test_before'                => $activity->ACT_TEST_BEFORE,
            'act_loop_maximum'               => $activity->ACT_LOOP_MAXIMUM,
            'act_loop_condition'             => $activity->ACT_LOOP_CONDITION,
            'act_loop_cardinality'           => $activity->ACT_LOOP_CARDINALITY,
            'act_loop_behavior'              => $activity->ACT_LOOP_BEHAVIOR,
            'act_is_adhoc'                   => $activity->ACT_IS_ADHOC,
            'act_is_collapsed'               => $activity->ACT_IS_COLLAPSED,
            'act_completion_condition'       => $activity->ACT_COMPLETION_CONDITION,
            'act_ordering'                   => $activity->ACT_ORDERING,
            'act_cancel_remaining_instances' => $activity->ACT_CANCEL_REMAINING_INSTANCES,
            'act_protocol'                   => $activity->ACT_PROTOCOL,
            'act_method'                     => $activity->ACT_METHOD,
            'act_is_global'                  => $activity->ACT_IS_GLOBAL,
            'act_referer'                    => $activity->ACT_REFERER,
            'act_default_flow'               => $activity->ACT_DEFAULT_FLOW,
            'act_master_diagram'             => $activity->ACT_MASTER_DIAGRAM,
            "bou_x"                          => $activity->shape->BOU_X,
            "bou_y"                          => $activity->shape->BOU_Y,
            "bou_width"                      => $activity->shape->BOU_WIDTH,
            "bou_height"                     => $activity->shape->BOU_HEIGHT,
            "bou_container"                  => $activity->shape->BOU_CONTAINER,
            "bou_element"                    => $activity->shape->BOU_ELEMENT,
        ];
    }
}
