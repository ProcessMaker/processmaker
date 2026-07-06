import { LogContainer } from './components/Logs/LogContainer';
import { routes } from './components/Logs/routes';

// eslint-disable-next-line no-undef
Vue.use(VueRouter);

// eslint-disable-next-line no-undef
const router = new VueRouter({
  mode: 'history',
  base: '/admin/logs',
  routes,
});

window.Vue.component('admin-logs', LogContainer);

document.addEventListener('DOMContentLoaded', () => {
  new window.Vue({
    el: '#admin-logs-main',
    router,
    components: {
      LogContainer,
    },
    render: (h) => h(LogContainer),
  });
});

