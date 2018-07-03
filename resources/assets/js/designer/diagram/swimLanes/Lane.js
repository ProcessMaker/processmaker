import {JointElements} from "../jointElements"
import {Shape} from "../Shape"
/**
 * Pool class
 */
export default class extends Shape {
    constructor(options, graph, paper, parent) {
        super(graph, paper)
        this.isContainer = true
        this.type = "lane"
        this.options = {
            id: null,
            x: null,
            y: null,
            width: 600,
            height: 150
        }
        this.setParent(parent)
        this.config(options)
    }

    /**
     * Render the Pool Based in options config
     */
    render() {
        this.shape = new JointElements.Lane();
        this.shape.position(this.options.x, this.options.y);
        this.shape.resize(this.options.width, this.options.height);
        this.shape.addTo(this.graph);
    }
}
