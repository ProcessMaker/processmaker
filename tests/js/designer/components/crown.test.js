import {mount, shallow, shallowMount} from "@vue/test-utils"
import crown from "../../../../resources/assets/js/designer/components/crown.vue"

describe("crown.vue", () => {
    let cmp
    beforeEach(() => {
        cmp = shallowMount(crown, {
            propsData: {}
        })
    })

    it("Instance of Crown", () => {
        expect(cmp.vm.$el.querySelector('.item-crown')).toBeInstanceOf(HTMLElement)
    })
    it("removeElement - Verify if the Crown removes a element", () => {
        cmp.vm.remove()
        expect(cmp.vm.visible).toEqual(false)
    })

    it("show - Verify if the Crown is visible", () => {
        cmp.vm.show({
            x: 1,
            y: 1
        })
        expect(cmp.vm.x).toEqual(1)
        expect(cmp.vm.y).toEqual(1)
    })

    it("hide - Verify if the Crown is not visible", () => {
        cmp.vm.hide()
        expect(cmp.vm.visible).toEqual(false)
    })

})