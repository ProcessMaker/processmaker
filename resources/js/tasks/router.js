import Vue from "vue";
import VueRouter from "vue-router";
import ProcessBrowser from "./components/ProcessBrowser.vue";
import DashboardViewer from "./components/DashboardViewer.vue";
import Process from "../processes-catalogue/components/Process";

Vue.use(VueRouter);

const router = new VueRouter({
  mode: "history",
  base: "/tasks",
  routes: [
    {
      path: "/:processId?",
      name: "process-browser",
      component: Process,
      props: route => ({
        processId: parseInt(route.params.processId) || null,
        process: null
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