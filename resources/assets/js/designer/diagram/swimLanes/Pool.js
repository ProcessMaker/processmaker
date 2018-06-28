import {JointElements} from "../jointElements"
import {Shape} from "../Shape"
import {Elements} from "../elements"
/**
 * Pool class
 */
export class Pool extends Shape {
    constructor(options, graph, paper) {
        super(graph, paper)
        this.isContainer = true
        this.lanes = []
        this.type = "Pool"
        this.options = {
            id: null,
            x: null,
            y: null,
            width: 700,
            height: 250
        }
        this.heightLane = 250
        this.config(options)
    }

    /**
     * Render the Pool Based in options config
     */
    render() {
        this.shape = new JointElements.Pool();
        this.shape.position(this.options.x, this.options.y);
        this.shape.resize(this.options.width, this.options.height);
        this.shape.addTo(this.graph);
        let lane = this.firstLane()
        lane.render()
        this.shape.embed(lane.shape)
    }

    /**
     * Create the first lane in pool
     * @returns {*}
     */
    firstLane() {
        let dx = this.options.width * 0.07
        let lane = this.createShapeLane({
            x: this.options.x + dx,
            y: this.options.y,
            width: this.options.width - dx,
            height: this.options.height

        })
        return lane
    }

    /**
     * Create the object jointjs for lane
     * @param options
     */
    createShapeLane(options) {
        let lane = new Elements["Lane"](
            {
                x: options.x,
                y: options.y,
                width: options.width,
                height: options.height
            },
            this.graph,
            this.paper,
            this
        );
        this.lanes.push(lane)
        return lane
    }

    /**
     * Add lane in Pool
     * @returns {*}
     */
    createLane() {
        this.options.height += this.heightLane
        this.shape.resize(this.options.width, this.options.height)
        let dx = this.options.width * 0.07
        let lane = this.createShapeLane({
            x: this.options.x + dx,
            y: this.options.y + this.lanes.length * this.heightLane,
            width: this.options.width - dx,
            height: this.heightLane
        })
        lane.render()
        this.shape.embed(lane.shape)
        return lane
    }

    /**
     * Emit a message to crown to display
     */
    showCrown() {

    }
}
