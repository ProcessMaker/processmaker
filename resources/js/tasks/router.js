import { createRouter, createWebHistory } from "vue-router";
import DashboardViewer from "./components/DashboardViewer.vue";
import Process from "../processes-catalogue/components/Process";

const screen = JSON.parse(sessionStorage.getItem("dashboard_screen"));
const formData = JSON.parse(sessionStorage.getItem("dashboard_formData"));

const router = createRouter({
  history: createWebHistory("/inbox"),
  routes: [
    {
      path: "/process/:processId",
      name: "process-browser",
      component: Process,
      props: (route) => ({
        processId: parseInt(route.params.processId) || null,
        process: null,
        ellipsisPermission: window.ProcessMaker.ellipsisPermission,
      }),
    },
    {
      path: "",
      name: "inbox",
      component: Process,
      props: (route) => ({
        processId: null,
        process: null,
        ellipsisPermission: window.ProcessMaker.ellipsisPermission,
      }),
    },
    {
      path: "/dashboard/:dashboardId",
      name: "dashboard",
      component: DashboardViewer,
      props: (route) => ({
        dashboardId: route.params.dashboardId || null,
        screen: route.params.screen || screen,
        formData: route.params.formData || formData,
      }),
    },
  ],
});

export default router;
