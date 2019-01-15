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
        save() {
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
                this.loading = false
            });
        }
    },
    computed: {
        modalTitle() {
            return this.authClient.id ? 'Edit Auth Client' : 'Create Auth Client'
        }
    }
});
