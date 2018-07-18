import actions from "../actions"


function shapeUpdate(elements, data) {
    debugger
    if (elements[data.id] && data.bounds) {
        elements[data.id].diagram.elements[0].attributes = data.bounds
    }
}


export default {
    [actions.bpmn.shape.update]: shapeUpdate
}





















