import Vue from "vue";
import ProcessesListing from "./components/ProcessesListing";

Vue.component("TemplatesListing", ProcessesListing);

new Vue({
  el: "#templatesListing",
  data: {
    filter: "",
  },
  methods: {
    reload() {
      this.$refs.processListing.dataManager([{
        field: "updated_at",
        direction: "desc",
      }]);
    },
  },
});
