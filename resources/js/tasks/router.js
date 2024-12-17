import Vue from "vue";
import VueRouter from "vue-router";
import ProcesoBrowser from "./components/ProcesoBrowser";

Vue.use(VueRouter);

const router = new VueRouter({
  mode: "history",
  base: "/tasks",
  routes: [
    {
      name: "proceso-browser",
      path: "/",
      component: ProcesoBrowser,
      props(route) {
        return {
          procesoId: route.query.parametro || null
        };
      }
    }
  ]
});

export default router; 