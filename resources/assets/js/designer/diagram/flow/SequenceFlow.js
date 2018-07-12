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
        this.setSource(this.options.source)
        this.setTarget(this.options.target)
        this.shape.router('orthogonal', {
            elementPadding: 10
        })
        this.shape.addTo(this.graph)
        this.createTools()
    }
}
