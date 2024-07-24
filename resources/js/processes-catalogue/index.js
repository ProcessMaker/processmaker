import Vue from "vue";
import Process from "./components/Process";
import ProcessesCatalogue from "./components/ProcessesCatalogue";
import ProcessListing from "./components/ProcessListing";

export const EventBus = new Vue();
Vue.use(VueRouter);
const router = new VueRouter({
  mode: "history",
  base: "/process-browser",
  //See https://v3.router.vuejs.org/guide/
  routes: [
    {
      name: "index",
      path: "",
      component: ProcessListing,
      props(route) {
        return {
          categoryId: route.query.categoryId || 'recent',
        };
      }
    },
    {
      name: "show",
      path: "/:processId",
      component: Process,
      props(route) {
        return {
          processId: parseInt(route.params.processId) || null,
          process: route.params.process || null,
        };
      }
    },
  ]
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
  methods: {
  },
});
