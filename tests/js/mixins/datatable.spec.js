import { shallowMount } from '@vue/test-utils';
import datatable from '../../../resources/js/components/common/mixins/datatable';

let wrapper;

const fetchMock = jest.fn();

describe('Datatable Mixin', () => {
  beforeEach(() => {

    const $t = () => {
    };
    wrapper = shallowMount(datatable, {
      mocks: {$t, fetch: fetchMock}
    })
  });

  test('should create', () => {
    expect(wrapper.vm).toBeTruthy();
  });
});