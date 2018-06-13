import {JointElements} from "../jointElements"
import {Shape} from "../Shape"
/**
 * InclusiveGateway class
 */
export class InclusiveGateway extends Shape {
    constructor(options, graph, paper) {
        super(graph, paper)
        this.options = {
            id: null,
            x: null,
            y: null,
            width: 40,
            height: 40,
            rounded: 10
        }
        this.config(options)
    }

    /**
     * Render the InclusiveGateway Based in options config
     */
    render() {
        this.shape = new JointElements.InclusiveGateway();
        this.shape.position(this.options.x, this.options.y);
        this.shape.resize(this.options.width, this.options.height);
        this.shape.addTo(this.graph);
    }
}
