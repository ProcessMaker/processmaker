import {JointElements} from "../jointElements"
import {Shape} from "../Shape"
/**
 * ExclusiveGateway class
 */
export class ExclusiveGateway extends Shape {
    constructor(options, graph, paper) {
        super(graph, paper)
        this.options = {
            id: null,
            x: null,
            y: null,
            width: 40,
            height: 40
        }
        this.config(options)
    }

    /**
     * Render the ExclusiveGateway Based in options config
     */
    render() {
        this.shape = new JointElements.ExclusiveGateway();
        this.shape.position(this.options.x, this.options.y);
        this.shape.resize(this.options.width, this.options.height);
        this.shape.addTo(this.graph);
    }
}
