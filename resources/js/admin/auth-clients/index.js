import Vue from "vue";
import AuthClientsListing from "./components/AuthClientsListing";

new Vue({
    el: "#listAuthClients",
    data() {
        return {
            authClient: {
                id: null,
                name: "",
                redirect: "",
            },
        }
    },
    components: {AuthClientsListing},
    methods: {
        edit(item) {
            this.authClient = item;
            this.$refs.createEditAuthClient.show()
        },
        create() {
            this.authClient = {id: null, name: '', redirect: ''}
            this.$refs.createEditAuthClient.show()
        }
    },
    computed: {
        modalTitle() {
            return this.authClient.id ? 'Edit Auth Client' : 'Create Auth Client'
        }
    }
});
