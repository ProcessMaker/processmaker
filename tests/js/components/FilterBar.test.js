import { mount } from '@vue/test-utils'

import FilterBar from '../../../resources/assets/js/components/FilterBar'


let wrapper = null;

describe("FilterBar", () => {
    beforeEach(() => {
        wrapper = mount(FilterBar)

    })
    test("should have empty filter text to begin", () => {
        expect(wrapper.vm.filterText).toBe('')
    })
    test("resets filter by calling resetFilter and fires filter-reset", () => {
        wrapper.vm.filterText = 'sometext'
        expect(wrapper.vm.filterText).toBe('sometext')
        // Now reset it and ensure we're getting an event fired
       // Check for event fired
        // But we need to mock it
        let fireMock = jest.fn();
        // Replace the $events property with one that has a mock fire
        wrapper.vm.$events = {
            fire: fireMock
        }
        wrapper.vm.resetFilter()
        expect(wrapper.vm.filterText).toBe('')
        expect(fireMock.mock.calls.length).toBe(1)
        expect(fireMock.mock.calls[0][0]).toBe('filter-reset')
    })
    test("does not perform filter if filter query length is less than 3", () => {
        wrapper.vm.filterText = 'tes'
        let fireMock = jest.fn()
        // Replace the $events property with one that has a mock fire
        wrapper.vm.$events = {
            fire: fireMock
        }
        wrapper.vm.doFilter()
        expect(fireMock.mock.calls.length).toBe(0)
    })
    test("fires filter-set when performing query and query length is greater than 3", () => {
        wrapper.vm.filterText = 'test'
        let fireMock = jest.fn()
        // Replace the $events property with one that has a mock fire
        wrapper.vm.$events = {
            fire: fireMock
        }
        wrapper.vm.doFilter()
        expect(fireMock.mock.calls.length).toBe(1)
        expect(fireMock.mock.calls[0][0]).toBe('filter-set')
    })
})