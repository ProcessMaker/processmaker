<template>
    <div id="svgCanvas" ref="canvas" @dragover="dragOver"></div>
</template>

<script>
    import {Builder} from "../diagram/builder"
    import actions from "../actions"
    import EventBus from "../lib/event-bus"
    import _ from "lodash"
    import joint from 'jointjs'
    import parser from 'xml-js'
    import BPMNHandler from '../BPMNHandler/BPMNHandler'
    import {Elements} from "../diagram/elements"
    import qinu from 'qinu'

    export default {
        props: [
            'processUid'
        ],
        data() {
            return {
                graph: null,
                paper: null,
                bpmnHandler: null,
                bpmn: '<?xml version="1.0" encoding="UTF-8"?><bpmn:definitions xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL" xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI" xmlns:dc="http://www.omg.org/spec/DD/20100524/DC" id="Definitions_0p3wzv2" targetNamespace="http://bpmn.io/schema/bpmn"><bpmn:collaboration id="Collaboration_1"></bpmn:collaboration>	<bpmn:process id="Process_1" isExecutable="false">	<bpmn:startEvent id="StartEvent_1" /></bpmn:process><bpmndi:BPMNDiagram id="BPMNDiagram_1">	<bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="Process_1">	<bpmndi:BPMNShape id="_BPMNShape_StartEvent_1" bpmnElement="StartEvent_1">	<dc:Bounds x="173" y="102" width="36" height="36" /></bpmndi:BPMNShape></bpmndi:BPMNPlane></bpmndi:BPMNDiagram></bpmn:definitions>',
                builder: null
            }
        },
        watch: {
            bpmn() {
                this.builder.clear()
                this.builder.createFromBPMN(this.bpmnHandler.buildModel(this.bpmn))
            }
        },
        computed: {},
        created() {
            this.bpmnHandler = new BPMNHandler()
            EventBus.$on(actions.designer.drag.toolbar.end().type, (value) => this.createElement(value))
            EventBus.$on(actions.designer.bpmn.update().type, (value) => {
                this.bpmn = value
            })
            EventBus.$on(actions.designer.flow.creating().type, (value) => this.creatingFlow(value))
            EventBus.$on(actions.designer.flow.create().type, (value) => this.createFlow(value))
            EventBus.$on(actions.designer.shape.remove().type, (value) => this.removeElement(value))
            EventBus.$on(actions.bpmn.save().type, (value) => this.saveBPMN(value))
            EventBus.$on(actions.bpmn.shape.assignTask().type, (value) => this.assignTask(value))
            EventBus.$on(actions.designer.shape.dragFromCrown().type, (value) => this.createFromCrown(value))
        },
        methods: {
            /**
             * Create the element
             * @param event
             */
            createElement(event) {
                let definition = event.target.getAttribute("event")
                let type = event.target.getAttribute("type")
                this.updateCoordinates()
                const defaultOptions = {
                    id: type + '_' + qinu(),
                    type: type,
                    bounds: {
                        x: event.x - this.diagramCoordinates.x,
                        y: event.y - this.diagramCoordinates.y
                    },
                    eventDefinition: definition
                }
                if (Elements[type.toLowerCase()]) {
                    this.builder.createShape(defaultOptions, true)
                } else {
                    ProcessMaker.alert(type.toLowerCase() + " is not supported", "danger")
                }
            },
            /**
             * Listener for remove element of the canvas
             * @param e
             */
            removeElement (e){
                this.builder.removeSelection()
            },
            saveBPMN (e){
                let result = this.bpmnHandler.toXML()
                ProcessMaker.apiClient.patch(`processes/${this.$props.processUid}/bpmn`, {
                    bpmn: result
                }).then((response) => {
                    ProcessMaker.alert("Process saved", "success")
                })
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
            creatingFlow(ev){
                this.builder.setSourceElement({
                    x: ev.x - this.diagramCoordinates.x,
                    y: ev.y - this.diagramCoordinates.y
                })
            },
            /**
             * Listener from Crown to create Flow
             */
            createFlow(ev){
                this.builder.createFlow({
                    x: ev.x - this.diagramCoordinates.x,
                    y: ev.y - this.diagramCoordinates.y
                })
            },
            assignTask(ev){
                this.builder.assignTask(ev)
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
             * Listener to link mouseenter
             * @param linkView
             */
            linkMouseEnter(linkView){
                linkView.showTools();
            },
            /**
             * Listener to link mouseleave
             * @param linkView
             */
            linkMouseLeave(linkView){
                linkView.hideTools();
            },
            dragOver(ev){
                this.updateCoordinates()
                this.builder.updateConnectingFlow({
                    x: ev.x - this.diagramCoordinates.x,
                    y: ev.y - this.diagramCoordinates.y
                })
            },
            updateCoordinates(){
                this.diagramCoordinates = {
                    x: this.$el.getBoundingClientRect().left,
                    y: this.$el.getBoundingClientRect().top
                }
            },
            createFromCrown(options){
                let event = options.ev
                let type = options.type
                let eventDefinition = options.eventDefinition
                this.updateCoordinates()
                const defaultOptions = {
                    id: type + '_' + qinu(),
                    type: type,
                    bounds: {
                        x: event.x - this.diagramCoordinates.x,
                        y: event.y - this.diagramCoordinates.y
                    },
                    eventDefinition
                }
                if (Elements[type.toLowerCase()]) {
                    this.builder.createShape(defaultOptions, true)
                    this.builder.createFlow({
                        x: event.x - this.diagramCoordinates.x,
                        y: event.y - this.diagramCoordinates.y
                    })
                } else {
                    ProcessMaker.alert(type.toLowerCase() + " is not supported", "danger")
                }
            }
        },
        mounted() {
            this.updateCoordinates()
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
            })
            this.graph.on('change:position', this.changeElementPosition)
            this.paper.on('element:pointerclick', this.clickElement)
            this.paper.on('blank:pointerclick', this.clickCanvas)
            this.paper.on('cell:pointerdown', this.pointerDown)
            this.paper.on('cell:pointerup', this.pointerUp)
            this.paper.on('link:mouseenter', this.linkMouseEnter)
            this.paper.on('link:mouseleave', this.linkMouseLeave)
            this.builder = new Builder(this.graph, this.paper)
            this.builder.createFromBPMN(this.bpmnHandler.buildModel(this.bpmn))
        }
    }
</script>

<style lang="scss" scoped>
    #svgCanvas {
        background-image: url(../img/bg_designer.gif);
    }
</style>