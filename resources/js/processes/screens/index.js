import Vue from "vue";
import ScreenListing from "./components/ScreenListing";
import CreateScreenModal from "./components/CreateScreenModal";
import CategorySelect from "../categories/components/CategorySelect";

Vue.component("CategorySelect", CategorySelect);

new Vue({
  el: "#screenIndex",
  components: {
    CreateScreenModal,
    ScreenListing,
  },
  data: {
    filter: "",
    pmql: "",
    urlPmql: "",
    screenModal: false,
    screenId: null,
  },
  created() {
    const urlParams = new URLSearchParams(window.location.search);
    this.urlPmql = urlParams.get("pmql");
  },
  methods: {
    goToImport() {
      window.location = "/designer/screens/import";
    },
    show() {
      this.screenId = null;
      this.screenModal = true;
    },
    onNLQConversion(query) {
      this.onChange(query);
      this.reload();
    },
    onChange(query) {
      this.pmql = query;
    },
    reload() {
      this.$refs.screenListing.dataManager([
        {
          field: "updated_at",
          direction: "desc",
        },
      ]);
    },
  },
});
