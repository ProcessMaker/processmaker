import Vue from 'vue';
import UsersListing from './components/UsersListing';

new Vue({
    el: '#users-listing',
    data: {
        filter: '',
        userUuid: null,
    },
    components: {
        UsersListing,
    },
    methods: {
        reload() {
            this.$refs.listing.dataManager([{
                field: 'updated_at',
                direction: 'desc'
            }]);
        }
    }
});