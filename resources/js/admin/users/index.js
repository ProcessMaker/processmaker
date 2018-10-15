import Vue from 'vue';
import UsersListing from './components/UsersListing';
import ModalCreateUser from "./components/modal-user-add.vue";

new Vue({
    el: '#users-listing',
    data: {
        filter: '',
        userUuid: null,
        userModal: false
    },
    components: {
        UsersListing,
        ModalCreateUser,
    },
    methods: {
        show() {
            this.userUuid = null;
            this.userModal = true;
        },
        reload() {
            this.$refs.listing.dataManager([
                {
                    field: 'updated_at',
                    direction: 'desc'
                }
            ]);
        }
    }
});