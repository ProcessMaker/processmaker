import joint from "jointjs"
export const BlackBoxPool = joint.dia.Element.define('bpmn.BlackBoxPool',
    {
        attrs: {
            r: {
                stroke: "#000",
                strokeWidth: 2,
                fill: "#94a6b5",
                width: '25',
                refHeight: '100%'
            },
            d: {
                stroke: "#000",
                strokeWidth: 2,
                fill: "#94a6b5",
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