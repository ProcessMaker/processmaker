import joint from "jointjs"
import actions from "../../actions"
import EventBus from "../../lib/event-bus"
/**
 * Flow class
 */
export class Flow {
    constructor(options, graph, paper) {
        this.source = this.options.source
        this.target = this.options.target
        this.graph = graph
        this.paper = paper
        this.shape = null
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
        link.source(this.options.source);
        link.target(this.options.source);
        link.addTo(this.graph);
    }

    /**
     * Return the object snapsvg
     * @returns {*}
     */
    getShape() {
        return this.shape;
    }
}
