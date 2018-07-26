import _ from "lodash"
import joint from "jointjs"
import actions from "../../actions"
import EventBus from "../../lib/event-bus"

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
            res = _.drop(res)
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

    /**
     * Set target to link
     * @param target
     */
    setTarget(target) {
        this.shape.target(target.getShape(), {
            connectionPoint: {
                name: 'anchor',
                args: {
                    offset: 0
                }
            },
            anchor: {
                name: 'midSide',
                args: {
                    rotate: false,
                    padding: 0
                }
            }
        })
    }

    /**
     * Set target to link
     * @param target
     */
    setSource(source) {
        this.shape.source(source.getShape(), {
            connectionPoint: {
                name: 'anchor',
                args: {
                    offset: 0
                }
            },
            anchor: {
                name: 'midSide',
                args: {
                    rotate: false,
                    padding: 0
                }
            }
        })
    }

    /**
     * Create tools from link
     */
    createTools() {
        let verticesTool = new joint.linkTools.Vertices()
        let segmentsTool = new joint.linkTools.Segments()
        let sourceArrowheadTool = new joint.linkTools.SourceArrowhead()
        let targetArrowheadTool = new joint.linkTools.TargetArrowhead()
        let sourceAnchorTool = new joint.linkTools.SourceAnchor()
        let targetAnchorTool = new joint.linkTools.TargetAnchor()
        let boundaryTool = new joint.linkTools.Boundary()
        let removeButton = new joint.linkTools.Remove()
        let toolsView = new joint.dia.ToolsView({
            tools: [
                verticesTool, segmentsTool,
                sourceArrowheadTool, targetArrowheadTool,
                sourceAnchorTool, targetAnchorTool,
                boundaryTool, removeButton
            ]
        })
        let linkView = this.shape.findView(this.paper)
        linkView.addTools(toolsView);
        linkView.hideTools()
    }

    createBpmn() {
        let linkView = this.paper.findViewByModel(this.shape)
        let arrVertices = this.getVertices(linkView)
        let action = actions.bpmn.flow.create({
            id: this.shape.id,
            sourceRef: this.shape.getSourceElement().get("id"),
            targetRef: this.shape.getTargetElement().get("id"),
            type: this.options.type,
            bounds: arrVertices
        })
        EventBus.$emit(action.type, action.payload)
    }

    getVertices(linkView) {
        let connection = linkView.getConnection()
        let arrayPoints = []
        _.each(connection.segments, (val) => {
            arrayPoints.push(val.end)
        })
        return arrayPoints
    }

    updateBpmn() {
        let that = this
        return () => {
            let linkView = that.paper.findViewByModel(that.shape)
            let arrVertices = that.getVertices(linkView)
            let action = actions.bpmn.flow.update({
                id: that.shape.id,
                sourceRef: that.shape.getSourceElement().get("id"),
                targetRef: that.shape.getTargetElement().get("id"),
                type: that.options.type,
                bounds: arrVertices
            })
            EventBus.$emit(action.type, action.payload)
        }
    }

    addEvents() {
        this.shape.on("change", this.updateBpmn())
        //this.shape.on("change:source", this.updateBpmn())
        //this.shape.on("change:target", this.updateBpmn())
    }
}
