import {Shape} from "../shape"
import actions from "../../actions"
import EventBus from "../../lib/event-bus"
/**
 * Task Shape class
 */
export class TaskShape extends Shape {
    constructor(svg) {
        super(svg);
        this.id = null;
        this.options = {
            id: null,
            x: null,
            y: null,
            scaleX: 100,
            scaleY: 80,
            rounded: 10,
            attr: {
                fill: "#FFF",
                stroke: "#000",
                strokeWidth: 2
            }
        };
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
        this.shape.add(this.svg.rect(
            this.options.x,
            this.options.y,
            this.options.scaleX,
            this.options.scaleY,
            this.options.rounded
        ).attr(this.options.attr));
        this.shape.drag(this.onMove(), this.onDragStart(), this.onDragEnd())
    }

    /**
     * Emit a message to crown to display
     */
    showCrown() {
        let dx = 105
        let dy = 10
        let action = actions.designer.crown.show({
            y: this.options.y + this.svg.node.getBoundingClientRect().top - dy,
            x: this.options.x + this.svg.node.getBoundingClientRect().left + dx
        })
        EventBus.$emit(action.type, action.payload)
    }
}
