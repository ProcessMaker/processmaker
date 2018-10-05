import joint from "jointjs"
export const InclusiveGateway = joint.dia.Element.define('bpmn.InclusiveGateway',
    {
        attrs: {
            p: {
                fill: "#FFF",
                stroke: "#000",
                strokeWidth: 2,
                refD: "M0.630525477,10.6816884 C0.456495378,10.8561873 0.456540222,11.1442949 0.630329149,11.3181153 L10.6822379,21.3696614 C10.8560174,21.5434462 11.1439826,21.5434462 11.3177621,21.3696614 L21.3696591,11.3174593 C21.543447,11.1436662 21.543447,10.8556661 21.3696709,10.6818847 L11.3177621,0.630338561 C11.1439826,0.456553813 10.8560174,0.456553813 10.6822262,0.630350303 L0.630525477,10.6816884 Z"
            },
            c: {
                strokeWidth: 2,
                stroke: '#000000',
                fill: '#FFF',
                cx: 20,
                cy: 20,
                r: 9
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
                tagName: 'path',
                selector: 'p'
            }, {
                tagName: 'circle',
                selector: 'c'
            },
            {
                tagName: 'text',
                selector: 'label'
            }
        ]
    });