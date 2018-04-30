import {StartEvent} from "./events/startEvent"
import _ from "lodash"
import actions from "../actions/index";

//import {ConnectorShape} from "./elements/connector/connector.shape";

//import {isNullOrUndefined} from "util";

export class Builder {
    constructor(svg) {
        this.svg = svg;
        debugger;
    }

    createShape(type, options) {
        let shape,
            defaultOptions = {
                $type: type,
                id: options.id,
                name: options.bpmnElement && options.bpmnElement.name ? options.bpmnElement.name : options.name ? options.name : "",
                uid: null,
                type: null,
                moddleElement: options
            };

        debugger;
        defaultOptions = _.extend(defaultOptions, options);
        // TODO Improve uid selector, too much duplicated or multiplicated lines... (ø_ø)!
        switch (type) {
            case "bpmn:StartEvent":
                defaultOptions.type = type;
                defaultOptions.id = options.id;
                shape = new StartEvent(
                    defaultOptions,
                    new EventShape(
                        this.canvas,
                        this.svgLoader
                    )
                );
                shape.render();
                break;
            default:
                // shape = this.UnknownShape(defaultOptions, this.canvas);
                break;
        }
        if (shape) {
            // Event Handling
            const nativeShape = shape.getShape().getNativeShape();

            const shapeCanvas = this.canvas;
            nativeShape.click((event) => {
                this.shapeSelected = true;
                const shapeBox = nativeShape.getBBox();
                console.log("shapeBox:", shapeBox);
                event.bpmn_element = {
                    id: defaultOptions.id,
                    type: defaultOptions.type
                };
                console.log(`Type: ${defaultOptions.type} Id: ${defaultOptions.id}`);
                if (this.selection) {
                    this.selection.remove();
                }

                this.selection = shapeCanvas.rect(shapeBox.x - 5, shapeBox.y - 5, shapeBox.width + 10, shapeBox.height + 10, 5).attr({
                    fill: "none",
                    stroke: "#1fb64b",
                    strokeWidth: 2,
                    strokeDasharray: "3px,7px",
                    strokeLinecap: "square"
                });
                this.diagram.getShape().getNativeShape().add(this.selection);
            });
            nativeShape.dblclick((event) => {
                console.log("dblclick handler for connect purposes");
                // save pre connect shape
                if (shapeCanvas.preConnectionShape) {
                    console.log(shapeCanvas.preConnectionShape);
                    console.log(shape);
                    this.connect(shapeCanvas.preConnectionShape, shape);
                    shapeCanvas.preConnectionShape = false;
                    shapeCanvas.preConnectionShape = null;
                    console.log("-----------------");
                } else {
                    shapeCanvas.preConnectionShape = shape;
                }
            });
            let positions = {};
            nativeShape.drag(
                (dx, dy, posx, posy) => {
                    // console.log(shape);
                    nativeShape.attr({cx: posx, cy: posy});

                    shape.getShape().refreshAllConnections(nativeShape);

                    positions = {
                        sessionId: $("#sessionId").val(),
                        id: defaultOptions.id,
                        type: defaultOptions.type,
                        x: dx,
                        y: dy,
                        transform: nativeShape.data("origTransform") + (nativeShape.data("origTransform") ? "T" : "t") + [dx, dy]
                    };
                },
                () => {
                    console.log("on drag start");
                    nativeShape.data("origTransform", nativeShape.transform().local);
                },
                () => {
                    console.log("on drag end");
                    this.sendMessageChannel(positions);
                }
            );

            this.registerElement(defaultOptions.id, shape);
            return shape;
        }
    }

    /**
     * Create a connector
     * @constructor
     */
    CreateConnectors() {
        // Todo create connector
    }

    UnknownShape(options, canvas) {
        const shape = {
            shape: canvas.group()
        };
        return shape;
    }

    draw(data) {
        let jw = require("json-walker");
        const diagrams = new Promise((resolve, reject) => {
            jw.findArraysByKey("diagrams", data, (err, results) => {
                resolve(results);
            });
        });
        diagrams.then((res) => {
            const planeElement = new Promise((resolve, reject) => {
                jw.findArraysByKey("planeElement", res[0], (err, modified) => {
                    resolve(modified);
                    console.log("finished");
                });
            });
            planeElement.then((res) => {
                for (let item in res) {
                    for (let element in res[item]) {
                        let moddleElement = res[item][element];
                        let flows = ["bpmn:SequenceFlow", "bpmn:MessageFlow"];
                        if (flows.indexOf(moddleElement.bpmnElement.$type) < 0) {
                            console.log(moddleElement);
                            this.createShape(
                                moddleElement.bpmnElement.$type,
                                this.extend(moddleElement.bpmnElement, moddleElement.bounds)
                            );
                        }
                    }
                    for (let element in res[item]) {
                        let moddleElement = res[item][element];
                        let flows = ["bpmn:SequenceFlow", "bpmn:MessageFlow"];
                        if (flows.indexOf(moddleElement.bpmnElement.$type) >= 0) {
                            console.log(moddleElement);
                            this.createShape(
                                moddleElement.bpmnElement.$type, moddleElement
                            );

                        }
                    }
                }
            });
            // jw.findArraysByKey('planeElement', res[0], function(err, results){
            //     console.log(results);
            // });
        });


    }

    getOffset(diagram, elementData) {
        const aliases = {
            bpmnLane: {type: "lanes", id_field: "lan_uid"},
            bpmnPool: {type: "laneset", id_field: "lns_uid"}
        };
        const offset = {};
        if (elementData.bou_container !== "bpmnDiagram") {
            const data = this.getOffset(
                diagram,
                this.searchElement(
                    diagram,
                    aliases[elementData.bou_container].type,
                    aliases[elementData.bou_container].id_field,
                    elementData.bou_element
                )
            );
            offset.x = +elementData.bou_x + data.x + 1;
            offset.y = +elementData.bou_y + data.y + 1;
        } else {
            offset.x = parseInt(elementData.bou_x, 10);
            offset.y = parseInt(elementData.bou_y, 10);
        }
        return offset;
    }

    searchElement(diagram, type, idField, id) {
        for (const element of diagram[type]) {
            if (element[idField] === id) {
                return element;
            }
        }
    }

    registerElement(id, shape) {
        this.diagram.add(shape);
        this.elementRegistry.set(id, shape);
    }

    getElementById(id) {
        return this.elementRegistry.get(id);
    }

    connect(srcShape, destShape) {
        let defaultOptions = {};
        defaultOptions.type = "bpmn:SequenceFlow";
        defaultOptions.id = `SequenceFlow_${Math.floor((Math.random() * 100) + 1)}`;
        defaultOptions.method = "manhathan";
        defaultOptions.label = {
            bounds: {
                width: 0,
                height: 0,
                x: 0,
                y: 0
            }
        };
        let shape = new Flow(
            defaultOptions,
            new ConnectorShape(
                this.canvas,
                this.svgLoader
            )
        );
        let shapeBox = srcShape.getShape().shape.getBBox();
        const x1 = shapeBox.x + shapeBox.width;
        const y1 = shapeBox.y + shapeBox.height / 2;

        shapeBox = destShape.getShape().shape.getBBox();
        const x2 = shapeBox.x;
        const y2 = shapeBox.y + shapeBox.height / 2;

        srcShape.getShape().registerOutputConn(defaultOptions.uid, shape);
        destShape.getShape().registerInputConn(defaultOptions.uid, shape);
        shape.getShape().renderManhathan(x1, y1, x2, y2);
        this.diagram.add(shape);
    }
}