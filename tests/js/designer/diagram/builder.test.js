import {mount, shallow} from "@vue/test-utils";
import {Builder} from "../../../../resources/assets/designer/diagram/builder";
import {Elements} from "../../../../resources/assets/designer/diagram/elements";

let svg;
const mockGroup = jest.fn(() => svg);
const mockAdd = jest.fn(() => svg);
const mockDrag = jest.fn(() => svg);
const mockRect = jest.fn(() => svg);
const mockAttr = jest.fn(() => svg);
const mockPath = jest.fn(() => svg);
const mockTrans = jest.fn(() => svg);
const mockCircle = jest.fn(() => svg);
const mockBox = jest.fn(() => svg);
const mockRemove = jest.fn(() => svg);

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
};
mockAdd.mockReturnValue(svg);
mockGroup.mockReturnValue(svg);
mockRect.mockReturnValue(svg);
mockAttr.mockReturnValue(svg);
mockPath.mockReturnValue(svg);
mockTrans.mockReturnValue(svg);
mockCircle.mockReturnValue(svg);
mockBox.mockReturnValue(svg);

describe("Builder Class ", () => {
    let eEvent,
        ev;

    beforeEach(() => {
        eEvent = new Builder(svg, Dispatcher);
        ev = new Elements.EndEvent(
            {
                $type: "bpmn:EndEvent",
                id: "end1",
                name: "End Event 1",
                moddleElement: {}
            },
            svg
        );
        ev.render();
    });

    it("onClickShape() - Verify the event click in a shape", () => {
        let fn = eEvent.onClickShape(ev);
        fn();
        expect(mockRect.mock.calls.length).toBe(1);
    });

    it("RemoveSelection() - Verify the event click in a shape", () => {
        let fn = eEvent.onClickShape(ev);
        fn();
        eEvent.removeSelectionBorder();
        expect(mockRemove.mock.calls.length).toBe(1);
    });
});
