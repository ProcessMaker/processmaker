import Vue from "vue";
import RequestsListing from "./components/RequestsListing";
import Multiselect from 'vue-multiselect'
import AvatarImage from "../components/AvatarImage";
Vue.component("avatar-image", AvatarImage);

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
        participantsOptions: [],
        advanced: false,
        isLoading: false
    },
    el: "#requests-listing",
    components: { RequestsListing, Multiselect },
    mounted() {
        this.getProcesses('')
        this.getUsers('')
        this.getParticipants('')
    },
    methods: {
        getInitials(firstname, lastname) {
            return firstname.match(/./u)[0] + lastname.match(/./u)[0]
        },
        getProcesses(query) {
            this.isLoading = true
            ProcessMaker.apiClient
                .get("/requests/search?type=process&filter=" + query, { baseURL: '' })
                .then(response => {
                    this.processOptions = response.data;
                    this.isLoading = false
                    setTimeout(3000)
                });
        },
        getUsers(query) {
            this.isLoading = true
            ProcessMaker.apiClient
                .get("/requests/search?type=requester&filter=" + query, { baseURL: '' })
                .then(response => {
                    this.requestorOptions = response.data;
                    this.isLoading = false
                    setTimeout(3000)
                });
        },
        getParticipants(query) {
            this.isLoading = true
            ProcessMaker.apiClient
                .get("/requests/search?type=participants&filter=" + query, { baseURL: '' })
                .then(response => {
                    this.participantsOptions = response.data;
                    this.isLoading = false
                    setTimeout(3000)
                });
        }
    }
});
