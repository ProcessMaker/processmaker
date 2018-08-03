import actions from "../actions"
import EventBus from "../lib/event-bus"
import {JointElements} from "./jointElements"
import _ from "lodash"
/**
 * Shape class
 */
export class Shape {
    constructor(graph, paper) {
        this.graph = graph
        this.paper = paper
        this.isContainer = false
        this.shape = null
        this.parent = null
        this.type = "Shape"
    }

    /**
     * Merge options default with options from arguments
     * @param options
     * @returns {TaskShape}
     */
    config(options) {
        this.options = Object.assign({}, this.options, {
            id: options.id,
            type: options.type,
            eventDefinition: options.eventDefinition,
            attributes: options.attributes
        })
        return this
    }

    /**
     * Return shape's options
     * @param val
     * @returns {*}
     */
    getOptions(val) {
        if (val) {
            return this.options[val]
        }
        return this.options
    }

    configBounds(bounds) {
        this.options.bounds = Object.assign({}, this.options.bounds, bounds);
    }

    configAttributes(attr) {
        this.options.attributes = Object.assign({}, this.options.attributes, attr);
    }

    updateBounds(bounds) {
        this.options.bounds = Object.assign({}, this.options.bounds, bounds);
        this.updateBpmn()
        this.updateFlows()
    }

    /**
     * Emit a message to crown to display
     */
    showCrown() {
        let diffDy = -6
        let action = actions.designer.crown.show({
            y: this.options.bounds.y + diffDy,
            x: this.options.bounds.x + this.options.bounds.width
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

    /**
     * Set the parent in this Shape
     * @param parent
     * @returns {Shape}
     */
    setParent(parent) {
        this.parent = parent
        return this
    }

    /**
     * Return the type of shape
     * @param parent
     * @returns {string}
     */
    getType() {
        return this.type
    }

    /**
     * Reset the vertices in link
     */
    resetFlows() {
        let links
        if (this.shape) {
            links = this.graph.getConnectedLinks(this.shape)
            _.each(links, (link) => {
                link.vertices([])
            })
        }
    }

    /**
     * Update Flows in BPMN Model
     */
    updateFlows() {
        let links
        let that = this
        if (this.shape) {
            links = this.graph.getConnectedLinks(this.shape)
            _.each(links, (link) => {
                let linkView = that.paper.findViewByModel(link)
                let arrVertices = that.getVertices(linkView)
                let action = actions.bpmn.flow.update({
                    id: link.id,
                    sourceRef: link.getSourceElement().get("id"),
                    targetRef: link.getTargetElement().get("id"),
                    bounds: arrVertices
                })
                EventBus.$emit(action.type, action.payload)
            })
        }
    }

    remove() {
        this.shape.remove()
        return this
    }

    /**
     * Update BPMN model
     */
    updateBpmn() {
        let action = actions.bpmn.shape.update(this.options)
        EventBus.$emit(action.type, action.payload)
    }

    /**
     * Create BPMN model
     */
    createBpmn() {
        let action = actions.bpmn.shape.create(this.options)
        EventBus.$emit(action.type, action.payload)
    }

    /**
     * Return array points from flow
     * @param linkView
     * @returns {Array}
     */
    getVertices(linkView) {
        let connection = linkView.getConnection()
        let arrayPoints = []
        _.each(connection.segments, (val) => {
            arrayPoints.push(val.end)
        })
        return arrayPoints
    }
}
