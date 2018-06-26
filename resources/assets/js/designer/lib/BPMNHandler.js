import _ from "lodash"

/**
 * BPMNHandler class
 */
export default class BPMNHandler {
    constructor(bpmn) {
        this.bpmn = bpmn
        //Elements for update
        this.elements = {}
        this.buildElements = []
        this.elementsDiagram = []
        this.process = []
    }


    buildModel() {
        this.elementsDiagram = this.findBPMNDiagram()
        this.process = this.findProcess()
        this.buildElementsDiagram(this.elementsDiagram)
        return this.buildElements
    }


    findCollaboration() {

    }

    findBPMNDiagram() {
        let BPMNDiagram = _.find(this.bpmn.elements[0].elements, (value) => {
            return value.name == "bpmndi:BPMNDiagram" ? true : false
        })
        return BPMNDiagram.elements[0].elements
    }

    findProcess() {
        return _.filter(this.bpmn.elements[0].elements, (value) => {
            return value.name == "bpmn:process" ? true : false
        })
    }

    buildElementsDiagram(els) {
        let that = this
        _.find(els, (value) => {
            let idBpmnElement = value.attributes.bpmnElement
            let bpmnEl = that.findElementInProcess(this.process, idBpmnElement)
            if (value.name == "bpmndi:BPMNShape") {
                that.elements[idBpmnElement] = {
                    diagram: value,
                    process: bpmnEl
                }
                that.buildElements.push(this.formatElement(value, bpmnEl))
            }
        })
    }

    findElementInProcess(processes, idbpmn) {
        let element
        _.each(processes, (process) => {
            let el = _.find(process.elements, (el) => {
                return el.attributes.id == idbpmn
            })
            element = el ? el : element
        })
        return element
    }

    formatElement(di, bpmn) {
        let Element = {}
        let attr = this.getAttributes(di, "dc:Bounds")
        let name = bpmn.name.split(':')
        _.forEach(attr, (value, key, obj) => {
            obj[key] = parseInt(value)
        })
        return Object.assign({}, attr, {type: name[1], id: di.attributes.bpmnElement})
    }


    getAttributes(di, property) {
        if (di.name && di.name == property) {
            return di.attributes
        } else {
            return this.getAttributes(di.elements[0] ? di.elements[0] : {}, property)
        }
    }

    getElements(di, property) {
        return di.name == property ? di.elements : null
    }


}
