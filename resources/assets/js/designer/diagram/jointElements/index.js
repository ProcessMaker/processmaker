import {InclusiveGateway} from "./InclusiveGateway"
import {ParallelGateway} from "./ParallelGateway"
import {ExclusiveGateway} from "./ExclusiveGateway"
import {IntermediateEmailEvent} from "./IntermediateEmailEvent"
import {IntermediateTimerEvent} from "./IntermediateTimerEvent"
import {EndEmailEvent} from "./EndEmailEvent"
import {StartEvent} from "./StartEvent"
import {EndEvent} from "./EndEvent"
import {DataObject} from "./DataObject"
import {DataStore} from "./DataStore"
import {Pool} from "./Pool"
import {Group} from "./Group"
import {BlackBoxPool} from "./BlackBoxPool"
import {SubProcess} from "./SubProcess"
import {Task} from "./Task"

export const JointElements = Object.assign({}, {
    InclusiveGateway,
    ParallelGateway,
    ExclusiveGateway,
    IntermediateEmailEvent,
    IntermediateTimerEvent,
    EndEmailEvent,
    StartEvent,
    EndEvent,
    DataObject,
    DataStore,
    Pool,
    Group,
    BlackBoxPool,
    SubProcess,
    Task
})
