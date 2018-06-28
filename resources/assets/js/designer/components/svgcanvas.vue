<template>
    <div id="svgCanvas" ref="canvas" @dragover="$event.preventDefault()" @drop="dropHandler($event)"></div>
</template>

<script>
    import {Builder} from "../diagram/builder"
    import actions from "../actions"
    import EventBus from "../lib/event-bus"
    import _ from "lodash"
    import joint from 'jointjs'
    import parser from 'xml-js'
    import BPMNHandler from '../lib/BPMNHandler'
    export default {
        props: [
            'bpmn'
        ],
        data() {
            return {
                graph: null,
                paper: null,
                bpmnHandler: null,
                xml: null
            }
        },
        watch: {
            bpmn() {
            }
        },
        computed: {},
        created() {
            EventBus.$on(actions.designer.drag.toolbar.end().type, (value) => this.createElement(value))
            EventBus.$on(actions.designer.flow.create().type, (value) => this.createFlow(value))
            EventBus.$on(actions.designer.shape.remove().type, (value) => this.removeElement(value))
        },
        methods: {
            loadXML() {
                let options = {ignoreComment: true, alwaysChildren: true}
                let result = parser.xml2js(this.xml, options)
                this.bpmnHandler = new BPMNHandler(result)
                this.createFromBPMN(this.bpmnHandler.buildModel())
            },
            validateXML(xml) {
                //todo add method for validate xml with xsd BPMN
                this.xml = xml
                this.loadXML()
            },
            dropHandler(e) {
                function errorHandler(evt) {
                    switch (evt.target.error.code) {
                        case evt.target.error.NOT_FOUND_ERR:
                            alert(__('File Not Found!'));
                            break;
                        case evt.target.error.NOT_READABLE_ERR:
                            alert(__('File is not readable'));
                            break;
                        case evt.target.error.ABORT_ERR:
                            break; // noop
                        default:
                            alert(__('An error occurred reading this file.'));
                    }
                }
                let file = e && e.dataTransfer ? e.dataTransfer.files[0] : null
                if (file) {
                    let that = this;
                    let reader = new FileReader();
                    reader.onerror = errorHandler;
                    reader.onabort = function (e) {
                        alert(__('File read cancelled'));
                    };
                    reader.onload = function (ev) {
                        that.validateXML(ev.target.result);
                    };
                    reader.readAsText(file);
                    e.preventDefault()
                }
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
                    type: name[1]
                };
                this.builder.createShape(defaultOptions, event.target.id);
            },
            /**
             * Create the element
             * @param event
             */
            createFromBPMN(elements) {
                let that = this
                this.builder.clear()
                _.each(elements, (element) => {
                    this.builder.createShape(element);
                })
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
        }
    }
</script>

<style lang="scss" scoped>
    #svgCanvas {
        background-image: url(../img/bg_designer.gif);
    }
</style>