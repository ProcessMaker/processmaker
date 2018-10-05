import joint from "jointjs"
export const Lane = joint.dia.Element.define('bpmn.Lane',
    {
        attrs: {
            d: {
                stroke: "#000",
                strokeWidth: 2,
                fill: "none",
                refWidth: '100%',
                refHeight: '100%'
            }

        }
    }, {
        markup: [{
            tagName: 'rect',
            selector: 'd'
        }]
    });