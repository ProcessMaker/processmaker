import {JointElements} from "../jointElements"
import {Shape} from "../Shape"
/**
 * ExclusiveGateway class
 */
export default class extends Shape {
    constructor(options, graph, paper) {
        super(graph, paper)
        this.options = {
            id: null,
            x: null,
            y: null
        }
        this.config(options)
        //Force in width & height
        this.config({
            width: 40,
            height: 40
        })
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
