import Vue from "vue";
import ProcessesListing from "./components/ProcessesListing";
import CategorySelect from "./categories/components/CategorySelect";

Vue.component('category-select', CategorySelect);

new Vue({
    el: "#processIndex",
    data: {
        filter: "",
        processModal: false,
        processId: null,
    },
    components: {
        ProcessesListing
    },
    methods: {
        show() {
            this.processId = null;
            this.processModal = true;
        },
        edit(id) {
            this.processId = id;
            this.processModal = true;
        },
        goToImport() {
            window.location = "/processes/import"
        },
        reload() {
            this.$refs.processListing.dataManager([{
                field: "updated_at",
                direction: "desc"
            }]);
        }
    }
});
