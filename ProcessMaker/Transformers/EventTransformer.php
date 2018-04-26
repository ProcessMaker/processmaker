<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\Event;

/**
 * Event transformer, used to prepare the JSON response returned in the
 * designer's endpoints.
 *
 * @package ProcessMaker\Transformer
 */
class EventTransformer extends TransformerAbstract
{

    /**
     * Transform the event.
     *
     * @param Event $event
     *
     * @return array
     */
    public function transform(Event $event)
    {
        return [
            "evn_uid"                          => $event->EVN_UID,
            "evn_name"                         => $event->EVN_NAME,
            "evn_type"                         => $event->EVN_TYPE,
            "evn_marker"                       => $event->EVN_MARKER,
            "evn_is_interrupting"              => $event->EVN_IS_INTERRUPTING,
            "evn_cancel_activity"              => $event->EVN_CANCEL_ACTIVITY,
            "evn_activity_ref"                 => $event->EVN_ACTIVITY_REF,
            "evn_wait_for_completion"          => $event->EVN_WAIT_FOR_COMPLETION,
            "evn_error_name"                   => $event->EVN_ERROR_NAME,
            "evn_error_code"                   => $event->EVN_ERROR_CODE,
            "evn_escalation_name"              => $event->EVN_ESCALATION_NAME,
            "evn_escalation_code"              => $event->EVN_ESCALATION_CODE,
            "evn_message"                      => $event->EVN_MESSAGE,
            "evn_operation_name"               => $event->EVN_OPERATION_NAME,
            "evn_operation_implementation_ref" => $event->EVN_OPERATION_IMPLEMENTATION_REF,
            "evn_time_date"                    => $event->EVN_TIME_DATE,
            "evn_time_cycle"                   => $event->EVN_TIME_CYCLE,
            "evn_time_duration"                => $event->EVN_TIME_DURATION,
            "evn_behavior"                     => $event->EVN_BEHAVIOR,
            "bou_x"                            => $event->shape->BOU_X,
            "bou_y"                            => $event->shape->BOU_Y,
            "bou_width"                        => $event->shape->BOU_WIDTH,
            "bou_height"                       => $event->shape->BOU_HEIGHT,
            "bou_container"                    => $event->shape->BOU_CONTAINER,
            "bou_element"                      => $event->shape->BOU_ELEMENT,
        ];
    }
}
