import _ from "lodash"
/**
 * BPMNDiagram class
 */
export default class BPMNDiagram {
    constructor(data, BPMN) {
        this.data = data
        this.BPMN = BPMN
    }

    findPlane(idPlane) {
        let plane = _.find(this.data, (value, key) => {
            return value.attributes.bpmnElement == idPlane
        })
        return plane
    }

    findElement(idBpmnElement) {
        let element
        _.each(this.data.elements, (value) => { // Find in Planes
            _.each(value.elements, (v) => { // Find in a Plane
                if (v.attributes.bpmnElement == idBpmnElement) {
                    element = v
                    return false
                }
            })
            if (element) {
                return false
            }
        })
        return element
    }

    /**
     *
     * @param idBpmnElement
     * @param keyOptions ex:"Bounds"
     * @param options - value to update
     */
    updateElement(idBpmnElement, keyOptions, options) {
        let bpmnElement = this.findElement(idBpmnElement)
        let option = this.findOptionInElement(bpmnElement, keyOptions)
        option.attributes = options
    }

    updateEdge(idBpmnElement, options) {
        debugger
        let bpmnElement = this.findElement(idBpmnElement)
        this.deleteOptions(bpmnElement, "waypoint")
        let points = this.createBounds(options.bounds, this.BPMN.BPMNDefinitions.getdi())
        bpmnElement.elements = _.concat(bpmnElement.elements, points)
    }

    findOptionInElement(Element, keyOption) {
        let option = _.find(Element.elements, (value) => {
            return value.name.indexOf(keyOption) > 0
        })
        return option
    }

    deleteOptions(edgeElement, option) {
        _.remove(edgeElement.elements, (value) => {
            if (value.name.indexOf(option) > 0) {
                return true
            }
        })
    }

    createBounds(data, namespace) {
        let bounds = []
        _.each(data, (value) => {
            bounds.push({
                "type": "element",
                "name": namespace + ":waypoint",
                "attributes": value,
                "elements": []
            })
        })
        return bounds
    }

    createElement(data) {
        let diagram = {
            "type": "element",
            "name": this.BPMN.BPMNDefinitions.getdi() + ":BPMNShape",
            "attributes": {"id": data.id + "_di", "bpmnElement": data.id},
            "elements": [{
                "type": "element",
                "name": this.BPMN.BPMNDefinitions.getdc() + ":Bounds",
                "attributes": data.bounds,
                "elements": []
            }]
        }
        this.addBPMNElement(diagram)
    }

    addBPMNElement(bpmnElement) {
        let element
        _.each(this.data.elements, (value) => { // Find in Planes
            value.elements.push(bpmnElement)
        })
    }
}
