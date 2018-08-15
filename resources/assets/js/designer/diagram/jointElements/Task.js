import joint from "jointjs"
export const Task = joint.dia.Element.define('bpmn.Task',
    {
        attrs: {
            r: {
                fill: "#FFF",
                refWidth: '100%',
                refHeight: '100%',
                strokeWidth: 2,
                stroke: "#000",
                rx: "7"
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
            tagName: 'text',
            selector: 'label'
        }]
    });