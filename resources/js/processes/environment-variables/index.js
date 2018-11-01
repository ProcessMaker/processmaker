import Vue from "vue";
import VariablesListing from "./components/VariablesListing";

// Bootstrap our Variables listing
new Vue({
    el: "#process-variables-listing",
    data: {
        filter: ""
    },
    components: {
        VariablesListing
    }
});
