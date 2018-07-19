import {JointElements} from "../jointElements"
import {Shape} from "../Shape"
/**
 * IntermediateEmailEvent class
 */
export default class extends Shape {
    constructor(options, graph, paper) {
        super(graph, paper)
        this.options = {
            id: null,
            bounds: {
                x: null,
                y: null,
                width: null,
                height: null
            }

        }
        this.config(Object.assign({}, options, {
            type: "intermediateThrowEvent",
            definition: options.type
        }))
        this.configBounds({
            width: 40,
            height: 40
        })
    }

    /**
     * Render the IntermediateEmailEvent Based in options config
     */
    render() {
        this.shape = new JointElements.MessageEventDefinition();
        this.shape.position(this.options.bounds.x, this.options.bounds.y)
        this.shape.resize(this.options.bounds.width, this.options.bounds.height)
        this.shape.addTo(this.graph)
    }
}
