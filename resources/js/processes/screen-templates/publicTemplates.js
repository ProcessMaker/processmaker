import Vue from "vue";
import PublicTemplatesListing from "./components/PublicTemplatesListing.vue";

Vue.component("PublicTemplatesListing", PublicTemplatesListing);

const app = new Vue({
  el: "#publicTemplatesIndex",
  components: {
    PublicTemplatesListing,
  },
  data: {
    filter: "",
    pmql: "",
    urlPmql: "",
  },
  methods: {
    onNLQConversion(query) {
      this.onChange(query);
      this.$nextTick(() => {
        this.reload();
      });
    },
    onChange(query) {
      this.pmql = query;
    },
    reload() {
      this.$refs.publicTemplatesListing.fetch();
    },
  },
});

export default app;
