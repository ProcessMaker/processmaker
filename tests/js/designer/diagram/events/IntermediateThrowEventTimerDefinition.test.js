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
        ev = new Elements["intermediatethrowevent"]({
            id: "timer",
            type: "intermediatethrowevent",
            eventDefinition: "timerEventDefinition",
            bounds: {
                x: 10,
                y: 10,
                width: 40,
                height: 40
            },
            attributes: {
                name: "test"
            }
        }, graph, paper)
    })

    it("render - function to render the event", () => {
        ev.render()
        expect(ev.getShape()).toBeInstanceOf(JointElements.TimerEventDefinition)
    })
})