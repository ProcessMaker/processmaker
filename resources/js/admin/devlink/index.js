import Vue from "vue";
import DevLink from "./components/DevLink";
import Index from "./components/Index";

Vue.use(VueRouter);
const router = new VueRouter({
  mode: "history",
  base: "/admin/devlink",
  //See https://v3.router.vuejs.org/guide/
  routes: [
    {
      name: "index",
      path: "/",
      component: Index,
    },
  ]
});

new Vue({
  el: "#devlink",
  router,
  components: { DevLink },
});
