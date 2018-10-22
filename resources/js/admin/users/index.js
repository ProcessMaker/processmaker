import Vue from 'vue';
import UsersListing from './components/UsersListing';
import Multiselect from 'vue-multiselect/src/Multiselect';

Vue.component('multiselect', Multiselect)

new Vue({
    el: '#users-listing',
    data: {
        filter: '',
        userUuid: null,
        options: [{
                title: 'People',
                desc: 'HR',
                img: '/img/avatar-placeholder.gif'
            },
            {
                title: 'Humans',
                desc: 'HR',
                img: '/img/avatar-placeholder.gif'
            },
            {
                title: 'Workers',
                desc: 'HR',
                img: '/img/avatar-placeholder.gif'
            }
        ]
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