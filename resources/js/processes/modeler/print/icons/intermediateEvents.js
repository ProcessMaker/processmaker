const intermediateEventColor = '#FBBE02';
const intermediateEvents = {
  'bpmn:intermediateCatchEvent:timerEventDefinition':
  {
    type: 'icon',
    class: 'far fa-clock',
    style: `color:${intermediateEventColor};`,
    title: 'Intermediate Timer Event',
  },
  'bpmn:intermediateThrowEvent:messageEventDefinition':
  {
    type: 'icon',
    class: 'fa fa-envelope',
    style: `color:${intermediateEventColor};`,
    title: 'Intermediate Message Throw Event',
  },
  'bpmn:intermediateCatchEvent:messageEventDefinition':
  {
    type: 'icon',
    class: 'far fa-envelope',
    style: `color:${intermediateEventColor};`,
    title: 'Intermediate Message Catch Event',
  },
  'bpmn:intermediateCatchEvent:signalEventDefinition':
  {
    type: 'image',
    src: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTkiIGhlaWdodD0iMTciIHZpZXdCb3g9IjAgMCAxOSAxNyIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICAgIDxwYXRoIGQ9Ik0xMC43OTkgMS4yNUwxOC4xNjAzIDE0QzE4LjczNzYgMTUgMTguMDE1OSAxNi4yNSAxNi44NjEyIDE2LjI1SDIuMTM4NzhDMC45ODQwODQgMTYuMjUgMC4yNjIzOTYgMTUgMC44Mzk3NDYgMTRMOC4yMDA5NiAxLjI1QzguNzc4MzEgMC4yNSAxMC4yMjE3IDAuMjUgMTAuNzk5IDEuMjVaIgogICAgICAgICAgc3Ryb2tlPSIjRkJCRTAyIi8+Cjwvc3ZnPgo=',
    title: 'Intermediate Signal Catch Event',
  },
  'bpmn:intermediateThrowEvent:signalEventDefinition':
  {
    type: 'image',
    src: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTkiIGhlaWdodD0iMTciIHZpZXdCb3g9IjAgMCAxOSAxNyIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICAgIDxwYXRoIGQ9Ik0xMC43OTkgMS4yNUwxOC4xNjAzIDE0QzE4LjczNzYgMTUgMTguMDE1OSAxNi4yNSAxNi44NjEyIDE2LjI1SDIuMTM4NzhDMC45ODQwODQgMTYuMjUgMC4yNjIzOTYgMTUgMC44Mzk3NDYgMTRMOC4yMDA5NiAxLjI1QzguNzc4MzEgMC4yNSAxMC4yMjE3IDAuMjUgMTAuNzk5IDEuMjVaIgogICAgICAgICAgZmlsbD0iI0ZBQkQyRCIvPgo8L3N2Zz4K',
    title: 'Intermediate Signal Throw Event',
  },
};

export default intermediateEvents;