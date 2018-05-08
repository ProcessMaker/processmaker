/**
 * Task Shape class
 */
export class TaskShape {
    constructor(svg) {
        this.id = null
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
        }
        this.svg = svg
        this.shape = this.svg.group()
    }

    config(options) {
        this.options = Object.assign({}, this.options, options)
        return this;
    }

    render() {
        this.shape.add(this.svg.rect(
            this.options.x,
            this.options.y,
            this.options.scaleX,
            this.options.scaleY,
            this.options.rounded
        ).attr(this.options.attr));
        this.shape.drag();
    }
}
