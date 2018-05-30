import {StartEvent} from "./events/startEvent"
import {EndEvent} from "./events/endEvent"
import {Task} from "./tasks/Task"
import {ExclusiveGateway} from "./gateways/ExclusiveGateway"
import {ParallelGateway} from "./gateways/parallelGateway"
import {InclusiveGateway} from "./gateways/inclusiveGateway"

export const Elements = Object.assign({}, {
    StartEvent,
    EndEvent,
    Task,
    ExclusiveGateway,
    ParallelGateway,
    InclusiveGateway
})
