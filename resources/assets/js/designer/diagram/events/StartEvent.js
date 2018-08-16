import {JointElements} from "../jointElements"
import {Shape} from "../Shape"
/**
 * StartEvent class
 */
export default class extends Shape {
    constructor(options, graph, paper) {
        super(graph, paper)
        this.options = {
            id: null,
            type: "startEvent",
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
     * Render the StartEvent Based in options config
     */
    render() {
        this.shape = new JointElements.StartEvent({id: this.options.id})
        this.shape.position(this.options.bounds.x, this.options.bounds.y)
        this.shape.resize(this.options.bounds.width, this.options.bounds.height)
        this.shape.attr({
            label: {
                text: this.options.attributes.name
            }
        })
        this.shape.addTo(this.graph)
    }
}
