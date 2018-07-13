import {mount, shallow, shallowMount} from "@vue/test-utils"
import {Elements} from "../../../../../resources/assets/js/designer/diagram/elements"
import {JointElements} from "../../../../../resources/assets/js/designer/diagram/jointElements"
import joint from "jointjs"

document.body.innerHTML =
    '<div id ="svgCanvas">' +
    '</div>';

describe("Elements", () => {
    let ev, graph, paper
    beforeEach(() => {
        graph = new joint.dia.Graph
        paper = {}
        ev = new Elements["intermediatetimerevent"]({
            id: "timer",
            x: 10,
            y: 10,
            width: 40,
            height: 40
        }, graph, paper)
    })

    it("render - function to render the event", () => {
        ev.render()
        expect(ev.shape).toBeInstanceOf(JointElements.IntermediateTimerEvent)
    })
})