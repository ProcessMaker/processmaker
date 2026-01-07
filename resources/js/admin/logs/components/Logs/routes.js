import { LogTable } from './LogTable';

export default {};

export const routes = [
  {
    name: 'logs.index',
    path: '/',
    redirect: '/email/errors',
  },
  // Email logs routes
  {
    name: 'logs.email',
    path: '/email/:logType',
    component: LogTable,
    props(route) {
      return {
        category: 'email',
        logType: route.params.logType,
      };
    },
  },
  // FlowGenie Agents logs routes
  {
    name: 'logs.agents.redirect',
    path: '/agents',
    redirect: '/agents/design',
  },
  {
    name: 'logs.agents',
    path: '/agents/:logType',
    component: LogTable,
    props(route) {
      return {
        category: 'agents',
        logType: route.params.logType,
      };
    },
  },
];

