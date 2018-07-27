import actions from "../actions"
import _ from "lodash"

function participantUpdate(data, elements) {
    if (elements[data.id] && data.bounds) {
        elements[data.id].diagram.elements[0].attributes = data.bounds
    }
}

function shapeUpdate(data, elements) {
    if (elements[data.id] && data.bounds) {
        elements[data.id].diagram.elements[0].attributes = data.bounds
    }
}

function participantCreate(data, elements, arrayElements, processes, collaborations) {
    let process = {
        "type": "element",
        "name": "bpmn:participant",
        "attributes": {"id": data.id, "name": "Approval Link", "processRef": "approval_link"},
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

function shapeCreate(data, elements, arrayElements, processes) {
    let eventDefinition = data.eventDefinition ? createEventDefinition(data.eventDefinition) : null
    let arrEvent = eventDefinition ? [eventDefinition] : []
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

    let process = {
        "type": "element",
        "name": "bpmn:" + data.type,
        "attributes": {"id": data.id},
        "elements": arrEvent
    }

    elements[data.id] = {
        diagram,
        process
    }
    arrayElements.push(diagram)
    processes[0].elements.push(process)
}

function createEventDefinition(def) {
    let event = {
        elements: [],
        name: "bpmn:" + def,
        type: "element"
    }
    return event
}


function flowCreate(data, elements, arrayElements, processes) {
    let bounds = createBounds(data.bounds)

    let diagram = {
        "type": "element",
        "name": "bpmndi:BPMNEdge",
        "attributes": {"id": data.id + "_di", "bpmnElement": data.id},
        "elements": bounds
    }

    let process = {
        "type": "element",
        "name": "bpmn:sequenceFlow",
        "attributes": {"id": data.id, "sourceRef": data.sourceRef, "targetRef": data.targetRef},
        "elements": []
    }

    elements[data.id] = {
        diagram,
        process
    }
    arrayElements.push(diagram)
    processes[0].elements.push(process)
}


function flowUpdate(data, elements) {
    if (elements[data.id]) {
        elements[data.id].process.attributes = {
            "id": data.id,
            "sourceRef": data.sourceRef,
            "targetRef": data.targetRef
        }

        elements[data.id].diagram.attributes = {
            "id": data.id + "_di",
            "bpmnElement": data.id
        }

        elements[data.id].diagram.elements = createBounds(data.bounds)
    }
}

function createBounds(data) {
    let bounds = []
    _.each(data, (value) => {
        bounds.push({
            "type": "element",
            "name": "di:waypoint",
            "attributes": value,
            "elements": []
        })
    })
    return bounds
}

export default {
    [actions.bpmn.shape.update]: shapeUpdate,
    [actions.bpmn.participant.update]: participantUpdate,
    [actions.bpmn.flow.update]: flowUpdate,
    [actions.bpmn.shape.create]: shapeCreate,
    [actions.bpmn.flow.create]: flowCreate,
    [actions.bpmn.participant.create]: participantCreate
}
