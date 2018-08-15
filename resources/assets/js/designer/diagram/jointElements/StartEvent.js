import joint from "jointjs"
export const StartEvent = joint.dia.Element.define('bpmn.StartEvent',
    {
        attrs: {
            c: {
                fill: "#EDFFFC",
                stroke: "#00BF9C",
                strokeWidth: 2,
                cx: 20,
                cy: 20,
                r: 20
            },
            label: {
                textVerticalAnchor: 'middle',
                textAnchor: 'middle',
                refX: '50%',
                refY: '120%',
                fontSize: 12,
                fill: '#000'
            }
        }
    },
    {
        markup: [
            {
                tagName: 'circle',
                selector: 'c'
            },
            {
                tagName: 'text',
                selector: 'label'
            }
        ]
    });