import Vue from "vue";
import VariablesListing from "./components/VariablesListing";

// Bootstrap our Variables listing
new Vue({
    el: "#process-variables-listing",
    data: {
        filter: ""
    },
    components: {
        VariablesListing
    },
    methods: {
        __(variable) {
            return __(variable);
        },
        deleteVariable(data) {
            ProcessMaker.apiClient.delete(`environment_variables/${data.id}`)
                .then((response) => {
                    ProcessMaker.alert(__("The environment variable was deleted."), "success");
                    this.reload();
                });
        },
        reload() {
            this.$refs.listVariable.dataManager([
                {
                    field: "updated_at",
                    direction: "desc"
                }
            ]);
        }
    }
});
