import {mount, shallow} from '@vue/test-utils'
import {Elements} from '../../../../resources/assets/designer/diagram/elements'

let svg
const mockGroup = jest.fn(() => svg);
const mockAdd = jest.fn(() => svg);
const mockDrag = jest.fn(() => svg);
const mockRect = jest.fn(() => svg);
const mockAttr = jest.fn(() => svg);

svg = {
    group: mockGroup,
    add: mockAdd,
    drag: mockDrag,
    rect: mockRect,
    attr: mockAttr
}
mockAdd.mockReturnValue(svg)
mockGroup.mockReturnValue(svg)
mockRect.mockReturnValue(svg)
mockAttr.mockReturnValue(svg)

describe('Task', () => {
    let task

    beforeEach(() => {
        task = new Elements["Task"](
            {
                $type: "bpmn:Task",
                id: "t1",
                name: "Task 1",
                moddleElement: {}
            },
            svg
        );
    })

    it('config', () => {
        expect(task.options).toEqual({
            id: 't1',
            x: null,
            y: null,
            scaleX: 100,
            scaleY: 80,
            rounded: 10,
            attr: {fill: '#FFF', stroke: '#000', strokeWidth: 2},
            "$type": 'bpmn:Task',
            name: 'Task 1',
            moddleElement: {}
        });
    })

    it('render', () => {
        task.render()
    })
})