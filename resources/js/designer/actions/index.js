import createActions from "../base/actionsCreator"
import {designer} from "./designer"
import {bpmn} from "./bpmn"

export default createActions(Object.assign(
    {},
    designer,
    bpmn
));
