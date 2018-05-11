import {EventShape} from "./eventShape";

/**
 * End Event Class
 */
export class EndEvent extends EventShape {
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
            "bpmn:EndEvent": {
                EMPTY: {
                    path: "m496 48c-203-1-394 153-437 351-41 174 33 368 181 470 143 103 348 111 497 15 150-91 238-275 210-449-26-181-170-339-350-376-33-7-67-11-101-11z m10 142c150-1 287 123 302 271 19 142-72 291-210 334-134 45-296-13-366-138-77-129-45-313 78-403 56-43 126-66 196-64z",
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
