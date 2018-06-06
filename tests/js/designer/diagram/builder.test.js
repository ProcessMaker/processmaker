import {mount, shallow} from "@vue/test-utils"
import {Builder} from "../../../../resources/assets/js/designer/diagram/builder"
import {Elements} from "../../../../resources/assets/js/designer/diagram/elements"
import Vue from "vue"

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
        let fn = eEvent.onClickShape(ev)
        fn()
        //expect(mockRect.mock.calls.length).toBe(1)
    });
});
