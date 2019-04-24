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
        processOptions: ['list', 'of', 'options'],
        statusOptions: ['list', 'of', 'options'],
        requestorOptions: ['list', 'of', 'options'],
        participantsOptions: ['list', 'of', 'options'],
        advanced: false
    },
    el: "#requests-listing",
    components: { RequestsListing, Multiselect },
    methods: {
    }
});
