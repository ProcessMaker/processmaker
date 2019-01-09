import Vue from 'vue';
import VuePassword from "vue-password";
import UsersListing from './components/UsersListing';

Vue.component("vue-password", VuePassword);

new Vue({
    el: '#users-listing',
    data: {
        filter: '',
    },
    components: {UsersListing},
    methods: {
        reload() {
            this.$refs.listing.dataManager([{
                field: 'updated_at',
                direction: 'desc'
            }]);
        },
    }
});
