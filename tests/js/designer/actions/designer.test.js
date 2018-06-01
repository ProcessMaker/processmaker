import {mount, shallow} from '@vue/test-utils'
import actions from '../../../../resources/assets/js/designer/actions'

describe('designer.vue', () => {
    beforeEach(() => {

    })

    it('Actions', () => {
        expect(actions.designer.drag.toolbar.end({})).toEqual({
            type: "designer/drag/toolbar/end",
            payload: {}
        })
        expect(actions.designer.drag.toolbar.start({})).toEqual({
            type: "designer/drag/toolbar/start",
            payload: {}
        })

        expect(actions.designer.drag.shape.start({})).toEqual({
            type: "designer/drag/shape/start",
            payload: {}
        })

        expect(actions.designer.drag.shape.end({})).toEqual({
            type: "designer/drag/shape/end",
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