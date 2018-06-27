import startevent from "./events/StartEvent"
import endevent from "./events/EndEvent"
import task from "./tasks/Task"
import servicetask from "./tasks/ServiceTask"
import scripttask from "./tasks/ScriptTask"
import inclusivegateway from "./gateways/InclusiveGateway"
import parallelgateway from "./gateways/ParallelGateway"
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
import participant from "./swimLanes/Participant"
import {Group} from "./artifacts/Group"
import {BlackBoxPool} from "./swimLanes/BlackBoxPool"
import callactivity from "./tasks/CallActivity"

export const Elements = Object.assign({}, {
    startevent,
    intermediatecatchevent,
    intermediatethrowevent,
    IntermediateTimerEvent,
    endevent,
    EndEmailEvent,
    task,
    servicetask,
    scripttask,
    Flow,
    inclusivegateway,
    parallelgateway,
    exclusivegateway,
    DataObject,
    DataStore,
    Pool,
    Lane,
    participant,
    Group,
    BlackBoxPool,
    callactivity
})
