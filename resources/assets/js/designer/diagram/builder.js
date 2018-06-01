import {Elements} from "./elements";
import _ from "lodash";
import actions from "../actions/index"

import EventBus from "../lib/event-bus"

export class Builder {
    constructor(svg) {
        this.svg = svg;
        this.selection = [];
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
        }
    }

    /**
     * onClick event for a shape
     * @param element
     * @returns {function(*)}
     */
    onClickShape(element) {
        let that = this;
        return (event) => {
            that.removeSelectionBorder();
            that.hideCrown();
            element.createSelectionBorder();
            element.showCrown()
            that.selection = [];
            that.selection.push(element);
            return false;
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
     * This method removes the crown in the selected shape
     */
    hideCrown() {
        _.forEach(this.selection, (el) => {
            el.hideCrown();
        });
    }

    /**
     * Remove the shape selected
     * @param element
     * @returns {function(*)}
     */
    removeSelection() {
        _.forEach(this.selection, (el) => {
            el.hideCrown();
            el.remove();
        });
    }
}
