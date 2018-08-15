import {JointElements} from "../jointElements"
import {Shape} from "../Shape"
import joint from "jointjs"
/**
 * Group class
 */
export default class extends Shape {
    constructor(options, graph, paper) {
        debugger
        console.log("asdasd")
        super(graph, paper)
        this.shapeEmbed = null
        this.options = {
            id: null,
            bounds: {
                x: null,
                y: null,
                width: 120,
                height: 30
            },
            attributes: {}
        }
        this.config(options)
        this.configBounds(options.bounds)
    }

    /**
     * Render the Group Based in options config
     */
    render() {
        this.shape = new JointElements.TextAnnotation({})
        this.shape.resize(this.options.bounds.width, this.options.bounds.height)
        this.shape.position(this.options.bounds.x, this.options.bounds.y)
        this.shape.attr('label/text', 'text annotation')
        this.shape.addTo(this.graph)
    }
}
