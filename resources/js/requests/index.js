import Vue from "vue";
import RequestsListing from "./components/RequestsListing";
import Multiselect from 'vue-multiselect'

new Vue({
    data: {
        filter: "",
        title: "All Request",
        processes: null,
        status: null,
        requestor: null,
        participants: null,
        processOptions: [],
        statusOptions: ['list', 'of', 'options'],
        requestorOptions: ['list', 'of', 'options'],
        participantsOptions: ['list', 'of', 'options'],
        advanced: false,
        isLoading: false
    },
    el: "#requests-listing",
    components: { RequestsListing, Multiselect },
    mounted() {
        this.getProcesses('')
    },
    methods: {
        getProcesses(query) {
            this.isLoading = true
            ProcessMaker.apiClient
                .get("/processes?&per_page=50" + "&filter=" + query)
                .then(response => {
                    this.processOptions = response.data.data;
                    this.isLoading = false
                    setTimeout(3000)
                });
        }
    }
});
