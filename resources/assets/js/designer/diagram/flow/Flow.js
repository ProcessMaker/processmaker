import joint from "jointjs"
import actions from "../../actions"
import EventBus from "../../lib/event-bus"
/**
 * Flow class
 */
export class Flow {
    constructor(options, graph, paper) {
        this.graph = graph
        this.paper = paper
        this.shape = null
        this.options = options
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
     * Render the Flow
     */
    render() {
        debugger
        this.shape = new joint.shapes.standard.Link({
            router: {name: 'manhattan'}
        });
        this.shape.source(this.options.source);
        this.shape.target(this.options.target);
        this.shape.addTo(this.graph);
    }

    /**
     * Return the object joint
     * @returns {*}
     */
    getShape() {
        return this.shape;
    }
}
