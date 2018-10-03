import {mount, shallow, shallowMount} from "@vue/test-utils"
import toolbar from "../../../../resources/js/designer/components/toolbar.vue"
import EventBus from "../../../../resources/js/designer/lib/event-bus"

describe("toolbar.vue", () => {
    let cmp
    beforeEach(() => {
        cmp = shallowMount(toolbar, {
            propsData: {}
        })
    })

    it("createElement", () => {
        EventBus.$on("drag/toolbar/end", (value) => {
            expect(value).toEqual({prop: "test"})
        })
        cmp.vm.createElement({prop: "test"})
    })
})
