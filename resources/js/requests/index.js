import Vue from "vue";
import RequestsListing from "./components/RequestsListing";
import Multiselect from 'vue-multiselect'
import AvatarImage from "../components/AvatarImage";
Vue.component("avatar-image", AvatarImage);

new Vue({
    data: {
        filter: "",
        title: "All Request",
        process: [],
        status: [],
        requester: [],
        participants: [],
        processOptions: [],
        statusOptions: [],
        requesterOptions: [],
        participantsOptions: [],
        advanced: false,
        pmql: '',
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
        this.getAll()
    },
    methods: {
        toggleAdvanced() {
          if (this.advanced) {
            this.advanced = false;
          } else {
            this.advanced = true;
            Vue.nextTick().then(() => {
              this.$refs.search_input.focus();
            });
          }
        },
        runSearch(advanced) {
          if (! advanced) {
            this.buildPmql();
          }
          this.$refs.requestList.fetch(null, true);
        },
        buildPmql() {          
          let clauses = [];
          
          //Parse process
          if (this.process.length) {
            let string = '';
            this.process.forEach((process, key) => {
              string += 'request = "' + process.name + '"';
              if (key < this.process.length - 1) string += ' OR ';
            });
            clauses.push(string);
          }
          
          //Parse status
          if (this.status.length) {
            let string = '';
            this.status.forEach((status, key) => {
              string += 'status = "' + status.value + '"';
              if (key < this.status.length - 1) string += ' OR ';
            });
            clauses.push(string);
          }
          
          //Parse requester
          if (this.requester.length) {
            let string = '';
            this.requester.forEach((requester, key) => {
              string += 'requester = "' + requester.username + '"';
              if (key < this.requester.length - 1) string += ' OR ';
            });
            clauses.push(string);
          }
          
          //Parse participants
          if (this.participants.length) {
            let string = '';
            this.participants.forEach((participants, key) => {
              string += 'participant = "' + participants.username + '"';
              if (key < this.participants.length - 1) string += ' OR ';
            });
            clauses.push(string);
          }
          
          this.pmql = '';
          clauses.forEach((string, key) => {
            this.pmql += '(';
            this.pmql += string;
            this.pmql += ')';
            if (key < clauses.length - 1) this.pmql += ' AND ';
          });          
        },
        getInitials(firstname, lastname) {
            if (firstname) {
              return firstname.match(/./u)[0] + lastname.match(/./u)[0]
            } else {
              return null;
            }
        },
        allLoading(value) {
          this.isLoading.process = value;
          this.isLoading.status = value;
          this.isLoading.requester = value;
          this.isLoading.participants = value;
        },
        getAll(){
          this.allLoading(true);
          ProcessMaker.apiClient
              .get("/requests/search?type=all", { baseURL: '' })
              .then(response => {
                  this.processOptions = response.data.process;
                  this.statusOptions = response.data.status;
                  this.requesterOptions = response.data.requester;
                  this.participantsOptions = response.data.participants;
                  this.allLoading(false);
                  setTimeout(3000)
              });
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
