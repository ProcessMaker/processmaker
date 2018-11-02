import Vue from "vue";
import RequestsListing from "./components/RequestsListing";

new Vue({
    data: {
        filter: "",
        title: "All Request"
    },
    el: "#requests-listing",
    components: { RequestsListing },
    methods: {
    }
});
