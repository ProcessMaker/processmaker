import actions from "../actions"
import EventBus from "../lib/event-bus"
import {JointElements} from "./jointElements"
/**
 * Shape class
 */
export class Shape {
    constructor(graph, paper) {
        this.graph = graph
        this.paper = paper
        this.isContainer = false
        this.shape = null
        this.draggable = true
    }

    /**
     * Merge options default with options from arguments
     * @param options
     * @returns {TaskShape}
     */
    config(options) {
        this.options = Object.assign({}, this.options, options);
        return this;
    }

    /**
     * Emit a message to crown to display
     */
    showCrown() {
        let diffDy = -6
        let action = actions.designer.crown.show({
            y: this.options.y + diffDy,
            x: this.options.x + this.options.width
        })
        EventBus.$emit(action.type, action.payload)
    }

    /**
     * This method hides the crown of shape
     */
    hideCrown() {
        let action = actions.designer.crown.hide()
        EventBus.$emit(action.type, action.payload)
    }

    /**
     * Return the object jointjs
     * @returns {*}
     */
    getShape() {
        return this.shape;
    }

    /**
     * Unselect the shape
     */
    unselect() {
        this.hideCrown()
    }

    /**
     * Select the shape
     */
    select() {

    }
}
