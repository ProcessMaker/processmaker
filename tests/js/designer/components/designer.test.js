import {mount, shallow} from '@vue/test-utils'
import designer from '../../../../resources/assets/designer/components/designer.vue'
let Snap = global.Snap;

Snap = () => {
    return jest.fn().mockImplementation(() => {
        return {};
    });
}

jest.mock('bpmn-moddle', () => {
    return jest.fn().mockImplementation(() => {
        return {
            fromXML: (xmlInput) => {
                expect(xmlInput).toEqual('<?xml>')
            }
        };
    });
})

describe('designer.vue', () => {
    let cmp

    beforeEach(() => {
        cmp = shallow(designer)
    })

    it('loadXML', () => {
        cmp.vm.loadXML('<?xml>')
    })
})