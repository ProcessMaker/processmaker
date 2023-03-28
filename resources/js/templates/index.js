import Vue from "vue";
import ProcessTemplatesListing from "./components/ProcessTemplatesListing";

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
      console.log('SHOW');
      // this.processId = null;
      // this.processModal = true;
    },
    edit(id) {
      console.log('EDIT', id);
      // this.processId = id;
      // this.processModal = true;
    },
    goToImport() {
      window.location = "/processes/import";
    },
    reload() {
      console.log('TEMPLATE INDEX.JS RELAOD');
      this.$refs.templateListing.dataManager([{
        field: "updated_at",
        direction: "desc",
      }]);
    },
    // reload() {
    //   this.$refs.templateListing.fetch();
    // },
  },
});
