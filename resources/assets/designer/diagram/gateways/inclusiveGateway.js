import {GatewayShape} from "./gatewayShape"

/**
 * Inclusive Gateway Class
 */
export class InclusiveGateway extends GatewayShape {
    constructor(options, svg) {
        super(svg);
        this.config(options)
    }

    /**
     * Return the object snap.svg for the inclusive gateway shape
     */
    getBase() {
        const my = this.options.y + 20
        let base = this.svg.circle(+this.options.x, +my, 8).attr(this.options.attr);
        return base;
    }
}
