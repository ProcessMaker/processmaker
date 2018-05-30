import {mount, shallow} from '@vue/test-utils'
import {Elements} from '../../../../resources/assets/js/designer/diagram/elements'

let svg
const mockGroup = jest.fn(() => svg);
const mockAdd = jest.fn(() => svg);
const mockDrag = jest.fn(() => svg);
const mockRect = jest.fn(() => svg);
const mockAttr = jest.fn(() => svg);
const mockPath = jest.fn(() => svg);
const mockTrans = jest.fn(() => svg);
const mockCircle = jest.fn(() => svg);
const mockPolyline = jest.fn(() => svg);

svg = {
    group: mockGroup,
    add: mockAdd,
    drag: mockDrag,
    rect: mockRect,
    attr: mockAttr,
    path: mockPath,
    transform: mockTrans,
    circle: mockCircle,
    polyline: mockPolyline
}
mockAdd.mockReturnValue(svg)
mockGroup.mockReturnValue(svg)
mockRect.mockReturnValue(svg)
mockAttr.mockReturnValue(svg)
mockPath.mockReturnValue(svg)
mockTrans.mockReturnValue(svg)
mockCircle.mockReturnValue(svg)

describe('ExclusiveGateway - Class', () => {
    let eGat

    beforeEach(() => {
        eGat = new Elements["ExclusiveGateway"](
            {
                $type: "bpmn:ExclusiveGateway",
                id: "exc1",
                name: "Exclusive Gateway 1",
                moddleElement: {}
            },
            svg
        );
    })

    it('config() - verify the merge of options', () => {
        expect(eGat.options).toEqual({
            "$type": "bpmn:ExclusiveGateway",
            "attr": {"fill": "#FFF", "stroke": "#000", "strokeWidth": 2},
            "edgeLength": 28.284271247461902,
            "id": "exc1",
            "marker": "EMPTY",
            "moddleElement": {},
            "name": "Exclusive Gateway 1",
            "scaleX": 40,
            "scaleY": 40,
            "x": 100,
            "y": 100
        });
    })

    it('render() - Verify if use the library snap svg', () => {
        eGat.render()
        expect(mockAdd.mock.calls.length).toBe(1);
        expect(mockDrag.mock.calls.length).toBe(1);
        expect(mockRect.mock.calls.length).toBe(1);
        expect(mockAttr.mock.calls.length).toBe(2);
        expect(mockGroup.mock.calls.length).toBe(3);
        expect(mockTrans.mock.calls.length).toBe(1);
        expect(mockPolyline.mock.calls.length).toBe(2);
    })
})