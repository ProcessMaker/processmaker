import actions from "../actions"


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

function participantCreate(data, elements) {
    let participant = {
        "type": "element",
        "name": "bpmn:participant",
        "attributes": {"id": "Participant_18loy4z", "name": "Approval Link", "processRef": "approval_link"},
        "elements": []
    }
}

function shapeCreate(data, elements, arrayElements, processes) {
    let diagram = {
        "type": "element",
        "name": "bpmndi:BPMNShape",
        "attributes": {"id": "_BPMNShape_" + data.id, "bpmnElement": data.id},
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
        "elements": []
    }

    elements[data.id] = {
        diagram,
        process
    }
    arrayElements.push(diagram)
    processes[0].elements.push(process)
}

export default {
    [actions.bpmn.shape.update]: shapeUpdate,
    [actions.bpmn.participant.update]: participantUpdate,
    [actions.bpmn.shape.create]: shapeCreate,
    [actions.bpmn.participant.create]: participantCreate,

}





















