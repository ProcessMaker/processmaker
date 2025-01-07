import Vue from "vue";
import VueRouter from "vue-router";
import ProcessBrowser from "./components/ProcessBrowser.vue";
import DashboardViewer from "./components/DashboardViewer.vue";

Vue.use(VueRouter);

const router = new VueRouter({
  mode: "history",
  base: "/tasks",
  routes: [
    {
      path: "/process/:processId",
      name: "process-browser",
      component: ProcessBrowser,
      props: (route) => ({
        processId: parseInt(route.params.processId) || null,
        process: null,
      }),
    },
    {
      path: "/dashboard/:dashboardId",
      name: "dashboard",
      component: DashboardViewer,
      props: (route) => ({
        dashboardId: route.params.dashboardId || null,
      }),
    },
  ],
});

export default router;
