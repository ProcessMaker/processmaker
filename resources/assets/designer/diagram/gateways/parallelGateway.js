import {GatewayShape} from "./gatewayShape"

/**
 * Parallel Gateway Class
 */
export class ParallelGateway extends GatewayShape {
    constructor(options, svg) {
        super(svg);
        this.config(options)
    }

    /**
     * Return the object snap.svg for the parallel gateway shape
     */
    getBase() {
        const my = this.options.y + 20
        let base = this.svg.group(
            this.svg.polyline([+this.options.x - 12, +my, +this.options.x + 12, +my]),
            this.svg.polyline([+this.options.x, +my - 12, +this.options.x, +my + 12])
        ).attr(this.options.attr);
        return base;
    }
}
