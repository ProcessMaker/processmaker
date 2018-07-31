import {JointElements} from "../jointElements"
import {Shape} from "../Shape"
/**
 * EndEmailEvent class
 */
export default class extends Shape {
    constructor(options, graph, paper) {
        super(graph, paper)
        this.options = {
            id: null,
            type: "endEvent",
            bounds: {
                x: null,
                y: null,
                width: null,
                height: null
            }

        }
        this.config(options)
        options.bounds = Object.assign({}, options.bounds, {
            width: 40,
            height: 40
        })
        this.configBounds(options.bounds)
    }

    /**
     * Render the EndEmailEvent Based in options config
     */
    render() {
        this.shape = new JointElements.EndEmailEvent();
        this.shape.position(this.options.bounds.x, this.options.bounds.y);
        this.shape.resize(this.options.bounds.width, this.options.bounds.height);
        this.shape.addTo(this.graph);
    }
}
