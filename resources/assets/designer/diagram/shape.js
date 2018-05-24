import actions from "../actions/"
/**
 * Class Shape - Base Class
 */

export class Shape {
    constructor(svg) {
        this.svg = svg;
        this.shape = this.svg.group();
        this.selectionBorder = null;
    }

    /**
     * Draw selection border in shape
     */
    createSelectionBorder() {
        const shapeBox = this.shape.getBBox();
        this.selectionBorder = this.svg.rect(shapeBox.x - 3, shapeBox.y - 3, shapeBox.width + 6, shapeBox.height + 6, 5).attr({
            fill: "none",
            stroke: "#1fb64b",
            strokeWidth: 2,
            strokeDasharray: "3px,7px",
            strokeLinecap: "square"
        });
    }

    /**
     * Remove the selection border
     */
    removeSelectionBorder() {
        if (this.selectionBorder) {
            this.selectionBorder.remove();
            this.selectionBorder = null;
        }
    }

    /**
     * This method remove the shape
     * @returns {Shape}
     */
    remove() {
        this.shape.remove();
        this.shape = null;
        this.removeSelectionBorder();
        return this;
    }

    /**
     * Return the object snapsvg
     * @returns {*}
     */
    getSnapObject() {
        return this.shape;
    }

    /**
     * This method updates the position of shape in movement
     * @param dx
     * @param dy
     */
    onMove() {
        return (dx, dy) => {
            this.shape.attr({
                transform: this.shape.data("origTransform") + (this.shape.data("origTransform") ? "T" : "t") + [dx, dy]
            });
        };
    }

    /**
     * This method is execute on DragStart Event
     */
    onDragStart() {
        return (ev) => {
            let action = actions.designer.drag.shape.start(ev)
            Dispatcher.$emit(action.type, action.payload)
            this.shape.data("origTransform", this.shape.transform().local);
        };
    }

    /**
     * This method is execute on DragEnd Event
     */
    onDragEnd() {
        return (ev) => {
            let action = actions.designer.drag.shape.end(ev)
            Dispatcher.$emit(action.type, action.payload)
        };
    }
}
