import _ from "lodash"

/**
 * BPMNHandler class
 */
export default class BPMNHandler {
    constructor(bpmn) {
        this.bpmn = bpmn
        //Elements for update
        this.elements = {} // Models from Elements

        this.bpmnDesigner = {
            shapes: [],
            links: []
        } // Builded elements to Process Designer

        this.elementsDiagram = [] // diagrams
        this.processes = [] // processes definition
        this.collaborations = [] // collaborations objects
    }

    /**
     * this method build objects to process the BPMN
     */
    buildModel() {
        this.elementsDiagram = this.findBPMNDiagram()
        this.processes = this.findProcess()
        this.collaborations = this.findCollaboration()

        this.buildElementsDiagram(this.elementsDiagram)
        console.log(this.bpmnDesigner)
        return this.bpmnDesigner
    }

    /**
     * This method find a type definition in BPMN
     * @param type
     */
    findDefinition(type) {
        let BPMNDiagram = _.find(this.bpmn.elements[0].elements, (value) => {
            return value.name == type ? true : false
        })
        return BPMNDiagram.elements[0].elements
    }

    /**
     * This method find a object collaboration in BPMN
     * @returns {null}
     */
    findCollaboration() {
        let collaboration = _.find(this.bpmn.elements[0].elements, (value) => {
            return value.name.indexOf("collaboration") >= 0 ? true : false
        })
        return collaboration ? collaboration.elements : null
    }

    /**
     * This method find a object BPMNDiagram in BPMN
     */
    findBPMNDiagram() {
        let BPMNDiagram = _.find(this.bpmn.elements[0].elements, (value) => {
            return value.name.indexOf("BPMNDiagram") >= 0 ? true : false
        })
        return BPMNDiagram.elements[0].elements
    }

    /**
     * This method find a object Process in BPMN
     */
    findProcess() {
        return _.filter(this.bpmn.elements[0].elements, (value) => {
            return value.name.indexOf("process") >= 0 ? true : false
        })
    }

    /**
     * This method build a elements map from diagram
     * @param els
     */
    buildElementsDiagram(els) {
        let that = this
        _.find(els, (value) => {
            let idBpmnElement = value.attributes.bpmnElement
            let bpmnEl
            bpmnEl = that.findElementInCollaboration(this.collaborations, idBpmnElement)
            bpmnEl = !bpmnEl ? that.findElementInProcess(this.processes, idBpmnElement) : bpmnEl

            if (bpmnEl && value.name == "bpmndi:BPMNEdge") {
                that.elements[idBpmnElement] = {
                    diagram: value,
                    process: bpmnEl
                }
                that.bpmnDesigner.links.push(that.formatEdge(value, bpmnEl))

            } else if (bpmnEl) {
                //console.log(bpmnEl, idBpmnElement)
                that.elements[idBpmnElement] = {
                    diagram: value,
                    process: bpmnEl
                }
                that.bpmnDesigner.shapes.push(this.formatElement(value, bpmnEl))
            }
        })
    }

    /**
     * Find elements in ProcessObject
     * @param processes
     * @param idbpmn
     * @returns {*}
     */
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

    /**
     * Find element in object Collaboration
     * @param colls
     * @param idbpmn
     * @returns {*}
     */
    findElementInCollaboration(colls, idbpmn) {
        let element
        _.each(colls, (coll) => {
            if (coll.attributes && coll.attributes.id && coll.attributes.id == idbpmn) {
                element = coll
            }
        })
        return element
    }

    /**
     * Format a diagram element for send to process designer
     * @param di
     * @param bpmn
     * @returns {*}
     */
    formatElement(di, bpmn) {
        let Element = {}
        let attr = this.getAttributes(di, "dc:Bounds")
        let name = bpmn.name.split(':')
        _.forEach(attr, (value, key, obj) => {
            obj[key] = parseFloat(value)
        })
        return Object.assign({}, attr, {
            type: name.length == 1 ? name[0].toLowerCase() : name[1].toLowerCase(),
            id: di.attributes.bpmnElement
        })
    }

    /**
     * Format a diagram element for send to process designer
     * @param di
     * @param bpmn
     * @returns {*}
     */
    formatEdge(di, bpmn) {
        let Element = {}
        let attr = this.getAttributes(di, "bpmndi:BPMNEdge")
        let name = bpmn.name.split(':')
        let wayPoints = []

        //From BPMN Element
        let destTarget = this.getAttributes(bpmn, "Flow")

        //From diagram
        _.each(di.elements, (el) => {
            _.forEach(el.attributes, (value, key, obj) => {
                obj[key] = parseFloat(value)
            })
            if (el.name == "di:waypoint") {
                wayPoints.push(el.attributes)
            }
        })

        return Object.assign({}, attr, {
            type: name.length == 1 ? name[0].toLowerCase() : name[1].toLowerCase(),
            id: di.attributes.bpmnElement
        }, destTarget, {wayPoints})
    }

    /**
     * Get the attributes from element
     * @param di
     * @param property
     * @returns {*}
     */
    getAttributes(di, property) {
        if (di && di.name && di.name.indexOf(property) >= 0) {
            return di.attributes
        } else if (di) {
            return this.getAttributes(di.elements[0] ? di.elements[0] : null, property)
        } else {
            return null
        }
    }
}
