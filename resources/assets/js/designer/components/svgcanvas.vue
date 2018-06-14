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
                paper: null,

                xml: null, // BPMN XML string
                $svg: null, //scg Object Jquery
                svg: null, //svg Canvas Snap Object
                definitions: null,  // Definitions parse of XML
                diagramCoordinates: null, // Coordinates for svg tag
                builder: null,
                scale: 1,
                pan: {
                    panStartX: null,
                    panStartY: null,
                    mouseDown: null,
                    pageTop: null,
                    pageLeft: null,
                    panEndX: null,
                    panEndY: null,
                    panTop: null,
                    panLeft: null
                } // Options for panning in the designer
            }
        },
        computed: {},
        created() {
            EventBus.$on(actions.designer.drag.toolbar.end().type, (value) => this.createElement(value))
            EventBus.$on(actions.designer.flow.create().type, (value) => this.createFlow(value))
            EventBus.$on(actions.designer.shape.remove().type, (value) => this.removeElement(value))
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
            addListeners(){
                this.graph.on('change:position', this.changePosition);
            },
            changePosition(element){
                this.builder.updatePosition(element)
            },
            createFlow(){
                this.builder.setSourceElement()
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
            this.addListeners()
        }
    }
</script>

<style lang="scss" scoped>
    #svgCanvas {
        background-image: url(../img/bg_designer.gif);
    }
</style>
