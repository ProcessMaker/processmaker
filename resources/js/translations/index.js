import Vue from "vue";
import ProcessTranslationsListing from "./components/ProcessTranslationsListing";

new Vue({
  el: "#translationsIndex",
  components: {
    ProcessTranslationsListing,    
  },
  data: {
    filter: "",
  },
  methods: {
    show() {
    },
    edit(id) {
    },
    goToImport() {
      window.location = "/translation/process/import";
    },
    // reload() {
    //   this.$refs.translationListing.dataManager([{
    //     field: "updated_at",
    //     direction: "desc",
    //   }]);
    // },
    reload() {
      this.$refs.translationListing.fetch();
    },
  },
});
