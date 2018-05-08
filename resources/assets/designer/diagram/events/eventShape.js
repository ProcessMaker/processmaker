/**
 * StartEventShape
 */

export class EventShape {
    constructor (svg) {
        this.svg = svg;
        this.shape = this.svg.group();
        this.x = 100;
        this.y = 100;
        this.scale = 40;
        this.attr = {
            fill: "#B4DCCB",
            stroke: "#018A4F",
            strokeWidth: 2
        };
        this.animateOptions = {r: this.scale};
        this.animationTime = 10;
        this.inputConnectors = new Map();
        this.outputConnectors = new Map();
    }

    /**
     * Load element in SVG Object
     * @param path
     * @param options
     */
    loadElement (path, options) {
        return this.svg.path(path)
            .transform(`${options.scale}, ${options.x}, ${options.y}`)
            .attr(options.attr);
    }

    /**
     * Init config
     * @param options
     */
    config (options) {
        this.x = +options.x - 4;
        this.y = +options.y - 4;
        this.scale = options.width || this.scale;
        this.options = options;
        this.attr = options.attr || this.attr;
        this.attr.id = options.id;
        this.animateOptions.r = +options.width / 2 || this.scale;
        this.animateOptions = options.animateOptions || this.animateOptions;
        this.animationTime = options.animationTime || this.animationTime;
    }

    /**
     * Get shape base to render
     * @param type
     * @param marker
     */
    getBase (type, marker) {
        const x = this.x + this.x * 0.043;
        const y = this.y + this.y * 0.043;
        const bases = {
            "bpmn:StartEvent": {
                EMPTY: {
                    path: "m496 48c-176 0-345 113-412 276-70 161-34 362 89 487 119 128 314 175 477 115 169-58 294-224 301-403 12-176-92-351-250-428-62-31-132-47-201-47-1 0-3 0-4 0z m12 49c173 1 335 126 380 293 47 159-17 344-155 439-143 105-354 97-489-18-136-109-185-309-115-468 60-147 212-248 371-246 3 0 6 0 8 0z",
                    options: {
                        x,
                        y,
                        scale: "s0.04",
                        attr: {
                            stroke: "#000",
                            strokeWidth: 0
                        }
                    }
                }
            }
        };
        return bases[type][marker];
    }

    /**
     * Render the element in SVG
     */
    render () {
        let element = this.getBase(
            this.options.$type,
            this.options.eventDefinitions ? this.options.eventDefinitions[0].$type : this.options.marker
        );
        let rec = this.svg.circle(this.x + this.scale / 2, this.y + this.scale / 2, this.scale / 2).attr({fill: "#FFFFFF"});
        let border = this.loadElement(
            element.path,
            element.options
        );
        let group = this.svg.group(rec, border);
        group.attr({
            id: this.options.id
        });
        this.shape.add(group);
        this.shape.drag();
    }
}
