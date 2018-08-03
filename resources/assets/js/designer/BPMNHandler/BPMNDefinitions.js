import _ from "lodash"

/**
 * BPMNDefinitions class - Manage the tags definitions in XML
 */
let defs = {
    TAG_MODEL: "http://www.omg.org/spec/BPMN/20100524/MODEL",
    TAG_DI: "http://www.omg.org/spec/BPMN/20100524/DI",
    TAG_BPMNDI: "http://www.omg.org/spec/DD/20100524/DI",
    TAG_DC: "http://www.omg.org/spec/DD/20100524/DC",
    TAG_XSI: "http://www.w3.org/2001/XMLSchema-instance",
    TARGET_NAMESPACE: "http://bpmn.io/schema/bpmn"
}

export default class BPMNDefinitions {
    constructor(attributes) {
        this.data = attributes
        this.model = null
        this.dc = null
        this.xsi = null
        this.di = null
        this.targetNameSpace = null
        this.processTags()
    }

    getmodel() {
        return this.model.split(":")[1]
    }

    getdc() {
        return this.dc.split(":")[1]
    }

    getxsi() {
        return this.xsi.split(":")[1]
    }

    getdi() {
        return this.di.split(":")[1]
    }

    getbpmndi() {
        return this.bpmndi.split(":")[1]
    }


    processTags() {
        _.each(this.data, (value, key) => {
            if (value == defs.TAG_MODEL) {
                this.model = key
            }
            if (value == defs.TAG_DI) {
                this.di = key
            }
            if (value == defs.TAG_BPMNDI) {
                this.bpmndi = key
            }
            if (value == defs.TAG_DC) {
                this.dc = key
            }
            if (value == defs.TAG_XSI) {
                this.xsi = key
            }
            if (value == defs.TARGET_NAMESPACE) {
                this.targetNameSpace = key
            }
        })
    }
}
