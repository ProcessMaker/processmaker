import _ from "lodash"

/**
 * BPMNProcess class
 */
export default class BPMNProcess {
    constructor(data, BPMN) {
        this.data = data
        this.BPMN = BPMN
    }

    createElement(data) {
        let eventDefinition = data.eventDefinition ? this.createEventDefinition(data.eventDefinition) : null
        let arrEvent = eventDefinition ? [eventDefinition] : []

        let process = {
            "type": "element",
            "name": this.BPMN.BPMNDefinitions.getmodel() ? this.BPMN.BPMNDefinitions.getmodel() + ":" + data.type : data.type,
            "attributes": {"id": data.id},
            "elements": arrEvent
        }
        this.data.elements.push(process)
    }

    updateElement(data) {
        let element = this.findElement(data.id)
        Object.assign(element.attributes, data.attributes)
    }

    findElement(idBpmnElement) {
        let element
        _.each(this.data.elements, (value) => {
            if (value.attributes.id == idBpmnElement) {
                element = value
                return false
            }
        })
        return element
    }

    createFlow(data) {
        let process = {
            "type": "element",
            "name": this.BPMN.BPMNDefinitions.getmodel() ? this.BPMN.BPMNDefinitions.getmodel() + ":" + data.type : data.type,
            "attributes": {"id": data.id, "sourceRef": data.sourceRef, "targetRef": data.targetRef},
            "elements": []
        }
        this.data.elements.push(process)
    }

    createEventDefinition(def) {
        let event = {
            elements: [],
            name: this.BPMN.BPMNDefinitions.getmodel() ? this.BPMN.BPMNDefinitions.getmodel() + ":" + def : def,
            type: "element"
        }
        return event
    }
}
