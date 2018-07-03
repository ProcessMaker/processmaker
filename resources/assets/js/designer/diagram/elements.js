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
import intermediatetimerevent from "./events/IntermediateTimerEvent"
import endemailevent from "./events/EndEmailEvent"
import sequenceflow from "./flow/SequenceFlow"
import messageflow from "./flow/MessageFlow"
import dataobject from "./data/DataObject"
import datastore from "./data/DataStore"
import participant from "./swimLanes/Participant"
import lane from "./swimLanes/Lane"
import group from "./artifacts/Group"
import blackboxpool from "./swimLanes/BlackBoxPool"
import callactivity from "./tasks/CallActivity"

export const Elements = Object.assign({}, {
    startevent,
    intermediatecatchevent,
    intermediatethrowevent,
    intermediatetimerevent,
    endevent,
    endemailevent,
    task,
    servicetask,
    scripttask,
    sequenceflow,
    messageflow,
    inclusivegateway,
    parallelgateway,
    exclusivegateway,
    dataobject,
    datastore,
    participant,
    lane,
    group,
    blackboxpool,
    callactivity
})
