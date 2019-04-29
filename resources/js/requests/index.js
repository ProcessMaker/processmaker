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
        requestorOptions: [],
        participantsOptions: ['list', 'of', 'options'],
        advanced: false,
        isLoading: false
    },
    el: "#requests-listing",
    components: { RequestsListing, Multiselect },
    mounted() {
        this.getProcesses('')
        this.getUsers('')
    },
    methods: {
        getProcesses(query) {
            this.isLoading = true
            ProcessMaker.apiClient
                .get("/requests/search?type=process&filter=" + query, {baseURL: ''})
                .then(response => {
                    this.processOptions = response.data;
                    this.isLoading = false
                    setTimeout(3000)
                });
        },
        getUsers(query) {
            this.isLoading = true
            ProcessMaker.apiClient
              .get("/requests/search?type=requester&filter=" + query, {baseURL: ''})
              .then(response => {
                this.requestorOptions = response.data;
                this.isLoading = false
                setTimeout(3000)
              });
        }
    }
});
