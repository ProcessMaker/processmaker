import Vue from "vue";
import VueRouter from "vue-router";
import DashboardViewer from "./components/DashboardViewer.vue";
import Process from "../processes-catalogue/components/Process";

Vue.use(VueRouter);

const router = new VueRouter({
  mode: "history",
  base: "/inbox",
  routes: [
    {
      path: "/process/:processId",
      name: "process-browser",
      component: Process,
      props: route => ({
        processId: parseInt(route.params.processId) || null,
        process: null,
        ellipsisPermission: window.ProcessMaker.ellipsisPermission
      })
    },
    {
      path: "/dashboard/:dashboardId",
      name: "dashboard",
      component: DashboardViewer,
      props: route => ({
        dashboardId: route.params.dashboardId || null
      })
    }
  ]
});

export default router; 
