import {JointElements} from "../jointElements"
import {Shape} from "../Shape"
/**
 * DataObject class
 */
export default class extends Shape {
    constructor(options, graph, paper) {
        super(graph, paper)
        this.options = {
            id: null,
            x: null,
            y: null,
            width: 30,
            height: 40
        }
        this.config(options)
    }

    /**
     * Render the DataObject Based in options config
     */
    render() {
        this.shape = new JointElements.DataObject();
        this.shape.position(this.options.x, this.options.y);
        this.shape.resize(this.options.width, this.options.height);
        this.shape.addTo(this.graph);
    }
}
