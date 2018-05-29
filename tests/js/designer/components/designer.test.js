import {mount, shallow, shallowMount} from "@vue/test-utils"
import designer from "../../../../resources/assets/designer/components/designer.vue"

let svg
const mockGroup = jest.fn(() => svg)
const mockAdd = jest.fn(() => svg)
const mockDrag = jest.fn(() => svg)
const mockRect = jest.fn(() => svg)
const mockAttr = jest.fn(() => svg)
const mockPath = jest.fn(() => svg)
const mockTrans = jest.fn(() => svg)
const mockCircle = jest.fn(() => svg)
const mockClick = jest.fn(() => svg)
const mockDblClick = jest.fn(() => svg)

const mockNode = {
    getBoundingClientRect: jest.fn(() => svg)
};
const mockLeft = jest.fn(() => svg)
const mockTop = jest.fn(() => svg)
const mockBounding = () => svg

svg = {
    group: mockGroup,
    node: mockNode,
    add: mockAdd,
    drag: mockDrag,
    rect: mockRect,
    attr: mockAttr,
    path: mockPath,
    transform: mockTrans,
    circle: mockCircle,
    left: mockLeft,
    top: mockTop,
    click: mockClick,
    dblclick: mockDblClick
};

mockAdd.mockReturnValue(svg)
mockLeft.mockReturnValue(svg)
mockTop.mockReturnValue(svg)
mockGroup.mockReturnValue(svg)
mockRect.mockReturnValue(svg)
mockAttr.mockReturnValue(svg)
mockPath.mockReturnValue(svg)
mockTrans.mockReturnValue(svg)
mockCircle.mockReturnValue(svg)
mockClick.mockReturnValue(svg)
mockDblClick.mockReturnValue(svg)

Snap = () => svg
Dispatcher = {
    $on () {
    }
}

jest.mock("bpmn-moddle", () => jest.fn().mockImplementation(() => ({
    fromXML: (xmlInput) => {
        expect(xmlInput).toEqual("<?xml>")
    }
})))

describe("designer.vue", () => {
    let cmp, cmp2

    beforeEach(() => {
        cmp = shallowMount(designer, {
            propsData: {
                $parent: {
                    dispatcher: {$emit: {}}
                }
            }
        })
    })

    it("loadXML", () => {
        cmp.vm.loadXML("<?xml>")
    })

    it("createElement", () => {
        cmp.vm.createElement({
            target: {
                id: "bpmn:StartEvent",
                x: 5,
                y: 5
            }
        })
    })

    it("mouseMove() - Verify if the mousemove event updates the pan property in vue component", () => {
        cmp2 = shallowMount(designer, {propsData: {pan: {}}})
        cmp2.vm.mouseDown({
            pageX: 100,
            pageY: 100
        })
        cmp2.vm.mouseMove({
            pageX: 100,
            pageY: 100
        })
        expect(cmp2.vm.pan).toEqual({
                panStartX: 100,
                panStartY: 100,
                mouseDown: true,
                pageTop: 0,
                pageLeft: 0,
                panEndX: 100,
                panEndY: 100,
                panTop: 0,
                panLeft: 0
            }
        )
        cmp2.vm.mouseMove({
            pageX: -100,
            pageY: -100
        })
        expect(cmp2.vm.pan).toEqual({
            "mouseDown": true,
            "pageLeft": 0,
            "pageTop": 0,
            "panEndX": -100,
            "panEndY": -100,
            "panLeft": -200,
            "panStartX": 100,
            "panStartY": 100,
            "panTop": -200
        })
    })

    it("mouseUp() - Verify if the mouseup event updates the pan property in vue component", () => {
        cmp2 = shallowMount(designer, {propsData: {pan: {}}})
        cmp2.vm.mouseUp()
        expect(cmp2.vm.pan.mouseDown).toEqual(false)
    })

    it("onDragStartShape() - Verify if the mouseup event updates the shapeDrag in pan property", () => {
        cmp2 = shallowMount(designer, {propsData: {pan: {}}})
        let fn = cmp2.vm.onDragStartShape()
        fn()
        expect(cmp2.vm.pan.shapeDrag).toEqual(true)
    })

    it("onDragEndShape() - Verify if the mouseup event updates the shapeDrag in pan property", () => {
        cmp2 = shallowMount(designer, {propsData: {pan: {}}})
        let fn = cmp2.vm.onDragEndShape()
        fn()
        expect(cmp2.vm.pan.shapeDrag).toEqual(false)
    })
})
