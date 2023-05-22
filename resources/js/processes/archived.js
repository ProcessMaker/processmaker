import Vue from "vue";
import ProcessesListing from "./components/ProcessesListing";

Vue.component("ArchivedProcessesList", ProcessesListing);

new Vue({
  el: "#archivedProcess",
  data: {
    filter: "",
    pmql: "",
    urlPmql: "",
  },
  created() {
    const urlParams = new URLSearchParams(window.location.search);
    this.urlPmql = urlParams.get("pmql");
  },
  methods: {
    onNLQConversion(query) {
      this.onChange(query);
      this.reload();
    },
    onChange(query) {
      this.pmql = query;
    },
    reload() {
      this.$refs.processListing.dataManager([{
        field: "updated_at",
        direction: "desc",
      }]);
    },
  },
});
