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
};

export default intermediateEvents;