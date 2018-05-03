<template>
    <div class="svg_container">
        <svg id="svg" height="5000px" width="5000px" class="svg_canvas"></svg>
    </div>
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
                svg: null, //svg Canvas in designer
                definitions: null,  // Definitions parse of XML
                diagramCoordinates: null, // Coordinates for svg tag
                builder: null,
                scale: 1
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
            }
        },
        mounted() {
            this.svg = Snap("#svg")
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
        background: #FFFFFF;
    }

    .pmdesigner-container {
        display: flex;
    }
</style>