import Vue from "vue";
import ProcessesListing from "./components/ProcessesListing";

Vue.component("archived-processes-list", ProcessesListing);

new Vue({
  el: "#archivedProcess",
  data: {
    filter: ""
  },
  methods: {
    reload () {
      this.$refs.processListing.dataManager([{
        field: "updated_at",
        direction: "desc"
      }]);
    }
  }
});
