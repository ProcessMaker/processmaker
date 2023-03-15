import Vue from "vue";
import ProcessTemplatesListing from "./components/ProcessTemplatesListing";
import CategorySelect from "./categories/components/CategorySelect";

Vue.component("CategorySelect", CategorySelect);

new Vue({
  el: "#templatesIndex",
  components: {
    ProcessTemplatesListing,    
  },
  data: {
    filter: "",
  },
  methods: {
    show() {
      this.processId = null;
      this.processModal = true;
    },
    edit(id) {
      this.processId = id;
      this.processModal = true;
    },
    goToImport() {
      window.location = "/template/process/import";
    },
    reload() {
      this.$refs.templateListing.fetch();
    },
  },
});
