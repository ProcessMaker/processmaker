import {JointElements} from "../jointElements"
import {Shape} from "../Shape"
/**
 * InclusiveGateway class
 */
export default class extends Shape {
    constructor(options, graph, paper) {
        super(graph, paper)
        this.options = {
            id: null,
            type: "inclusiveGateway",
            bounds: {
                x: null,
                y: null,
                width: 40,
                height: 40
            }
        }
        this.config(options)
        this.configBounds(options.bounds)
    }

    /**
     * Render the InclusiveGateway Based in options config
     */
    render() {
        this.shape = new JointElements.InclusiveGateway({id: this.options.id})
        this.shape.position(this.options.bounds.x, this.options.bounds.y)
        this.shape.resize(this.options.bounds.width, this.options.bounds.height)
        this.shape.addTo(this.graph)
    }
}
