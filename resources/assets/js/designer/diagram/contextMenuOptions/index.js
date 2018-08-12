import task from "./task"
import endEvent from "./endEvent"
import scriptTask from "./scriptTask"

export const ContextMenuOptions = Object.assign({
        "default": [
            {
                text: 'Properties',
                handler: () => {
                    alert("Not supported")
                }
            }
        ],
        getOptions: (type = null, eventDefinition = null) => {
            return ContextMenuOptions[type] ? ContextMenuOptions[type] : ContextMenuOptions["default"]
        }
    },
    task,
    endEvent,
    scriptTask
)