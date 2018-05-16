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
     * Remove this shape
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
}
