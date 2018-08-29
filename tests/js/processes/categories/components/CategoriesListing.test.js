import { mount, shallowMount } from '@vue/test-utils'
import CategoriesListing from '@pmjs/processes/categories/components/CategoriesListing';

let wrapper = null;

describe('Process Categories', () => {
    beforeEach(() => {
        // TODO: move to manual __mock__
        global.ProcessMaker = {
            apiClient: {
                get: () => { return { then: () => { } } },
            }
        };
        wrapper = shallowMount(CategoriesListing);
    })
    it('foo', () => {
        expect(wrapper.vm.orderBy).toEqual("name");
    });
});