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
        requester: null,
        participants: null,
        processOptions: [],
        statusOptions: [],
        requesterOptions: [],
        participantsOptions: [],
        advanced: false,
        isLoading: {
          process: false,
          requester: false,
          status: false,
          participants: false,  
        }
    },
    el: "#requests-listing",
    components: { RequestsListing, Multiselect },
    mounted() {
        this.getStatus('')
        this.getProcesses('')
        this.getRequesters('')
        this.getParticipants('')
    },
    methods: {
        test(option) {
          console.log('OPTION', option);
        },
        getInitials(firstname, lastname) {
            if (firstname) {
              return firstname.match(/./u)[0] + lastname.match(/./u)[0]
            } else {
              return null;
            }
        },
        getStatus() {
          this.isLoading.status = true;
          ProcessMaker.apiClient
              .get("/requests/search?type=status", { baseURL: '' })
              .then(response => {
                  this.statusOptions = response.data;
                  this.isLoading.status = false
                  setTimeout(3000)
              });
        },
        getProcesses(query) {
            this.isLoading.process = true
            ProcessMaker.apiClient
                .get("/requests/search?type=process&filter=" + query, { baseURL: '' })
                .then(response => {
                    this.processOptions = response.data;
                    this.isLoading.process = false
                    setTimeout(3000)
                });
        },
        getRequesters(query) {
            this.isLoading.requester = true
            ProcessMaker.apiClient
                .get("/requests/search?type=requester&filter=" + query, { baseURL: '' })
                .then(response => {
                    this.requesterOptions = response.data;
                    this.isLoading.requester = false
                    setTimeout(3000)
                });
        },
        getParticipants(query) {
            this.isLoading.participants = true
            ProcessMaker.apiClient
                .get("/requests/search?type=participants&filter=" + query, { baseURL: '' })
                .then(response => {
                    this.participantsOptions = response.data;
                    this.isLoading.participants = false
                    setTimeout(3000)
                });
        }
    }
});
