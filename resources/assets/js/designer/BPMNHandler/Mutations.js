import actions from "../actions"


function shapeUpdate(data, elements) {
    if (elements[data.id] && data.bounds) {
        elements[data.id].diagram.elements[0].attributes = data.bounds
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
    [actions.bpmn.shape.create]: shapeCreate
}





















