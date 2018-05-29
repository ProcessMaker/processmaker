import {mount, shallow} from "@vue/test-utils"
import {Elements} from "../../../../resources/assets/designer/diagram/elements"
import Crown from "../../../../resources/assets/designer/components/crown.vue"
import Vue from "vue"


let svg
Dispatcher = new Vue()
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
const mockData = jest.fn(() => svg)
const mockNode = {
    getBoundingClientRect: jest.fn(() => svg)
};


svg = {
    group: mockGroup,
    data: mockData,
    add: mockAdd,
    drag: mockDrag,
    rect: mockRect,
    attr: mockAttr,
    path: mockPath,
    transform: mockTrans,
    circle: mockCircle,
    getBBox: mockBox,
    remove: mockRemove,
    node: mockNode
}
mockAdd.mockReturnValue(svg)
mockTrans.mockReturnValue(svg)
mockGroup.mockReturnValue(svg)
mockRect.mockReturnValue(svg)
mockAttr.mockReturnValue(svg)
mockPath.mockReturnValue(svg)
mockTrans.mockReturnValue(svg)
mockCircle.mockReturnValue(svg)
mockBox.mockReturnValue(svg)
mockData.mockReturnValue(svg)


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
        mockAdd.mockClear()
        mockGroup.mockClear()
        mockRect.mockClear()
        mockAttr.mockClear()
        mockPath.mockClear()
        mockTrans.mockClear()
        mockCircle.mockClear()
        mockBox.mockClear()
        mockData.mockClear()
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

    it("onMove() - Verify if the execute this event and use the library", () => {
        let fn = eEvent.onMove()
        fn()
        expect(mockAttr.mock.calls.length).toBe(1)
        expect(mockData.mock.calls.length).toBe(2)
    });

    it("onDragStart() - Verify if the execute this event and use the library", () => {
        let fn = eEvent.onDragStart()
        fn()
        expect(mockTrans.mock.calls.length).toBe(1)
        expect(mockData.mock.calls.length).toBe(1)
    });

    it("onDragEnd() - Verify if the dx and dy are reset", () => {
        let fn = eEvent.onDragEnd()
        eEvent.dx = 5
        eEvent.dy = 5
        fn()
        expect(eEvent.dy).toBe(null)
        expect(eEvent.dx).toBe(null)
    });

    it("createCrown() - Verify if the crown has been created", () => {
        expect(eEvent.createCrown()).toBeInstanceOf(Vue.extend(Crown))
    });

    it("removeCrown() - Verify if the crown has been removed", () => {
        eEvent.createCrown()
        expect(eEvent.removeCrown()).toEqual(null)
    });
});
