import {Elements} from "./elements";
import _ from "lodash";
import actions from "../actions/index"
import joint from "jointjs"
import EventBus from "../lib/event-bus"
import {Validators} from './flow/Validators'

export class Builder {
    constructor(graph, paper) {
        this.graph = graph
        this.paper = paper
        this.collection = []
        this.selection = []
        this.targetShape = null
        this.sourceShape = null
    }

    /**
     * Create a shape based in type
     * @param type
     * @param options
     */

    createShape(options) {
        let element
        // Type Example - bpmn:StartEvent
        if (Elements[options.type.toLowerCase()]) {
            element = new Elements[options.type.toLowerCase()](
                options,
                this.graph,
                this.paper
            );
            element.render();
            this.collection.push(element)
            if (options.eClass === "Pool") {
                this.collection = _.concat(element.lanes, this.collection);
            }
        } else {
            let pool = this.verifyElementFromPoint({x: defaultOptions.x, y: defaultOptions.y}, "Pool")
            pool ? this.collection.push(pool.createLane()) : null
        }
    }

    /**
     * onClick event for a shape
     * @param element
     * @returns {function(*)}
     */
    onClickShape(elJoint) {
        let el = this.findElementInCollection(elJoint, true)
        if (el) {
            if (this.sourceShape) {
                this.connect(this.sourceShape, el)
            } else {
                this.hideCrown();
                el.showCrown()
                el.select()
                this.selection = [];
                this.selection.push(el);
            }
        }
        return false;
    }

    /**
     * onClick event for canvas
     * @param element
     * @returns {function(*)}
     */
    onClickCanvas() {
        this.hideCrown()
    }


    /**
     * Remove selection border of all shapes selected
     */
    removeSelectionBorder() {
        _.forEach(this.selection, (el) => {
            el.removeSelectionBorder();
        });
    }

    /**
     * This method removes the crown in the selected shape
     */
    hideCrown() {
        _.forEach(this.selection, (el) => {
            el.hideCrown();
        });
    }

    /**
     * Remove the shape selected
     * @param element
     * @returns {function(*)}
     */
    removeSelection() {
        _.forEach(this.selection, (el) => {
            el.hideCrown();
            el.remove();
        });
    }

    /**
     * Update the position in Shapes
     * @param element
     */
    updatePosition(element) {
        this.hideCrown()
        let res = _.find(this.collection, (o) => {
            return element.id === o.shape.id
        })
        if (res) {
            res.config(element.get("position"))
        }
    }

    /**
     * Connect shapes
     * @param source
     * @param target
     */
    connect(source, target) {
        if (source != target && Validators.verifyConnectWith(source.getType(), target.getType())) {
            let flow = new Elements["Flow"]({
                    source,
                    target
                },
                this.graph,
                this.paper
            );
            flow.render()
            source.hideCrown()
            this.sourceShape = null
        }
    }

    /**
     * This method find element joint js in collection
     * @param element
     */
    findElementInCollection(element, inModel = false) {
        return _.find(this.collection, (o) => {
            if (inModel) {
                return element.model.id === o.shape.id
            } else {
                return element.id === o.shape.id
            }
        })
    }

    /**
     * This method set source element to create flow
     * @param element
     */
    setSourceElement() {
        this.sourceShape = this.selection.pop()
    }

    /**
     * Reset the builder
     */
    clear() {
        this.graph.clear()
        this.collection = []
        this.selection = []
    }

}
