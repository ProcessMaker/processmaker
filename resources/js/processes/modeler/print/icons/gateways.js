const gatewayColor = '#000';
const gateways = {
  'bpmn:exclusiveGateway':
  {
    type: 'icon',
    class: 'fa fa-times',
    style: `color:${gatewayColor};`,
    title: 'Exclusive Gateway',
  },
  'bpmn:inclusiveGateway':
  {
    type: 'icon',
    class: 'far fa-circle',
    style: `color:${gatewayColor};`,
    title: 'Inclusive Gateway',
  },
  'bpmn:parallelGateway':
  {
    type: 'icon',
    class: 'fa fa-plus',
    style: `color:${gatewayColor};`,
    title: 'Parallel Gateway',
  },
  'bpmn:eventBasedGateway':
  {
    type: 'image',
    src: 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB3aWR0aD0iMTU0cHgiIGhlaWdodD0iMTU0cHgiIHZpZXdCb3g9IjAgMCAxNTQgMTU0IiB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiPgogICAgPCEtLSBHZW5lcmF0b3I6IFNrZXRjaCA1MC4yICg1NTA0NykgLSBodHRwOi8vd3d3LmJvaGVtaWFuY29kaW5nLmNvbS9za2V0Y2ggLS0+CiAgICA8dGl0bGU+ZXZlbnQtYmFzZWQtZ2F0ZXdheS1zeW1ib2w8L3RpdGxlPgogICAgPGRlc2M+Q3JlYXRlZCB3aXRoIFNrZXRjaC48L2Rlc2M+CiAgICA8ZGVmcz48L2RlZnM+CiAgICA8ZyBpZD0iUGFnZS0xIiBzdHJva2U9Im5vbmUiIHN0cm9rZS13aWR0aD0iMSIgZmlsbD0ibm9uZSIgZmlsbC1ydWxlPSJldmVub2RkIj4KICAgICAgICA8ZyBpZD0iZXZlbnQtYmFzZWQtZ2F0ZXdheS1zeW1ib2wiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDQuMDAwMDAwLCA0LjAwMDAwMCkiIHN0cm9rZT0iIzAwMDAwMCI+CiAgICAgICAgICAgIDxjaXJjbGUgaWQ9Ik92YWwiIHN0cm9rZS13aWR0aD0iOCIgY3g9IjczIiBjeT0iNzMiIHI9IjczIj48L2NpcmNsZT4KICAgICAgICAgICAgPGNpcmNsZSBpZD0iT3ZhbC1Db3B5IiBzdHJva2Utd2lkdGg9IjgiIGN4PSI3My41IiBjeT0iNzMuNSIgcj0iNTUuNSI+PC9jaXJjbGU+CiAgICAgICAgICAgIDxwb2x5Z29uIGlkPSJQb2x5Z29uIiBzdHJva2Utd2lkdGg9IjciIHBvaW50cz0iNzMuMDcyNTgwNiA0MSAxMDcuMTQ1MTYxIDY1LjgyNzc5MDcgOTQuMTMwNTkzNiAxMDYgNTIuMDE0NTY3NyAxMDYgMzkgNjUuODI3NzkwNyI+PC9wb2x5Z29uPgogICAgICAgIDwvZz4KICAgIDwvZz4KPC9zdmc+',
    title: 'Event Based Gateway',
  },
};
export default gateways;