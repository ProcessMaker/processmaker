import {mount, shallow} from '@vue/test-utils'
import actions from '../../../../resources/assets/designer/actions'

describe('designer.vue', () => {
    beforeEach(() => {

    })

    it('Actions', () => {
        expect(actions.designer.drag.end({})).toEqual({
            type: "designer/drag/end",
            payload: {}
        })
        expect(actions.designer.drag.start({})).toEqual({
            type: "designer/drag/start",
            payload: {}
        })

        expect(actions.designer.zoom.in({})).toEqual({
            type: "designer/zoom/in",
            payload: {}
        })

        expect(actions.designer.zoom.out({})).toEqual({
            type: "designer/zoom/out",
            payload: {}
        })

        expect(actions.designer.zoom.reset({})).toEqual({
            type: "designer/zoom/reset",
            payload: {}
        })
    })
})