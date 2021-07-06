import Vue from "vue";
import CreateProcessModal from "./components/CreateProcessModal";
import ProcessesListing from "./components/ProcessesListing";
import CategorySelect from "./categories/components/CategorySelect";

Vue.component('category-select', CategorySelect);

new Vue({
    el: "#processIndex",
    data: {
        filter: "",
        processModal: false,
        processId: null,
        showModal: false,
    },
    components: {
        CreateProcessModal,
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
            this.$refs.processListing.fetch();
        }
    }
});
