<template>
    <svg id="svg" height="7000px" width="7000px" class="svg_canvas"
         @mousemove="mouseMove"
         @mousedown="mouseDown"
         @mouseup="mouseUp">
    </svg>
</template>

<script>
    import bpmn from "bpmn-moddle"
    import {Builder} from "../diagram/builder"
    import actions from "../actions"
    let moddle = new bpmn()
    export default {
        data() {
            return {
                xml: null, // BPMN XML string
                $svg: null, //scg Object Jquery
                svg: null, //svg Canvas Snap Object
                definitions: null,  // Definitions parse of XML
                diagramCoordinates: null, // Coordinates for svg tag
                builder: null,
                scale: 1,
                pan: {}
            }
        },
        created() {
            Dispatcher.$on(actions.designer.drag.end().type, (value) => this.createElement(value))
        },
        methods: {
            loadXML(xml = null) {
                let that = this;
                if (xml) this.xml = xml;
                moddle.fromXML(that.xml, function (err, def) {
                    that.definitions = def;
                });
            },
            createElement(event) {
                let name = event.target.id.split(':');
                const defaultOptions = {
                    id: name[1] + '_' + Math.floor((Math.random() * 100) + 1),
                    x: event.x - this.diagramCoordinates.x,
                    y: event.y - this.diagramCoordinates.y,
                    eClass: name[1]
                };
                this.builder.createShape(event.target.id, defaultOptions);
            },
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
            mouseDown (e){
                this.pan.panStartX = e.pageX;
                this.pan.panStartY = e.pageY;
                this.pan.mouseDown = true;
                this.pan.pageTop = parseInt(this.$svg.css('top'), false) || 0;
                this.pan.pageLeft = parseInt(this.$svg.css('left'), false) || 0;
            },
            mouseUp (e){
                this.pan.mouseDown = false;
            }
        },
        mounted() {
            this.$svg = $("#svg") // Object Jquery
            this.svg = Snap("#svg") // Object Snap svg
            this.diagramCoordinates = {
                x: this.svg.node.getBoundingClientRect().left,
                y: this.svg.node.getBoundingClientRect().top
            }
            this.builder = new Builder(this.svg, Dispatcher)
        }
    }
</script>

<style>
    .svg_canvas {
        background-image: url(../img/bg_designer.gif);
        position: relative;
    }

    .pmdesigner-container {
        overflow: hidden;
    }
</style>