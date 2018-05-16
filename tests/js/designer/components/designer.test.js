import {mount, shallow} from "@vue/test-utils";
import designer from "../../../../resources/assets/designer/components/designer.vue";

let svg;
const mockGroup = jest.fn(() => svg);
const mockAdd = jest.fn(() => svg);
const mockDrag = jest.fn(() => svg);
const mockRect = jest.fn(() => svg);
const mockAttr = jest.fn(() => svg);
const mockPath = jest.fn(() => svg);
const mockTrans = jest.fn(() => svg);
const mockCircle = jest.fn(() => svg);
const mockClick = jest.fn(() => svg);
const mockDblClick = jest.fn(() => svg);

const mockNode = {
    getBoundingClientRect: jest.fn(() => svg)
};
const mockLeft = jest.fn(() => svg);
const mockTop = jest.fn(() => svg);
const mockBounding = () => svg;

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

mockAdd.mockReturnValue(svg);
mockLeft.mockReturnValue(svg);
mockTop.mockReturnValue(svg);
mockGroup.mockReturnValue(svg);
mockRect.mockReturnValue(svg);
mockAttr.mockReturnValue(svg);
mockPath.mockReturnValue(svg);
mockTrans.mockReturnValue(svg);
mockCircle.mockReturnValue(svg);
mockClick.mockReturnValue(svg);
mockDblClick.mockReturnValue(svg);

Snap = () => svg;
Dispatcher = {
    $on () {
    }
};

jest.mock("bpmn-moddle", () => jest.fn().mockImplementation(() => ({
    fromXML: (xmlInput) => {
        expect(xmlInput).toEqual("<?xml>");
    }
})));

describe("designer.vue", () => {
    let cmp;

    beforeEach(() => {
        cmp = shallow(designer, {
            propsData: {
                $parent: {
                    dispatcher: {$emit: {}}
                }
            }
        });
    });

    it("loadXML", () => {
        cmp.vm.loadXML("<?xml>");
    });

    it("createElement", () => {
        cmp.vm.createElement({
            target: {
                id: "bpmn:StartEvent",
                x: 5,
                y: 5
            }
        });
    });
});
