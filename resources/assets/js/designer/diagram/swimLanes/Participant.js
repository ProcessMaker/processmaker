import {JointElements} from "../jointElements"
import {Shape} from "../Shape"
import {Elements} from '../elements'
/**
 * Pool class
 */
export default class extends Shape {
    constructor(options, graph, paper) {
        super(graph, paper)
        this.lanes = []
        this.options = {
            id: null,
            type: "participant",
            bounds: {
                x: null,
                y: null,
                width: 700,
                height: 250
            }

        }
        this.config(options)
        this.configBounds(options.bounds)
        this.heightLane = this.options.height
    }

    /**
     * Render the Pool Based in options config
     */
    render() {
        this.shape = new JointElements.Participant();
        this.shape.position(this.options.bounds.x, this.options.bounds.y);
        this.shape.resize(this.options.bounds.width, this.options.bounds.height);
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
        let dx = 25
        let lane = this.createShapeLane({
            x: this.options.bounds.x + dx,
            y: this.options.bounds.y,
            width: this.options.bounds.width - dx,
            height: this.options.bounds.height

        })
        return lane
    }

    /**
     * Create the object jointjs for lane
     * @param options
     */
    createShapeLane(options) {
        let lane = new Elements["lane"]({
                bounds: {
                    x: options.x,
                    y: options.y,
                    width: options.width,
                    height: options.height
                }
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
        this.options.bounds.height += this.heightLane
        this.shape.resize(this.options.bounds.width, this.options.bounds.height)
        let dx = 25
        let lane = this.createShapeLane({
            x: this.options.bounds.x + dx,
            y: this.options.bounds.y + this.lanes.length * this.heightLane,
            width: this.options.bounds.width - dx,
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