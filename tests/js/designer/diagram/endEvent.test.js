import {mount, shallow} from "@vue/test-utils"
import {Elements} from "../../../../resources/assets/designer/diagram/elements"

let svg
const mockGroup = jest.fn(() => svg)
const mockAdd = jest.fn(() => svg)
const mockDrag = jest.fn(() => svg)
const mockRect = jest.fn(() => svg)
const mockAttr = jest.fn(() => svg)
const mockPath = jest.fn(() => svg)
const mockTrans = jest.fn(() => svg)
const mockCircle = jest.fn(() => svg)
const mockBox = jest.fn(() => svg)
const mockRemove = jest.fn(() => svg)

svg = {
    group: mockGroup,
    add: mockAdd,
    drag: mockDrag,
    rect: mockRect,
    attr: mockAttr,
    path: mockPath,
    transform: mockTrans,
    circle: mockCircle,
    getBBox: mockBox,
    remove: mockRemove
}
mockAdd.mockReturnValue(svg)
mockGroup.mockReturnValue(svg)
mockRect.mockReturnValue(svg)
mockAttr.mockReturnValue(svg)
mockPath.mockReturnValue(svg)
mockTrans.mockReturnValue(svg)
mockCircle.mockReturnValue(svg)
mockBox.mockReturnValue(svg)

describe("Task ", () => {
    let eEvent;

    beforeEach(() => {
        eEvent = new Elements.EndEvent(
            {
                $type: "bpmn:EndEvent",
                id: "end1",
                name: "End Event 1",
                moddleElement: {}
            },
            svg
        );
    });

    it("config() - verify the merge of options", () => {
        expect(eEvent.options).toEqual({
            $type: "bpmn:EndEvent",
            id: "end1",
            marker: "EMPTY",
            moddleElement: {},
            name: "End Event 1",
            scale: 40,
            x: 100,
            y: 100
        });
    });

    it("render() - Verify if use the library snap svg", () => {
        eEvent.render();
    });

    it("createSelectionBorder() - Verify if the shape create a selection Border from snapSvg object", () => {
        eEvent.createSelectionBorder();
        expect(mockRect.mock.calls.length).toBe(1);
    });

    it("remove() - Verify if the shape is remove", () => {
        eEvent.createSelectionBorder();
        eEvent.remove();
        expect(mockRemove.mock.calls.length).toBe(2);
    });
});
