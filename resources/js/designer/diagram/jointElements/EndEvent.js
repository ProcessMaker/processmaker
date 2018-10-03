import joint from "jointjs"
export const EndEvent = joint.dia.Element.define('bpmn.EndEvent',
    {
        attrs: {
            c: {
                fill: "#FFF1F2",
                stroke: "#ED4757",
                strokeWidth: 4,
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
    }, {
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