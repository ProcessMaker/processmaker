import Vue from 'vue';
import UsersListing from './components/UsersListing';
import Multiselect from 'vue-multiselect/src/Multiselect';

Vue.component('multiselect', Multiselect)

new Vue({
            el: '#users-listing',
            data: {
                filter: '',
                userId: null,
                userModal: false
            },
            components: {
                UsersListing,
                ModalCreateUser,
            },
            methods: {
                show() {
                    this.userId = null;
                    this.userModal = true;
                },
                methods: {
                    reload() {
                        this.$refs.listing.dataManager([{
                            field: 'updated_at',
                            direction: 'desc'
                        }]);
                    },
                }
            });