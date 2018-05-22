import {Shape} from "../shape";
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
        this.shape.drag((dx, dy) => {
                console.log("shape move")
                this.shape.attr({
                    transform: this.shape.data('origTransform') + ( this.shape.data('origTransform') ? "T" : "t") + [dx, dy]
                });
            }, () => {
                console.log("shape drag start")
                this.svg.shapeDrag = true
                this.shape.data('origTransform', this.shape.transform().local);
            },
            () => {
                this.svg.shapeDrag = null
                console.log("shape drag end")
            }
        )
    }
}
