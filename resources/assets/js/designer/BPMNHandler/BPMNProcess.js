import _ from "lodash"

/**
 * BPMNProcess class
 */
export default class BPMNProcess {
    constructor(data, BPMN) {
        this.data = data
        this.BPMN = BPMN
    }

    createElement() {
        let process = {
            "type": "element",
            "name": this.BPMN.BPMNDefinitions.getmodel() + ":" + data.type,
            "attributes": {"id": data.id},
            "elements": arrEvent
        }
    }
}
