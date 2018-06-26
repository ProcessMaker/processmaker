import _ from "lodash"

/**
 * BPMNHandler class
 */
export default class BPMNHandler {
    constructor(bpmn) {
        this.bpmn = bpmn
    }

    findCollaboration() {

    }

    findBPMNDiagram() {
        let BPMNDiagram = _.find(this.bpmn.elements[0].elements, (value) => {
            return value.name == "bpmndi:BPMNDiagram" ? true : false
        })
        debugger
    }
}
