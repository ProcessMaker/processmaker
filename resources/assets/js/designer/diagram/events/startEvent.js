import joint from "jointjs"
import actions from "../../actions"
import EventBus from "../../lib/event-bus"
/**
 * StartEvent class
 */
export class StartEvent {
    constructor(options, graph, paper) {
        this.options = {
            id: null,
            x: null,
            y: null,
            width: 40,
            height: 40,
            rounded: 10,
            attr: {
                fill: "#FFF",
                stroke: "#000",
                strokeWidth: 2
            }
        }
        this.config(options)
        this.graph = graph
        this.paper = paper
        this.shape = null
    }

    /**
     * Merge options default with options from arguments
     * @param options
     * @returns {TaskShape}
     */
    config(options) {
        this.options = Object.assign({}, this.options, options);
        return this;
    }

    /**
     * Render the StartEvent Based in options config
     */
    render() {
        this.shape = new joint.shapes.standard.Circle();
        this.shape.position(this.options.x, this.options.y);
        this.shape.resize(this.options.width, this.options.height);
        this.shape.attr({
            body: {},
            label: {}
        });
        this.shape.addTo(this.graph);
    }

    /**
     * Emit a message to crown to display
     */
    showCrown() {
        let diffDy = -6
        let action = actions.designer.crown.show({
            y: this.options.y + diffDy,
            x: this.options.x + this.options.width
        })
        EventBus.$emit(action.type, action.payload)
    }

    /**
     * This method hides the crown of shape
     */
    hideCrown() {
        let action = actions.designer.crown.hide()
        EventBus.$emit(action.type, action.payload)
    }

    /**
     * Return the object jointjs
     * @returns {*}
     */
    getShape() {
        return this.shape;
    }
}
