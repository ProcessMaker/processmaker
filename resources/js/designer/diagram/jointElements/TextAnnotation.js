import joint from "jointjs"
export const TextAnnotation = joint.dia.Element.define('bpmn.TextAnnotation',
    {
        attrs: {
            label: {
                textVerticalAnchor: 'middle',
                textAnchor: 'middle',
                refX: '50%',
                refY: '50%',
                fontSize: 14,
                fill: '#333333'
            },
            p: {
                //fill: "#FFF",
                //transform: "scale(0.05)",
                //strokeWidth: 2,
                //stroke: "#000",
                //rx: "7",
                fill: "transparent",
                stroke: "#000",
                strokeWidth: 2,
                refD: "M 20 0 L 0 0 0 100 20 100"
            },
            foreign: {}
        }
    },
    {
        markup: [
            {
                tagName: 'path',
                selector: 'p'
            },
            {
                tagName: 'text',
                selector: 'label'
            },
            {
                tagName: 'foreignObject',
                selector: 'foreign'
            }
        ]
    }
)
