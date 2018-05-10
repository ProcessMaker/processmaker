import {mount, shallow} from '@vue/test-utils'
import designer from '../../../../resources/assets/designer/components/designer.vue'


Snap = () => {
    return {
        node: {
            getBoundingClientRect: function () {
                return {
                    left: "left",
                    top: "top"
                }
            }
        },
        group: function () {
            return this
        },
        circle: function () {
            return {
                attr: function () {
                    expect(true).toEqual(true)
                }
            }
        },
        path: function () {
            return this
        },
        transform: function () {
            return this
        },
        attr: function () {
            expect(true).toEqual(true)
        },
        add: function () {
            expect(true).toEqual(true)
        },
        drag: function () {
            expect(true).toEqual(true)
        }
    }
}

Dispatcher = {
    "$on": function () {
    }
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
        cmp = shallow(designer, {
            propsData: {
                "$parent": {
                    dispatcher: {"$emit": {}}
                }
            }
        })
    })

    it('loadXML', () => {
        cmp.vm.loadXML('<?xml>')
    })

    it('createElement', () => {
        cmp.vm.createElement({
            target: {
                id: "bpmn:StartEvent",
                x: 5,
                y: 5
            }
        })
    })

})