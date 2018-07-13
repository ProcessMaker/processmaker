import joint from "jointjs"
import Flow from "./Flow"
/**
 * Flow class
 */
export default class extends Flow {
    constructor(options, graph, paper) {
        super(graph, paper)
        this.graph = graph
        this.paper = paper
        this.shape = null
        this.options = options
    }

    /**
     * Render the Flow
     */
    render() {
        this.shape = new joint.shapes.standard.Link()
        this.shape.vertices(this.formatWayPoints(this.options.wayPoints))
        this.shape.source(this.options.source.getShape())
        this.shape.target(this.options.target.getShape())
        this.shape.attr('line/stroke-dasharray', '3,5');
        this.shape.router('orthogonal', {
            elementPadding: 10
        })
        this.shape.addTo(this.graph)
        this.createTools()
    }
}
