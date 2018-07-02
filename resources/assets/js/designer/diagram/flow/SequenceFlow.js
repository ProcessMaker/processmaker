import joint from "jointjs"
import actions from "../../actions"
import EventBus from "../../lib/event-bus"
/**
 * Flow class
 */
export default class {
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
        this.shape = new joint.shapes.standard.Link({
            router: {name: 'manhattan'}
        });
        this.shape.source(this.options.source.getShape());
        this.shape.target(this.options.target.getShape());
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
