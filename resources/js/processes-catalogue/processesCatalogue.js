import Vue from "vue";
import VueRouter from "vue-router";
import Process from "./components/Process.vue";
import ProcessesCatalogue from "./components/ProcessesCatalogue.vue";
import ProcessListing from "./components/ProcessListing.vue";

Vue.use(VueRouter);

const router = new VueRouter({
  mode: "history",
  base: "/process-browser",
  routes: [
    {
      name: "index",
      path: "",
      component: ProcessListing,
      props(route) {
        return {
          categoryId: route.query.categoryId || "recent",
        };
      },
    },
    {
      name: "show",
      path: "/:processId",
      component: Process,
      props(route) {
        return {
          processId: parseInt(route.params.processId, 10) || null,
          process: route.params.process || null,
        };
      },
    },
  ],
});

new Vue({
  el: "#processes-catalogue",
  components: { ProcessesCatalogue },
  router,
  data() {
    return {
      permission: window.ProcessMaker.permission,
      isDocumenterInstalled: window.ProcessMaker.isDocumenterInstalled,
      filteredCategories: null,
      mobileSearchVisible: false,
    };
  },
});
