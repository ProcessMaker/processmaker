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
      path: "",
      name: "process-browser",
      component: ProcessBrowser,
      props: route => ({
        processId: route.query.process || null
      })
    },
    {
      path: "",
      name: "dashboard",
      component: DashboardViewer,
      props: route => ({
        dashboardId: route.query.dashboard || null
      })
    }
  ]
});

export default router; 