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
        this.connectingFlow = null
    }

    createFromBPMN(bpmn) {
        let that = this
        _.forEach(bpmn.shapes, (el) => {
            that.createShape(el)
        });
        _.forEach(bpmn.links, (el) => {
            let source = this.findInCollectionById(el.sourceRef)
            let target = this.findInCollectionById(el.targetRef)
            this.connect(Object.assign({}, {source, target}, el))
        });
    }

    /**
     * Create a shape based in type
     * @param type
     * @param options
     */
    createShape(options) {
        let element
        if (Elements[options.type]) {
            switch (options.type) {
                case "sequenceflow":
                    this.createFlow(options)
                    break;
                case "lane":
                    let participant = this.verifyElementFromPoint({x: options.x, y: options.y}, "participant")
                    participant ? this.collection.push(participant.createLane()) : null
                    break;
                case "textannotation":
                    break;
                default:
                    if (Elements[options.type.toLowerCase()]) {
                        let participant = this.verifyElementFromPoint({x: options.x, y: options.y}, "participant")
                        element = new Elements[options.type.toLowerCase()](
                            options,
                            this.graph,
                            this.paper
                        );
                        element.render();
                        this.collection.push(element)
                        participant ? participant.shape.embed(element.shape) : null
                    }
                    if (options.type === "participant") {
                        this.collection = _.concat(element.lanes, this.collection);
                    }
                    break;
            }
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
            this.hideCrown()
            el.showCrown()
            this.selection = []
            this.selection.push(el)

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
            res.resetFlows()
        }
    }

    /**
     * Connect shapes source, target, and vertices
     * @param source
     * @param target
     */
    connect(options) {
        if (options.source != options.target && Validators.verifyConnectWith(options.source.getType(), options.target.getType())) {
            let flow = new Elements[options.type](options,
                this.graph,
                this.paper
            );
            flow.render()
        }
    }


    /**
     * Create flow from Crown
     * @param ev
     */
    createFlow(ev) {
        let elements = this.graph.findModelsFromPoint(ev)
        if (elements.length > 0) {
            let element = elements.pop()
            let target = this.findElementInCollection(element)
            this.connect({
                source: this.sourceShape,
                target,
                type: "sequenceflow"
            })

        }
        this.connectingFlow.remove()
        this.connectingFlow = null
        this.sourceShape = null
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
     *
     * @param element
     */
    findInCollectionById(id) {
        return _.find(this.collection, (o) => {
            return id === o.options.id
        })
    }

    /**
     * This method set source element to create flow
     * @param element
     */
    setSourceElement(ev) {
        this.sourceShape = this.selection.pop()
        this.connectingFlow = true
        this.connectingFlow = new joint.shapes.standard.Link()
        this.connectingFlow.source(this.sourceShape.getShape())
        this.connectingFlow.target(ev)
        this.connectingFlow.attr('line/stroke-dasharray', '3,5');
        this.connectingFlow.router('normal')
        this.connectingFlow.addTo(this.graph)
    }

    updateConnectingFlow(ev) {
        if (this.connectingFlow) {
            this.connectingFlow.target(ev)
        }
    }

    /**
     * Reset the builder
     */
    clear() {
        this.graph.clear()
        this.collection = []
        this.selection = []
    }


    /**
     * This method process the pointerdown event in paper jointjs
     * @param cellView
     * @param evt
     * @param x
     * @param y
     */
    pointerDown(cellView, evt, x, y) {
        var cell = cellView.model;
        if (!cell.get('embeds') || cell.get('embeds').length === 0) {
            cell.toFront();
        }
        if (cell.get('parent')) {
            this.graph.getCell(cell.get('parent')).unembed(cell);
        }
    }

    /**
     * This method process the pointerup event in paper jointjs
     * @param cellView
     * @param evt
     * @param x
     * @param y
     */
    pointerUp(cellView, evt, x, y) {
        let cell = cellView.model;
        let cellViewsBelow = this.paper.findViewsFromPoint(cellView.getBBox ? cellView.getBBox().center() : cell.getBBox().center());
        if (cellViewsBelow.length) {
            // Note that the findViewsFromPoint() returns the view for the `cell` itself.
            let cellViewBelow = _.find(cellViewsBelow, function (c) {
                return c.model.id !== cell.id
            });
            // Prevent recursive embedding.
            if (cellViewBelow && cellViewBelow.model.get('parent') !== cell.id) {
                let el = this.findElementInCollection(cellViewBelow, true)
                let elCell = this.findElementInCollection(cell)
                if (el && el.type == "participant" && elCell && !elCell.type != "participant") {
                    cellViewBelow.model.embed(cell)
                }
            }
        }
    }

    verifyElementFromPoint(point, type) {
        let that = this
        let response = null
        let elements = this.graph.findModelsFromPoint({x: point.x, y: point.y})
        if (elements.length > 0) {
            _.each(elements, (o) => {
                let el = that.findElementInCollection(o)
                if (el instanceof Elements[type]) {
                    response = el
                }
                return el instanceof Elements[type]
            })
        }
        return response
    }
}
