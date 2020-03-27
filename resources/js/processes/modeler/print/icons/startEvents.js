const startEventColor = '#00BF9C';
const startEvents = {
  'bpmn:startEvent':
  {
    type: 'icon',
    class: 'far fa-circle',
    style: `color:${startEventColor};`,
    title: 'Start Event',
  },
  'bpmn:startEvent:timerEventDefinition':
  {
    type: 'icon',
    class: 'far fa-clock',
    style: `color:${startEventColor};`,
    title: 'Start Timer Event',
  },
  'bpmn:startEvent:messageEventDefinition':
  {
    type: 'icon',
    class: 'far fa-envelope',
    style: `color:${startEventColor};`,
    title: 'Message Start Event',
  },
  'bpmn:startEvent:signalEventDefinition':
  {
    type: 'image',
    src: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTkiIGhlaWdodD0iMTciIHZpZXdCb3g9IjAgMCAxOSAxNyIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICAgIDxwYXRoIGQ9Ik0xMC43OTkgMS4yNUwxOC4xNjAzIDE0QzE4LjczNzYgMTUgMTguMDE1OSAxNi4yNSAxNi44NjEyIDE2LjI1SDIuMTM4NzhDMC45ODQwODQgMTYuMjUgMC4yNjIzOTYgMTUgMC44Mzk3NDYgMTRMOC4yMDA5NiAxLjI1QzguNzc4MzEgMC4yNSAxMC4yMjE3IDAuMjUgMTAuNzk5IDEuMjVaIgogICAgICAgICAgc3Ryb2tlPSIjMDBCRjlDIi8+Cjwvc3ZnPgo=',
    title: 'Signal Start Event',
  },
};

export default startEvents;