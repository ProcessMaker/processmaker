import {Shape} from "../shape";
import actions from "../../actions"
import EventBus from "../../lib/event-bus"
/**
 * StartEventShape
 */

export class EventShape extends Shape {
    constructor(svg) {
        super(svg);
    }

    /**
     * Load element in SVG Object
     * @param path
     * @param options
     */
    loadElement(path, options) {
        return this.svg.path(path)
            .transform(`${options.scale}, ${options.x}, ${options.y}`)
            .attr(options.attr);
    }

    /**
     * Merge local options with arguments options
     * @param options
     * @returns {EventShape}
     */
    config(options) {
        this.options = Object.assign({}, this.options, options);
        return this;
    }

    /**
     * Render the element in SVG
     */
    render() {
        let element = this.getBase(
            this.options.$type,
            this.options.eventDefinitions ? this.options.eventDefinitions[0].$type : this.options.marker
        );

        let elementFill = this.getBaseFill();

        let shape = this.loadElement(
            element.path,
            element.options
        );
        let shapeFill = this.svg.circle(elementFill.x + this.options.scale / 2, elementFill.y + this.options.scale / 2, this.options.scale / 2).attr(elementFill.attr);

        let group = this.svg.group(shapeFill, shape);
        group.attr({
            id: this.options.id
        });
        this.shape.add(group);
        this.shape.drag(this.onMove(), this.onDragStart(), this.onDragEnd())
    }

    /**
     * Emit a message to crown to display
     */
    showCrown() {
        let evWidthDefault = 50
        let action = actions.designer.crown.show({
            y: this.options.y + this.svg.node.getBoundingClientRect().top,
            x: this.options.x + this.svg.node.getBoundingClientRect().left + evWidthDefault
        })
        EventBus.$emit(action.type, action.payload)
    }
}
