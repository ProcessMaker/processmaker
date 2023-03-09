import Vue from "vue";
import ProcessTemplatesListing from "./components/ProcessTemplatesListing";

Vue.component("ProcessTemplatesListing", ProcessTemplatesListing);

new Vue({
  el: "#processTemplatesListing",
  data: {
    filter: "",
  },
  methods: {
    reload() {
     // console.log('RELOAD TEMPLATES.JS');
      //this.$refs.processTemplatesListing.fetch();
      // console.log("TEMPLATE.JS RELOAD");
      // this.$refs.processListing.dataManager([{
      //   field: "updated_at",
      //   direction: "desc",
      // }]);
    },
  },
});
