import joint from "jointjs"
export const StartEvent = joint.dia.Element.define('bpmn.StartEvent',
    {
        attrs: {
            c: {
                fill: "#EDFFFC",
                stroke: "#00BF9C",
                strokeWidth: 2,
                refCx: "100%",
                refCy: "100%",
                refR: "50%"
            }
        }
    }, {
        markup: [{
            tagName: 'circle',
            selector: 'c'
        }]
    });