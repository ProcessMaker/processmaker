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
    it("createElement", () => {
        cmp.vm.show({
            x: 5,
            y: 6
        })
        expect(cmp.vm.x).toEqual(5)
        expect(cmp.vm.y).toEqual(6)
    })

})