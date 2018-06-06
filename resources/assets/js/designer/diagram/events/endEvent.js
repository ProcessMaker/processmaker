import joint from "jointjs"
import actions from "../../actions"
import EventBus from "../../lib/event-bus"
/**
 * EndEvent class
 */
export class EndEvent {
    constructor(options, graph, paper) {
        this.options = {
            id: null,
            x: null,
            y: null,
            width: 38,
            height: 38,
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
     * Render the End Event
     */
    render() {
        this.shape = new joint.shapes.standard.Circle();
        this.shape.position(this.options.x, this.options.y);
        this.shape.resize(this.options.width, this.options.height);
        this.shape.attr({
            body: {
                strokeWidth: 5
            }
        });
        this.shape.addTo(this.graph);
    }

    /**
     * Emit a message to crown to display
     */
    showCrown() {
        let diffDy = -6
        let diffDx = 3
        let action = actions.designer.crown.show({
            y: this.options.y + diffDy,
            x: this.options.x + this.options.width + diffDx
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
