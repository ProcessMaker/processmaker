import startevent from "./events/StartEvent"
import endevent from "./events/EndEvent"
import task from "./tasks/Task"
import servicetask from "./tasks/ServiceTask"
import {InclusiveGateway} from "./gateways/InclusiveGateway"
import {ParallelGateway} from "./gateways/ParallelGateway"
import exclusivegateway from "./gateways/ExclusiveGateway"
import intermediatecatchevent from "./events/IntermediateCatchEvent"
import intermediatethrowevent from "./events/IntermediateThrowEvent"
import {IntermediateTimerEvent} from "./events/IntermediateTimerEvent"
import {EndEmailEvent} from "./events/EndEmailEvent"
import {Flow} from "./flow/Flow"
import {DataObject} from "./data/DataObject"
import {DataStore} from "./data/DataStore"
import {Pool} from "./swimLanes/Pool"
import {Lane} from "./swimLanes/Lane"
import {Group} from "./artifacts/Group"
import {BlackBoxPool} from "./swimLanes/BlackBoxPool"
import {SubProcess} from "./tasks/SubProcess"

export const Elements = Object.assign({}, {
    startevent,
    intermediatecatchevent,
    intermediatethrowevent,
    IntermediateTimerEvent,
    endevent,
    EndEmailEvent,
    task,
    servicetask,
    Flow,
    InclusiveGateway,
    ParallelGateway,
    exclusivegateway,
    DataObject,
    DataStore,
    Pool,
    Lane,
    Group,
    BlackBoxPool,
    SubProcess
})
