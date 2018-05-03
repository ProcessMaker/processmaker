import {EventShape} from "./eventShape";

export class StartEvent extends EventShape {
    constructor (options, svg) {
        super(svg);
        this.type = options.type;
        this.name = options.name || "";
        this.options = options;
        options.attr = {
            fill: "#B4DCCB",
            stroke: "#018A4F",
            strokeWidth: 2
        };
        options.marker = "EMPTY";
        this.config(options);
    }
}
