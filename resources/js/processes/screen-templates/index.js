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
  mounted() {
    // const searchParams = new URLSearchParams(window.location.search);
    //   if (searchParams.size > 0 && searchParams.get("create") === "true") {
    //     this.$root.$emit("bv::show::modal", "createScreen", "#createScreenModalButton");
    //   }
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
