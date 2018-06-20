<template>
    <div id="svgCanvas" ref="canvas"></div>
</template>

<script>
    import bpmn from "bpmn-moddle"
    import {Builder} from "../diagram/builder"
    import actions from "../actions"
    import EventBus from "../lib/event-bus"
    import _ from "lodash"
    import joint from 'jointjs'

    let moddle = new bpmn()
    export default {
        // Set our own static components but also bring in our dynamic list of modals from above
        data() {
            return {
                graph: null,
                paper: null
            }
        },
        computed: {},
        created() {
            EventBus.$on(actions.designer.drag.toolbar.end().type, (value) => this.createElement(value))
            EventBus.$on(actions.designer.flow.create().type, (value) => this.createFlow(value))
            EventBus.$on(actions.designer.shape.remove().type, (value) => this.removeElement(value))
            EventBus.$on(actions.designer.lane.create().type, (value) => this.creatingLane(value))
        },
        methods: {
            loadXML(xml = null) {
                let that = this;
                if (xml) this.xml = xml;
                moddle.fromXML(that.xml, function (err, def) {
                    that.definitions = def;
                });
            },
            /**
             * Create the element
             * @param event
             */
            createElement(event) {
                let name = event.target.id.split(':')
                this.diagramCoordinates = {
                    x: this.$el.getBoundingClientRect().left,
                    y: this.$el.getBoundingClientRect().top
                }
                const defaultOptions = {
                    id: name[1] + '_' + Math.floor((Math.random() * 100) + 1),
                    x: event.x - this.diagramCoordinates.x,
                    y: event.y - this.diagramCoordinates.y,
                    eClass: name[1]
                };
                this.builder.createShape(event.target.id, defaultOptions);
            },
            /**
             * Listener for remove element of the canvas
             * @param e
             */
            removeElement (e){
                this.builder.removeSelection()
            },
            /**
             * Listener from Crown for update position in element
             */
            changeElementPosition(element){
                this.builder.updatePosition(element)
            },
            /**
             * Listener from Crown for click in any element
             */
            clickElement(element){
                this.builder.onClickShape(element)
            },
            /**
             * Listener from Crown for click in canvas
             */
            clickCanvas(element){
                this.builder.onClickCanvas(element)
            },
            /**
             * Listener from Crown to create Flow
             */
            createFlow(){
                this.builder.setSourceElement()
            },
            /**
             * Listener to dragstart in Lane
             */
            creatingLane(){
                this.builder.setCreatingLane(true)
            },
            /**
             * Listener in pointerDown event
             */
            pointerDown(cellView, evt, x, y){
                this.builder.pointerDown(cellView, evt, x, y)
            },
            /**
             * Listener in pointerup event
             */
            pointerUp(cellView, evt, x, y){
                this.builder.pointerUp(cellView, evt, x, y)
            },
            /**
             * Listener in mouseenter event
             */
            mouseEnter(cellView, evt, x, y){
                this.builder.mouseEnter(cellView, evt, x, y)
            },
            /**
             * Listener in interactive event
             */
            interactive(cellView, method){
                return this.builder.interactive(cellView, method)
            }
        },
        mounted() {
            this.graph = new joint.dia.Graph
            this.paper = new joint.dia.Paper({
                el: this.$el,
                model: this.graph,
                width: 7000,
                height: 7000,
                gridSize: 10,
                drawGrid: true,
                background: {
                    color: 'white'
                },
                interactive: this.interactive
            });
            this.builder = new Builder(this.graph, this.paper)
            this.graph.on('change:position', this.changeElementPosition)
            this.paper.on('element:pointerclick', this.clickElement)
            this.paper.on('blank:pointerclick', this.clickCanvas)
            this.paper.on('cell:pointerdown', this.pointerDown)
            this.paper.on('cell:pointerup', this.pointerUp)
            this.paper.on('element:mouseenter', this.mouseEnter)
            this.paper.on('element:mouseleave', this.mouseLeave)
        }
    }
</script>

<style lang="scss" scoped>
    #svgCanvas {
        background-image: url(../img/bg_designer.gif);
    }
</style>
