import {TaskShape} from "./taskShape";
/**
 * Activity class
 */
export class Task extends TaskShape {
    constructor (options, svg) {
        super(svg);
        this.config(options);
    }
}
