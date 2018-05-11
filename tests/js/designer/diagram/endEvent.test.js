import {mount, shallow} from '@vue/test-utils'
import {Elements} from '../../../../resources/assets/designer/diagram/elements'

let svg
const mockGroup = jest.fn(() => svg);
const mockAdd = jest.fn(() => svg);
const mockDrag = jest.fn(() => svg);
const mockRect = jest.fn(() => svg);
const mockAttr = jest.fn(() => svg);
const mockPath = jest.fn(() => svg);
const mockTrans = jest.fn(() => svg);
const mockCircle = jest.fn(() => svg);

svg = {
    group: mockGroup,
    add: mockAdd,
    drag: mockDrag,
    rect: mockRect,
    attr: mockAttr,
    path: mockPath,
    transform: mockTrans,
    circle: mockCircle
}
mockAdd.mockReturnValue(svg)
mockGroup.mockReturnValue(svg)
mockRect.mockReturnValue(svg)
mockAttr.mockReturnValue(svg)
mockPath.mockReturnValue(svg)
mockTrans.mockReturnValue(svg)
mockCircle.mockReturnValue(svg)

describe('Task ', () => {
    let eEvent

    beforeEach(() => {
        eEvent = new Elements["EndEvent"](
            {
                $type: "bpmn:EndEvent",
                id: "end1",
                name: "End Event 1",
                moddleElement: {}
            },
            svg
        );
    })

    it('config() - verify the merge of options', () => {
        expect(eEvent.options).toEqual({
            "$type": "bpmn:EndEvent",
            "id": "end1",
            "marker": "EMPTY",
            "moddleElement": {},
            "name": "End Event 1",
            "scale": 40,
            "x": 100,
            "y": 100
        });
    })

    it('render() - Verify if use the library snap svg', () => {
        eEvent.render()
    })
})