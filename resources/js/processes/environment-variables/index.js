import Vue from "vue";
import CreateEnvironmentVariableModal from "./components/CreateEnvironmentVariableModal";
import VariablesListing from "./components/VariablesListing";

// Bootstrap our Variables listing
new Vue({
  el: "#process-variables-listing",
  components: {
    CreateEnvironmentVariableModal,
    VariablesListing,
  },
  data: {
    filter: "",
  },
  methods: {
    __(variable) {
      return __(variable);
    },
    deleteVariable(data) {
      ProcessMaker.apiClient.delete(`environment_variables/${data.id}`)
        .then((response) => {
          ProcessMaker.alert(this.$t("The environment variable was deleted."), "success");
          this.reload();
        });
    },
    reload() {
      this.$refs.listVariable.dataManager([
        {
          field: "updated_at",
          direction: "desc",
        },
      ]);
    },
  },
});
