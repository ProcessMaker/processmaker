import _ from "lodash"

/**
 * BPMNProcess class
 */
export default class BPMNProcess {
    constructor(data, BPMN) {
        this.data = data
        this.BPMN = BPMN
    }

    /**
     * Create element BPMN
     * @param data
     */
    createBpmnObject(data) {
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

    /**
     * Update Object BPMN
     * @param data
     */
    updateBpmnObject(data) {
        let bpmnObject = this.findElement(data.id)
        Object.assign(bpmnObject.attributes, data.attributes)
        data.incoming ? this.updateIncoming(bpmnObject, data.incoming) : null
        data.outgoing ? this.updateOutgoing(bpmnObject, data.outgoing) : null
    }

    /**
     * Update incoming element
     * @param bpmnObject
     * @param incoming
     */
    updateIncoming(bpmnObject, incoming) {
        let element = this.findElementsInBpmnObject(bpmnObject, "name", "incoming")
        if (element) {
            let text = this.findElementsInBpmnObject(bpmnObject, "type", "text")
            if (text) {
                text.text = incoming
            }
        } else {
            bpmnObject.elements.push({
                elements: [{
                    text: incoming,
                    type: "text"
                }],
                name: this.BPMN.BPMNDefinitions.getmodel('incoming'),
                type: "element"
            })
        }
    }

    /**
     * Update outgoing element
     * @param bpmnObject
     * @param outgoing
     */
    updateOutgoing(bpmnObject, outgoing) {
        let element = this.findElementsInBpmnObject(bpmnObject, "name", "outgoing")
        if (element) {
            let text = this.findElementsInBpmnObject(bpmnObject, "type", "text")
            if (text) {
                text.text = outgoing
            }
        } else {
            bpmnObject.elements.push({
                elements: [{
                    text: outgoing,
                    type: "text"
                }],
                name: this.BPMN.BPMNDefinitions.getmodel('outgoing'),
                type: "element"
            })
        }
    }

    findElementsInBpmnObject(bpmnObject, key, value) {
        let response
        _.each(bpmnObject.elements, (el) => {
            if (el[key].indexOf(value) > 0) {
                response = el
            }
        })
        return response
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

    updateTask(data) {
        //Get the node names
        let scriptNodeName = this.BPMN.BPMNDefinitions.getmodel('script');
        let scriptFormatAttrName = this.BPMN.BPMNDefinitions.getnonamespace('scriptFormat');
        let scriptRefAttrName = this.BPMN.BPMNDefinitions.getpm('scriptRef');
        let scriptConfigurationAttrName = this.BPMN.BPMNDefinitions.getpm('scriptConfiguration');
        let formRefAttrName = this.BPMN.BPMNDefinitions.getpm('formRef');
        //Find the task
        let task = this.findElement(data.id);
        //Find the script or create one
        let script = null;
        _.each(task.elements, (node) => {
            if (node.name === scriptNodeName) {
                script = node;
            }
        });
        if (!script) {
            script = {
                type: 'element',
                name: scriptNodeName
            };
            task.elements.push(script);
        }
        //Set the task name
        if (data.name !== undefined) {
            task.attributes.name = data.name;
        }
        //Change the node type
        if (data.$type !== undefined) {
            task.name = this.BPMN.BPMNDefinitions.getmodel(data.$type);
        }
        //Set the task form
        if (data.formRef !== undefined) {
            task.attributes[formRefAttrName] = data.formRef;
        }
        //Set the notifyAfterRouting
        if (data.notifyAfterRouting !== undefined) {
            task.attributes.notifyAfterRouting = data.notifyAfterRouting;
        }
        //Set the notifyToRequestCreator
        if (data.notifyToRequestCreator !== undefined) {
            task.attributes.notifyToRequestCreator = data.notifyToRequestCreator;
        }
        //Set the dueDate
        if (data.dueDate !== undefined) {
            task.attributes.dueDate = data.dueDate;
        }
        //Set the code
        if (data.code !== undefined) {
            script.elements = [
                {
                    type: 'cdata',
                    cdata: data.code
                }
            ];
        }
        //Set the scriptFormat
        if (data.scriptFormat !== undefined) {
            task.attributes[scriptFormatAttrName] = data.scriptFormat;
        }
        //Set the scriptRef
        if (data.scriptRef !== undefined) {
            task.attributes[scriptRefAttrName] = data.scriptRef;
        }
        //Set the scriptConfiguration
        if (data.scriptConfiguration !== undefined) {
            task.attributes[scriptConfigurationAttrName] = data.scriptConfiguration;
        }
    }
}
