import Vue from "vue";
import AuthClientsListing from "./components/AuthClientsListing";

new Vue({
    el: "#listAuthClients",
    components: {
        AuthClientsListing
    },
    methods: {
        resetValues() {
            this.errors = {
                name: null,
                redirect: null
            }
        }
    },

});