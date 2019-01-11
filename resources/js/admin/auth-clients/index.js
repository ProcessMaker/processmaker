import Vue from "vue";
import AuthClientsListing from "./components/AuthClientsListing";

new Vue({
    el: "#listAuthClients",
    data: {
        filter: ""
    },
    components: {AuthClientsListing},
    methods: {
    }
});
