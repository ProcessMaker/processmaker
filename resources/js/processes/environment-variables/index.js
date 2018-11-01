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
        deleteVariable(data) {
            ProcessMaker.apiClient.delete(`environment_variables/${data.id}`)
                .then((response) => {
                    ProcessMaker.alert("Environment Variable Successfully Deleted", "success");
                    this.reload();
                });
        },
        reload () {
            this.$refs.listVariable.dataManager([
                {
                    field: "updated_at",
                    direction: "desc"
                }
            ]);
        }
    }
});
