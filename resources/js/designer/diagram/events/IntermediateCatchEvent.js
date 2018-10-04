import {JointElements} from "../jointElements"
import {Shape} from "../Shape"
import {IntermediateCatchEvent} from "./intermediateCatchEvent/"
import _ from "lodash"
/**
 * IntermediateCatchEvent class
 */
export default class {
    constructor(options, graph, paper) {
        this.adapter = new IntermediateCatchEvent[options["eventDefinition"]](options, graph, paper)
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

    getId(val) {
        return this.adapter.getId(val)
    }

    updateOptions(options) {
        return this.adapter.updateOptions(options)
    }
}
