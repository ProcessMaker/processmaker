import Vue from "vue";
import ScriptListing from "./components/ScriptListing";
import CreateScriptModal from "./components/CreateScriptModal";
import CategorySelect from "../categories/components/CategorySelect";

Vue.component("CategorySelect", CategorySelect);

new Vue({
  el: "#scriptIndex",
  components: {
    CreateScriptModal,
    ScriptListing,
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
    __(variable) {
      return __(variable);
    },
    deleteScript(data) {
      ProcessMaker.apiClient.delete(`scripts/${data.id}`)
        .then((response) => {
          ProcessMaker.alert(this.$t("The script was deleted."), "success");
          this.reload();
        });
    },
    onNLQConversion(query) {
      this.onChange(query);
      this.reload();
    },
    onChange(query) {
      this.pmql = query;
    },
    reload() {
      this.$refs.listScript.fetch();
    },
  },
});
