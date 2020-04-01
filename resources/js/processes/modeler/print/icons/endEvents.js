const endEventColor = '#ED4757';
const endEvents = {
  'bpmn:endEvent':
  {
    type: 'icon',
    class: 'far fa-circle',
    style: `color:${endEventColor};`,
    title: 'End Event',
  },
  'bpmn:endEvent:messageEventDefinition':
  {
    type: 'icon',
    class: 'far fa-envelope',
    style: `color:${endEventColor};`,
    title: 'Message End Event',
  },
  'bpmn:endEvent:errorEventDefinition':
  {
    type: 'image',
    src: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTEiIGhlaWdodD0iMTMiIHZpZXdCb3g9IjAgMCAxMSAxMyIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTcuNzM4NDQgNy4wMjgyOEM4LjgyNTY1IDQuNjg1MTIgOS45MTI3OSAyLjM0MzEgMTEgMEMxMC4xMjkzIDQuMTgwNjIgOS4yNTk1MSA4LjM1OTIxIDguMzg4NzcgMTIuNTM4OEM2Ljk2ODk2IDEwLjgxMjEgNS41NDkxNSA5LjA4NDI3IDQuMTI5MzQgNy4zNTc1OUMyLjc1MzI0IDkuMjM4MzcgMS4zNzYxIDExLjExOTIgMCAxM0MxLjIyODE4IDkuMTQwMjMgMi40NTUzNyA1LjI4MDQ1IDMuNjgzNTUgMS40MjA2MUM1LjAzNDgyIDMuMjg5ODQgNi4zODcxMiA1LjE1OTA2IDcuNzM4NDQgNy4wMjgyOFoiIGZpbGw9IiNFRDQ3NTciLz4KPC9zdmc+Cg==',
    title: 'Error End Event',
  },
  'bpmn:endEvent:signalEventDefinition':
  {
    type: 'image',
    src: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTkiIGhlaWdodD0iMTciIHZpZXdCb3g9IjAgMCAxOSAxNyIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEwLjc5OSAxLjI1TDE4LjE2MDMgMTRDMTguNzM3NiAxNSAxOC4wMTU5IDE2LjI1IDE2Ljg2MTIgMTYuMjVIMi4xMzg3OEMwLjk4NDA4NCAxNi4yNSAwLjI2MjM5NiAxNSAwLjgzOTc0NiAxNEw4LjIwMDk2IDEuMjVDOC43NzgzMSAwLjI1IDEwLjIyMTcgMC4yNSAxMC43OTkgMS4yNVoiCiAgICAgIGZpbGw9IiNFRDQ3NTciLz4KPC9zdmc+Cg==',
    title: 'Signal End Event',
  },
};
export default endEvents;