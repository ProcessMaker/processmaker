import joint from "jointjs"
export const EndEvent = joint.dia.Element.define('bpmn.EndEvent',
    {
        attrs: {
            c: {
                fill: "#FFF1F2",
                stroke: "#ED4757",
                strokeWidth: 4,
                cx: 40,
                cy: 40,
                r: 20
            }
        }
    }, {
        markup: [{
            tagName: 'circle',
            selector: 'c'
        }]
    });