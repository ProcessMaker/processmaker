import {mount} from '@vue/test-utils'
import ConfirmModal from '../../../resources/js/components/Confirm';

let wrapper = null;

describe('Component Confirm Modal', () => {

    beforeEach(() => {
        const title = 'confirm test',
            message = 'confirm message',
            variant = 'danger';

        wrapper = mount(ConfirmModal, {
            propsData: {
                title: title,
                message: message,
                variant: variant,
                confirm: null
            }
        });
    });

    test('should set props with data default', () => {
        const title = 'confirm test',
            message = 'confirm message',
            variant = 'danger';

        wrapper = mount(ConfirmModal, {
            propsData: {
                title: title,
                message: message,
                variant: variant
            }
        });
        expect(wrapper.find('.modal-title').text()).toBe(title);
        expect(wrapper.find('.modal-body span').text()).toBe(message);

    });

    test('button click close', () => {
        const button = wrapper.find('.close');
        button.trigger('click');
        expect(wrapper.emitted().confirm).toEqual(undefined)
    });

    test('button click cancel', () => {
        const button = wrapper.find('#cancel');
        button.trigger('click');
        expect(wrapper.emitted().confirm).toEqual([[false]])
    });

    test('button click yes', () => {
        const button = wrapper.find('#confirm');
        button.trigger('click');
        expect(wrapper.emitted().confirm).toEqual([[true]])
    });

    test('button yes and execute callback', () => {
        const title = 'confirm test',
            message = 'confirm message',
            variant = 'danger',
            callback = function () {
                this.$emit('testSuccess', true);
            };

        wrapper = mount(ConfirmModal, {
            propsData: {
                title: title,
                message: message,
                variant: variant,
                callback: callback
            }
        });

        const button = wrapper.find('#confirm');
        button.trigger('click');
        expect(wrapper.emitted().testSuccess).toEqual([[true]])

    });

});
