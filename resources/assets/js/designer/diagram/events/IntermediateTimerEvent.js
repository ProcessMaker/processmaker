import {JointElements} from "../jointElements"
import {Shape} from "../Shape"
/**
 * IntermediateTimerEvent class
 */
export class IntermediateTimerEvent extends Shape {
    constructor(options, graph, paper) {
        super(graph, paper)
        this.options = {
            id: null,
            x: null,
            y: null,
            width: 40,
            height: 40,
            rounded: 10,
            attr: {
                fill: "#EDFFFC",
                stroke: "#00BF9C"
            }
        }
        this.config(options)
    }

    /**
     * Render the IntermediateTimerEvent Based in options config
     */
    render() {
        this.shape = new JointElements.IntermediateTimerEvent();
        this.shape.position(this.options.x, this.options.y);
        this.shape.resize(this.options.width, this.options.height);
        this.shape.addTo(this.graph);
    }
}
