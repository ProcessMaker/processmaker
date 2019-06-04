import Vue from "vue";
import ScriptListing from "./components/ScriptListing";

new Vue({
    el: "#scriptIndex",
    data: {
        filter: ""
    },
    components: {
        ScriptListing
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
            this.$refs.listScript.dataManager([
                {
                    field: "updated_at",
                    direction: "desc"
                }
            ]);
        }
    }
});
