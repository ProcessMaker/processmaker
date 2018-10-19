import {mount, shallow, shallowMount} from "@vue/test-utils"
import {Elements} from "../../../../resources/js/designer/diagram/elements"

describe("Elements", () => {
    it("Instance of keys in elements", () => {
        expect(Elements['startevent']).toBeDefined()
        expect(Elements['intermediatethrowevent']).toBeDefined()
        expect(Elements['endevent']).toBeDefined()
        expect(Elements['task']).toBeDefined()
        expect(Elements['servicetask']).toBeDefined()
        expect(Elements['scripttask']).toBeDefined()
        expect(Elements['sequenceflow']).toBeDefined()
        expect(Elements['messageflow']).toBeDefined()
        expect(Elements['inclusivegateway']).toBeDefined()
        expect(Elements['parallelgateway']).toBeDefined()
        expect(Elements['exclusivegateway']).toBeDefined()
        expect(Elements['dataobject']).toBeDefined()
        expect(Elements['datastore']).toBeDefined()
        expect(Elements['participant']).toBeDefined()
        expect(Elements['lane']).toBeDefined()
        expect(Elements['group']).toBeDefined()
        expect(Elements['blackboxpool']).toBeDefined()
        expect(Elements['callactivity']).toBeDefined()
    })
})