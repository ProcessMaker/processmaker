import {mount, shallowMount} from '@vue/test-utils'
import CategoriesListing from '@pmjs/processes/categories/components/CategoriesListing';

let wrapper = null;

describe('Process Categories', () => {
  beforeEach(() => {
    // TODO: move to manual __mock__
    global.ProcessMaker = {
      apiClient: {
        get: () => {
          return {
            then: () => {
            }
          }
        },
      }
    };
    const $t = () => {
    };
    wrapper = shallowMount(CategoriesListing, {
      mocks: {$t},
    });
  });

  it('foo', () => {
    expect(wrapper.vm.orderBy).toEqual("name");
  });
});