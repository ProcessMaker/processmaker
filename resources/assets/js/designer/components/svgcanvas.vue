<template>
    <svg id="svg" ref="canvas" :style="{width: canvasWidth, height:canvasHeight}" class="svg_canvas"
         @mousemove="mouseMove"
         @mousedown="mouseDown"
         @mouseup="mouseUp">
    </svg>
</template>


<script>
    import bpmn from "bpmn-moddle"
    import {Builder} from "../diagram/builder"
    import actions from "../actions"
    import EventBus from "../lib/event-bus"


    let moddle = new bpmn()
    export default {
        // Set our own static components but also bring in our dynamic list of modals from above
        data() {
            return {
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
        computed: {
            canvasWidth() {
                // Calculate the width needed based off our object model
                return '100%'
            },
            canvasHeight() {
                // Calcuate the height needed based off our object model
                return '100%'
            }
        },
        created() {
            EventBus.$on(actions.designer.drag.toolbar.end().type, (value) => this.createElement(value))
            EventBus.$on(actions.designer.drag.shape.start().type, this.onDragStartShape())
            EventBus.$on(actions.designer.drag.shape.end().type, this.onDragEndShape())
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
                    x: this.svg.node.getBoundingClientRect().left,
                    y: this.svg.node.getBoundingClientRect().top
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
             * On mouseMove Event
             * @param e
             */
            mouseMove (e) {
                if (this.pan.mouseDown) {
                    let pageTop = this.pan.pageTop;
                    let pageLeft = this.pan.pageLeft;
                    this.pan.panEndX = e.pageX;
                    this.pan.panEndY = e.pageY;
                    if (this.pan.panStartY > this.pan.panEndY) {
                        this.pan.panTop = this.pan.panEndY - this.pan.panStartY;
                        pageTop += this.pan.panTop;
                        this.$svg.css({top: pageTop});
                    } else {
                        this.pan.panTop = this.pan.panStartY - this.pan.panEndY;
                        pageTop -= this.pan.panTop;
                        if (pageTop > 0) pageTop = 0;
                        this.$svg.css({top: pageTop});
                    }

                    if (this.pan.panStartX > this.pan.panEndX) {
                        this.pan.panLeft = this.pan.panEndX - this.pan.panStartX;
                        pageLeft += this.pan.panLeft;
                        this.$svg.css({left: pageLeft});
                    } else {
                        this.pan.panLeft = this.pan.panStartX - this.pan.panEndX;
                        pageLeft -= this.pan.panLeft;
                        if (pageLeft > 0) pageLeft = 0;
                        this.$svg.css({left: pageLeft});
                    }
                }
            },
            /**
             * On mouseDown Event
             * @param e
             */
            mouseDown (e){
                this.builder.hideCrown()
                if (!this.pan.shapeDrag) {
                    this.pan.panStartX = e.pageX;
                    this.pan.panStartY = e.pageY;
                    this.pan.mouseDown = true;
                    this.pan.pageTop = parseInt(this.$svg.css('top'), false) || 0;
                    this.pan.pageLeft = parseInt(this.$svg.css('left'), false) || 0;
                }
            },
            /**
             * On mouseUp Event
             * @param e
             */
            mouseUp (e){
                this.pan.mouseDown = false;
            },
            /**
             * On Drag start Shape listener
             * @returns {function()}
             */
            onDragStartShape (){
                return (ev) => {
                    this.pan.shapeDrag = true;
                }
            },
            /**
             * On Drag End Shape listener
             * @param e
             * @returns {function()}
             */
            onDragEndShape (e){
                return (ev) => {
                    this.pan.shapeDrag = false;
                }
            },
            /**
             * Listener for remove element of the canvas
             * @param e
             */
            removeElement (e){
                this.builder.removeSelection()
            }
        },
        mounted() {
            this.$svg = $("#svg") // Object Jquery
            this.svg = Snap("#svg") // Object Snap svg
            this.builder = new Builder(this.svg, EventBus)
        }
    }
</script>

<style lang="scss" scoped>
    .svg_canvas {
        display: block;
        background-image: url(../img/bg_designer.gif);
        background-color: white;
        min-height: 100%;
        min-width: 100%;
    }
</style>
