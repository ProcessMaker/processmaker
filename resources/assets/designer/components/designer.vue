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
                builder: null
            }
        },
        created() {
            debugger;
            this.$parent.emitter.$on(actions.designer.drag.end().type, (value) => this.createElement(value));
        },
        methods: {
            loadXML(xml = null) {
                let that = this;
                if (xml) this.xml = xml;
                moddle.fromXML(that.xml, function (err, def) {
                    that.definitions = def;
                    //that.diagramSvg.draw(def);
                });
            },
            createElement(event) {
                event.target.style.opacity = "";
                let name = event.target.id.split(':');
                const defaultOptions = {
                    id: name[1] + '_' + Math.floor((Math.random() * 100) + 1),
                    x: event.x - this.diagramCoordinates.x,
                    y: event.y - this.diagramCoordinates.y
                };
                const created = this.builder.createShape(event.target.id, defaultOptions);
                const shape = created.getShape();
                let element = moddle.create(event.target.id, {
                    id: defaultOptions.id,
                    x: shape.x,
                    y: shape.y,
                    width: shape.scaleX || shape.scale,
                    height: shape.scaleY || shape.scale
                });
                this.diagramSvg.sendMessageChannel({
                    sessionId: $('#sessionId').val(),
                    id: defaultOptions.id,
                    $type: event.target.id,
                    x: event.x - this.diagramCoordinates.x,
                    y: event.y - this.diagramCoordinates.y,
                });
                this.definitions.get('diagrams').push(element);
                console.log(this.definitions)
            },
        },
        mounted() {
            this.svg = Snap("#svg")
            this.diagramCoordinates = {
                x: document.getElementById('svg').getBoundingClientRect().left,
                y: document.getElementById('svg').getBoundingClientRect().top
            }
            debugger;
            this.builder = new Builder(this.svg)
            this.loadXML(this.$parent.xml)
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