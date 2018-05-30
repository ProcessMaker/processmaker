import {GatewayShape} from "./gatewayShape"

/**
 * Exclusive gateway Class
 */
export class ExclusiveGateway extends GatewayShape {
    constructor(options, svg) {
        super(svg);
        this.config(options)
    }

    /**
     * Return the object snap.svg for the Exclusive gateway shape
     */
    getBase() {
        const my = this.options.y + 20
        let base = this.svg.group(
            this.svg.polyline([+this.options.x - 8, +my - 8, +this.options.x + 8, +my + 8]),
            this.svg.polyline([+this.options.x - 8, +my + 8, +this.options.x + 8, +my - 8])
        ).attr(this.options.attr);
        return base;
    }
}
