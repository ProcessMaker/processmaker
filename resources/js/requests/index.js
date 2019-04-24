import Vue from "vue";
import RequestsListing from "./components/RequestsListing";
import Multiselect from 'vue-multiselect'

new Vue({
    data: {
        filter: "",
        title: "All Request",
        value: null,
        options: ['list', 'of', 'options'],
        advanced: false
    },
    el: "#requests-listing",
    components: { RequestsListing, Multiselect },
    methods: {
    }
});
