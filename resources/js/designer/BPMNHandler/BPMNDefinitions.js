import _ from "lodash"

/**
 * BPMNDefinitions class - Manage the tags definitions in XML
 */
let defs = {
    TAG_MODEL: "http://www.omg.org/spec/BPMN/20100524/MODEL",
    TAG_DI: "http://www.omg.org/spec/BPMN/20100524/DI",
    TAG_BPMNDI: "http://www.omg.org/spec/BPMN/20100524/DI",
    TAG_DC: "http://www.omg.org/spec/DD/20100524/DC",
    TAG_XSI: "http://www.w3.org/2001/XMLSchema-instance",
    TARGET_NAMESPACE: "http://bpmn.io/schema/bpmn",
    PM_NAMESPACE: "https://bpm4.processmaker.local/definition/ProcessMaker.xsd",
    
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

    getnonamespace(tagName) {
        return tagName;
    }

    getmodel(tagName) {
        return this.formatNS(this.model, tagName);
    }

    getdc(tagName) {
        return this.formatNS(this.dc, tagName);
    }

    getxsi(tagName) {
        return this.formatNS(this.xsi, tagName);
    }

    getdi(tagName) {
        return this.formatNS(this.di, tagName);
    }

    getbpmndi(tagName) {
        return this.formatNS(this.bpmndi, tagName);
    }

    getpm(tagName) {
        return this.formatNS(this.pmNamespace, tagName);
    }

    formatNS(namespace, tagName){
        let ns = namespace.split(":");
        return tagName ? (ns.length === 2 ? ns[1] + ':' + tagName : tagName)
                : (ns.length === 2 ? ns[1] : undefined);
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
            if (value == defs.PM_NAMESPACE) {
                this.pmNamespace = key
            }
        })
    }
}
