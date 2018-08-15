import joint from "jointjs"
export const CallActivity = joint.dia.Element.define('bpmn.CallActivity',
    {
        attrs: {
            r: {
                fill: "#FFF",
                refWidth: '100%',
                refHeight: '100%',
                strokeWidth: 3,
                stroke: "#000",
                rx: "7"
            },
            d: {
                fill: "#FFF",
                refWidth: '40%',
                refHeight: '40%',
                strokeWidth: 3,
                refX: "30%",
                refY: "60%",
                stroke: "#000",
                rx: "5"
            },
            p: {
                fill: "#000",
                refX: "40%",
                refY: "65%",
                transform: "scale(0.2, 0.3)",
                stroke: "#000",
                strokeWidth: 2,
                refD: "M15.609375,10.609375 L11.390625,10.609375 L11.390625,6.390625 C11.390625,6.17488281 11.2157422,6 11,6 C10.7842578,6 10.609375,6.17488281 10.609375,6.390625 L10.609375,10.609375 L6.390625,10.609375 C6.17488281,10.609375 6,10.7842578 6,11 C6,11.2157422 6.17488281,11.390625 6.390625,11.390625 L10.609375,11.390625 L10.609375,15.609375 C10.609375,15.8251172 10.7842578,16 11,16 C11.2157422,16 11.390625,15.8251172 11.390625,15.609375 L11.390625,11.390625 L15.609375,11.390625 C15.8251172,11.390625 16,11.2157422 16,11 C16,10.7842578 15.8251172,10.609375 15.609375,10.609375 Z"
            },
            label: {
                textVerticalAnchor: 'middle',
                textAnchor: 'middle',
                refX: '50%',
                refY: '50%',
                fontSize: 12,
                fill: '#333333'
            }

        }
    }, {
        markup: [{
            tagName: 'rect',
            selector: 'r'
        }, {
            tagName: 'rect',
            selector: 'd'
        }, {
            tagName: 'path',
            selector: 'p'
        }, {
            tagName: 'text',
            selector: 'label'
        }]
    });