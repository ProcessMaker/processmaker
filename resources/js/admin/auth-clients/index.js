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
                secret: "",
            },
            errors: {
                name: null,
                redirect: null,
            },
        }
    },
    components: {AuthClientsListing},
    methods: {
        edit(item) {
            this.authClient = item
            this.$refs.createEditAuthClient.show()
        },
        create() {
            this.authClient = {id: null, name: '', redirect: '', secret: null}
            this.$refs.createEditAuthClient.show()
        },
        save(event) {
            event.preventDefault()
            this.loading = true
            let method = 'POST'
            let url = '/oauth/clients'
            if (this.authClient.id) {
                // Do an update
                method = 'PUT',
                url = url + '/' + this.authClient.id
            }
            ProcessMaker.apiClient({
                method,
                url,
                baseURL: '/',
                data: this.authClient,
            }).then(response => {
                this.$refs.createEditAuthClient.hide()
                this.$refs.authClientList.fetch()
                this.resetValues();
                this.loading = false
            }).catch(error => {
                this.errors = error.response.data.errors
            });
        },
        resetValues() {
            this.errors = { name: null, redirect: null }
        }
    },
    computed: {
        modalTitle() {
            return this.authClient.id ? 'Edit Auth Client' : 'Create Auth Client'
        }
    }
});
