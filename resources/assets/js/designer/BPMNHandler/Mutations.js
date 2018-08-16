import actions from "../actions"
import _ from "lodash"
/**
 * Function to update participant object
 * @param data
 * @param elements
 */
function updateParticipant(data, elements) {
    if (elements[data.id] && data.bounds) {
        elements[data.id].diagram.elements[0].attributes = data.bounds
    }
}

/**
 * Function to create participant
 * @param data
 * @param elements
 * @param arrayElements
 * @param processes
 * @param collaborations
 */
function createParticipant(data, elements, arrayElements, processes, collaborations) {
    let process = {
        "type": "element",
        "name": "bpmn:participant",
        "attributes": {"id": data.id, "name": "", "processRef": ""},
        "elements": []
    }

    let diagram = {
        "type": "element",
        "name": "bpmndi:BPMNShape",
        "attributes": {"id": data.id + "_di", "bpmnElement": data.id},
        "elements": [{
            "type": "element",
            "name": "dc:Bounds",
            "attributes": data.bounds,
            "elements": []
        }]
    }

    elements[data.id] = {
        diagram,
        process
    }
    arrayElements.push(diagram)
    collaborations.push(process)
}
function createShape(payload, BPMNProcess, BPMNCollaboration, BPMNDiagram, BPMNDefinitions) {
    BPMNDiagram.createElement(payload)
    BPMNProcess.createElement(payload)
}

function createFlow(payload, BPMNProcess, BPMNCollaboration, BPMNDiagram, BPMNDefinitions) {
    BPMNDiagram.createEdge(payload)
    BPMNProcess.createFlow(payload)
}

function updateFlow(payload, BPMNProcess, BPMNCollaboration, BPMNDiagram, BPMNDefinitions) {
    BPMNDiagram.updateEdge(payload.id, payload)
}

function updateShape(payload, BPMNProcess, BPMNCollaboration, BPMNDiagram, BPMNDefinitions) {
    BPMNDiagram.updateElement(payload.id, payload)
    BPMNProcess.updateElement(payload)
}

function updateTask(payload, BPMNProcess, BPMNCollaboration, BPMNDiagram, BPMNDefinitions) {
    BPMNProcess.updateTask(payload)
}

export default {
    [actions.bpmn.shape.update]: updateShape,
    [actions.bpmn.participant.update]: updateParticipant,
    [actions.bpmn.flow.update]: updateFlow,
    [actions.bpmn.shape.create]: createShape,
    [actions.bpmn.flow.create]: createFlow,
    [actions.bpmn.participant.create]: createParticipant,
    [actions.bpmn.task.update]: updateTask,
    
}
