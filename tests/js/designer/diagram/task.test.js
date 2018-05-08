import {mount, shallow} from '@vue/test-utils'
import {Elements} from '../../../../resources/assets/designer/diagram/elements'

let svg = {
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
        return this;
    },
    drag: function () {
        expect(true).toEqual(true)
    },
    rect: function () {
        expect(true).toEqual(true)
        return this
    }
}

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