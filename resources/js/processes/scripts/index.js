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
  },
  mounted() {
    const searchParams = new URLSearchParams(window.location.search);
    if (searchParams.size > 0 && searchParams.get("create") === "true") {
      this.$root.$emit("bv::show::modal", "createScript", "#createScriptModalButton");
    }
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
    reload() {
      this.$refs.listScript.fetch();
    },
  },
});
