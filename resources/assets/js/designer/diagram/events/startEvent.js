import {EventShape} from "./eventShape";

export class StartEvent extends EventShape {
    constructor(options, svg) {
        super(svg);
        this.options = {
            id: null,
            marker: "EMPTY",
            name: null,
            x: 100,
            y: 100,
            scale: 40
        };
        this.config(options);
    }

    /**
     * Get shape base to render
     * @param type
     * @param marker
     */
    getBase(type, marker) {
        const x = this.options.x + this.options.x * 0.043;
        const y = this.options.y + this.options.y * 0.043;
        const bases = {
            "bpmn:StartEvent": {
                EMPTY: {
                    path: "m496 48c-176 0-345 113-412 276-70 161-34 362 89 487 119 128 314 175 477 115 169-58 294-224 301-403 12-176-92-351-250-428-62-31-132-47-201-47-1 0-3 0-4 0z m12 49c173 1 335 126 380 293 47 159-17 344-155 439-143 105-354 97-489-18-136-109-185-309-115-468 60-147 212-248 371-246 3 0 6 0 8 0z",
                    options: {
                        x,
                        y,
                        scale: "s0.04",
                        attr: {
                            stroke: "#018A4F",
                            strokeWidth: 0
                        }
                    }
                }
            }
        };
        return bases[type][marker];
    }

    /**
     * Get shape fill to render in SVG
     * @returns {{x: number, y, attr: {fill: string}}}
     */
    getBaseFill() {
        const baseFill = {
            x: this.options.x,
            y: this.options.y,
            attr: {
                fill: "#FFFFFF"
            }
        };
        return baseFill;
    }
}
