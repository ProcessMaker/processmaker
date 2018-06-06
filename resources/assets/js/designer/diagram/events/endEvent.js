import joint from "jointjs"
import actions from "../../actions"
import EventBus from "../../lib/event-bus"
/**
 * Activity class
 */
export class EndEvent {
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
     * Render the activity Based in options config
     */
    render() {
        this.shape = new joint.shapes.standard.Path();
        this.shape.position(this.options.x, this.options.y);
        this.shape.resize(this.options.width, this.options.height);
        this.shape.attr({
            body: {
                fill: 'white', // white background
                refD: "m496 48c-176 0-345 113-412 276-70 161-34 362 89 487 119 128 314 175 477 115 169-58 294-224 301-403 12-176-92-351-250-428-62-31-132-47-201-47-1 0-3 0-4 0z m12 49c173 1 335 126 380 293 47 159-17 344-155 439-143 105-354 97-489-18-136-109-185-309-115-468 60-147 212-248 371-246 3 0 6 0 8 0z"
            }
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
     * Return the object snapsvg
     * @returns {*}
     */
    getShape() {
        return this.shape;
    }
}
