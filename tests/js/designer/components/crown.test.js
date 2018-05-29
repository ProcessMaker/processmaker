import {mount, shallow, shallowMount} from "@vue/test-utils"
import crown from "../../../../resources/assets/designer/components/crown.vue"

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

})