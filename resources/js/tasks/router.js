import Vue from "vue";
import VueRouter from "vue-router";

Vue.use(VueRouter);

const InboxRoutePlaceholder = { template: "<div />" };

const loadProcess = () => import(
  /* webpackChunkName: "inbox-process" */
  "../processes-catalogue/components/Process"
);

const loadDashboardViewer = () => import(
  /* webpackChunkName: "inbox-dashboard" */
  "./components/DashboardViewer.vue"
);

const parseSessionJson = (key) => {
  const value = sessionStorage.getItem(key);
  if (!value) {
    return null;
  }

  return JSON.parse(value);
};

const screen = parseSessionJson('dashboard_screen');
const formData = parseSessionJson('dashboard_formData');

const router = new VueRouter({
  mode: "history",
  base: "/inbox",
  routes: [
    {
      path: "/process/:processId",
      name: "process-browser",
      component: loadProcess,
      props: route => ({
        processId: parseInt(route.params.processId) || null,
        process: null,
        ellipsisPermission: window.ProcessMaker.ellipsisPermission
      })
    },
    {
      path: "",
      name: "inbox",
      component: InboxRoutePlaceholder,
    },
    {
      path: "/dashboard/:dashboardId",
      name: "dashboard",
      component: loadDashboardViewer,
      props: route => ({
        dashboardId: route.params.dashboardId || null,
        screen: route.params.screen || screen,
        formData: route.params.formData || formData
      })
    }
  ]
});

export default router;
