import Vue from "vue";
import { createRouter, createWebHistory } from "vue-router";
import Process from "./components/Process";
import ProcessesCatalogue from "./components/ProcessesCatalogue";
import ProcessListing from "./components/ProcessListing";
import { createPmEventBus } from "../lib/pmEventBus";

export const EventBus = createPmEventBus();
const router = createRouter({
  history: createWebHistory("/process-browser"),
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
          processId: parseInt(route.params.processId) || null,
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
  methods: {
  },
});
