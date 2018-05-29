import {Shape} from "../shape"
import Crown from "../../components/crown.vue"
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

    createCrown() {
        var ComponentClass = Vue.extend(Crown)
        var instance = new ComponentClass({
            data: {
                top: this.options.y + this.svg.node.getBoundingClientRect().top,
                left: this.options.x + this.svg.node.getBoundingClientRect().left + 105
            }
        })
        instance.$mount() // pass nothing
        $(".svg-container").append(instance.$el)
        this.crown = instance
    }
}
