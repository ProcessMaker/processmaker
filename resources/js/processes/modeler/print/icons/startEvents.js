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
};

export default startEvents;