import {JointElements} from "../jointElements"
import {Shape} from "../Shape"
import {IntermediateThrowEvent} from "./intermediateThrowEvent/"
import _ from "lodash"
/**
 * IntermediateTimerEvent class
 */
export default class {
    constructor(options, graph, paper) {
        this.adapter = new IntermediateThrowEvent[options["eventDefinition"]](options, graph, paper)
    }

    render() {
        this.adapter.render()
    }

    getShape() {
        return this.adapter.shape
    }

    createBpmn() {
        return this.adapter.createBpmn()
    }

    updateBounds(data) {
        return this.adapter.updateBounds(data)
    }

    resetFlows(data) {
        return this.adapter.resetFlows(data)
    }

    showCrown() {
        return this.adapter.showCrown()
    }

    hideCrown() {
        return this.adapter.hideCrown()
    }

    getType() {
        return this.adapter.getType()
    }

    getOptions(val) {
        return this.adapter.getOptions(val)
    }

}