import {Elements} from "./elements";
import _ from "lodash";
import actions from "../actions/index";

export class Builder {
    constructor(svg, dispatcher) {
        this.svg = svg;
        this.selection = [];
        this.dispatcher = dispatcher;
    }

    /**
     * Create a shape based in type
     * @param type
     * @param options
     */
    createShape(type, options) {
        let element,
            defaultOptions = {
                $type: type,
                id: options.id,
                name: options.bpmnElement && options.bpmnElement.name ? options.bpmnElement.name : options.name ? options.name : "",
                moddleElement: options
            };
        defaultOptions = _.extend(defaultOptions, options);
        // Type Example - bpmn:StartEvent
        if (Elements[options.eClass]) {
            element = new Elements[options.eClass](
                defaultOptions,
                this.svg
            );
            element.render();
            element.getSnapObject().click(this.onClickShape(element));
            element.getSnapObject().dblclick(this.removeSelection(element));
        }
    }

    /**
     * onClick event for a shape
     * @param element
     * @returns {function(*)}
     */
    onClickShape(element) {
        let that = this;
        return () => {
            that.removeSelectionBorder();
            element.createSelectionBorder();
            that.selection.push(element);
        };
    }

    /**
     * Remove selection border of all shapes selected
     */
    removeSelectionBorder() {
        _.forEach(this.selection, (el) => {
            el.removeSelectionBorder();
        });
    }

    /**
     * Remove the shape selected
     * @param element
     * @returns {function(*)}
     */
    removeSelection(element) {
        return () => {
            element.remove()
        };
    }
}
