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

    formatWayPoints(wayPoints) {
        let res = []
        if (wayPoints && wayPoints.length > 2) {
            res = _.initial(wayPoints)
            res = _.drop(wayPoints);
        }
        return res
    }

    getSourcePoint(wayPoints) {
        return _.head(wayPoints)
    }

    getTargetPoint(wayPoints) {
        return _.last(wayPoints)
    }

    resetVertices() {
        if (this.shape) {
            this.shape.vertices([])
        }
    }
}
