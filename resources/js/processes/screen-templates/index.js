import Vue from "vue";
import MyTemplatesListing from "../screen-templates/components/MyTemplatesListing";

new Vue({
  el: "#myTemplatesIndex",
  components: {
    MyTemplatesListing,
  },
  data: {
    filter: "",
    pmql: "",
    urlPmql: "",
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
        this.$refs.myTemplatesListing.fetch();
      },
  },
});
