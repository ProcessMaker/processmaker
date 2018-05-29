import {Shape} from "../shape"
import Crown from "../../components/crown.vue"
import Vue from "vue"
/**
 * Gateway Shape Class
 */
export class GatewayShape extends Shape {
    constructor(svg) {
        super(svg);
        this.options = {
            id: null,
            marker: "EMPTY",
            name: null,
            x: 100,
            y: 100,
            scaleX: 40,
            scaleY: 40,
            edgeLength: Math.sqrt(Math.pow(20, 2) + Math.pow(20, 2)),
            attr: {
                fill: "#FFF",
                stroke: "#000",
                strokeWidth: 2
            }
        };
    }

    /**
     * Merge local options with arguments options
     * @param options
     * @returns {EventShape}
     */
    config(options) {
        if (options) {
            this.options = Object.assign({}, this.options, options);
        }
        return this;
    }

    /**
     * Return the object base from border of gateway
     */
    getBaseBorder() {
        const base = this.svg
            .rect(this.options.x, this.options.y, this.options.edgeLength, this.options.edgeLength)
            .attr(this.options.attr)
            .transform(`r45,${this.options.x},${this.options.y}`);
        return base;
    }

    /**
     * function to render shape
     */
    render() {
        const baseBorder = this.getBaseBorder();
        const base = this.getBase();
        this.shape.add(baseBorder, base);
        this.shape.drag(this.onMove(), this.onDragStart(), this.onDragEnd())
    }

    /**
     * Create a crown for this shape
     */
    createCrown() {
        let ComponentClass = Vue.extend(Crown)
        let instance = new ComponentClass({
            data: {
                top: this.options.y + this.svg.node.getBoundingClientRect().top - 10,
                left: this.options.x + this.svg.node.getBoundingClientRect().left + 30
            }
        })
        instance.$mount() // pass nothing
        $(".svg-container").append(instance.$el)
        this.crown = instance
        return this.crown
    }
}
