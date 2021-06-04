import Vue from "vue";
import ConnectorsListing from "./components/ConnectorsListing";

// Bootstrap our Variables listing
new Vue({
    el: "#process-connectors-listing",
    data: {
        filter: ""
    },
    components: {
        ConnectorsListing
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
                    direction: "desc"
                }
            ]);
        }
    }
});
