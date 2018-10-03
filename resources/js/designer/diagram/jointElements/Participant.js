import joint from "jointjs"
export const Participant = joint.dia.Element.define('bpmn.Participant',
    {
        attrs: {
            r: {
                stroke: "#000",
                strokeWidth: 2,
                fill: "#fff",
                width: '25',
                refHeight: '100%'
            },
            d: {
                stroke: "#000",
                strokeWidth: 2,
                fill: "#fff",
                refWidth: '100%',
                refHeight: '100%'
            }
        }
    }, {
        markup: [{
            tagName: 'rect',
            selector: 'd'
        }, {
            tagName: 'rect',
            selector: 'r'
        }]
    });