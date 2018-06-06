import {mount, shallow, shallowMount} from "@vue/test-utils"
import designer from "../../../../resources/assets/js/designer/components/svgcanvas.vue"

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
})
