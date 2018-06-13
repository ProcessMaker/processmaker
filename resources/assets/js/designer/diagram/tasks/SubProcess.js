import {JointElements} from "../jointElements"
import {Shape} from "../Shape"
/**
 * SubProcess class
 */
export class SubProcess extends Shape {
    constructor(options, graph, paper) {
        super(graph, paper)
        this.options = {
            id: null,
            x: null,
            y: null,
            width: 120,
            height: 80
        }
        this.config(options)
    }

    /**
     * Render the SubProcess Based in options config
     */
    render() {
        this.shape = new JointElements.SubProcess();
        this.shape.position(this.options.x, this.options.y);
        this.shape.resize(this.options.width, this.options.height);
        this.shape.addTo(this.graph);
    }
}
