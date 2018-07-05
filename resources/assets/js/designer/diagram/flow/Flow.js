import _ from "lodash"
/**
 * Flow class
 */
export default class {
    constructor(graph, paper) {
        this.graph = graph
        this.paper = paper
        this.shape = null
    }

    /**
     * Merge options default with options from arguments
     * @param options
     * @returns {TaskShape}
     */
    config(options) {
        this.options = Object.assign({}, this.options, options);
        return this;
    }

    /**
     * Return the object joint
     * @returns {*}
     */
    getShape() {
        return this.shape
    }

    /**
     * Get the way for load in link vertices
     * @param wayPoints
     * @returns {Array}
     */
    formatWayPoints(wayPoints) {
        let res = []
        if (wayPoints && wayPoints.length > 2) {
            res = _.initial(wayPoints)
            res = _.drop(wayPoints);
        }
        return res
    }

    /**
     * Get first point of waypoints
     */
    getSourcePoint(wayPoints) {
        return _.head(wayPoints)
    }

    /**
     * Get last point of waypoints
     * @param wayPoints
     */
    getTargetPoint(wayPoints) {
        return _.last(wayPoints)
    }

    /**
     * Reset vertices of link
     */
    resetVertices() {
        if (this.shape) {
            this.shape.vertices([])
        }
    }
}
