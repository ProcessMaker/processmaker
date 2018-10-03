import joint from "jointjs"
export const Group = joint.dia.Element.define('bpmn.Group',
    {
        attrs: {
            d: {
                stroke: "#000",
                strokeWidth: 2,
                strokeDasharray: "5, 5",
                fill: "transparent",
                refWidth: '100%',
                refHeight: '100%',
                rx: 5
            }

        }
    }, {
        markup: [{
            tagName: 'rect',
            selector: 'd'
        }]
    });