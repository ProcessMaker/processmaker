import Vue from "vue";
import MyTemplatesListing from "./components/MyTemplatesListing.vue";

Vue.component("MyTemplatesListing", MyTemplatesListing);

const app = new Vue({
  el: "#myTemplatesIndex",
  components: {
    MyTemplatesListing,
  },
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
      this.$nextTick(() => {
        this.reload();
      });
    },
    onChange(query) {
      this.pmql = query;
    },
    reload() {
      this.$refs.myTemplatesListing.fetch();
    },
  },
});

export default app;
