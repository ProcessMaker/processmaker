import {mount, shallow, shallowMount} from "@vue/test-utils"
import topToolbar from "../../../../resources/js/designer/components/toptoolbar.vue"
import EventBus from "../../../../resources/js/designer/lib/event-bus"

describe("topToolbar.vue", () => {
    let cmp
    beforeEach(() => {
        cmp = shallowMount(topToolbar, {
            propsData: {}
        })
    })

    it("uploadBPMN - Click from button upload bpmn file", () => {
        expect(cmp.vm.uploadBPMN()).toEqual(cmp.vm.$el.querySelector("#uploadBPMN"))
    })
})
