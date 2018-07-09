<template>
    <div id="svgCanvas" ref="canvas"></div>
</template>

<script>
    import {Builder} from "../diagram/builder"
    import actions from "../actions"
    import EventBus from "../lib/event-bus"
    import _ from "lodash"
    import joint from 'jointjs'
    import parser from 'xml-js'
    import BPMNHandler from '../lib/BPMNHandler'
    import {Elements} from "../diagram/elements";
    export default {
        data() {
            return {
                graph: null,
                paper: null,
                bpmnHandler: null,
                bpmn: null,
                builder: null
            }
        },
        watch: {
            bpmn() {
                this.builder.clear()
                let options = {ignoreComment: true, alwaysChildren: true}
                let result = parser.xml2js(this.bpmn, options)
                this.bpmnHandler = new BPMNHandler(result)
                this.builder.createFromBPMN(this.bpmnHandler.buildModel())
            }
        },
        computed: {},
        created() {
            EventBus.$on(actions.designer.drag.toolbar.end().type, (value) => this.createElement(value))
            EventBus.$on(actions.designer.bpmn.update().type, (value) => this.bpmn = value)
            EventBus.$on(actions.designer.flow.create().type, (value) => this.createFlow(value))
            EventBus.$on(actions.designer.shape.remove().type, (value) => this.removeElement(value))
        },
        methods: {
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
                    type: name[1].toLowerCase()
                };
                if (Elements[name[1].toLowerCase()]) {
                    this.builder.createShape(defaultOptions, event.target.id);
                } else {
                    console.error(name[1].toLowerCase() + "is not support")
                }
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
                }
            });
            this.builder = new Builder(this.graph, this.paper)
            this.graph.on('change:position', this.changeElementPosition);
            this.paper.on('element:pointerclick', this.clickElement)
            this.paper.on('blank:pointerclick', this.clickCanvas)
            this.paper.on('cell:pointerdown', this.pointerDown)
            this.paper.on('cell:pointerup', this.pointerUp)
        }
    }
</script>

<style lang="scss" scoped>
    #svgCanvas {
        background-image: url(../img/bg_designer.gif);
    }
</style>