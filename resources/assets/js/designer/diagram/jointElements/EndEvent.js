import joint from "jointjs"
export const EndEvent = joint.dia.Element.define('bpmn.EndEvent',
    {
        attrs: {
            c: {
                fill: "#FFF1F2",
                stroke: "#ED4757",
                strokeWidth: 4,
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