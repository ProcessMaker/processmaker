import Vue from 'vue';
import UsersListing from './components/UsersListing';
import CreateUser from './components/CreateUser';
import Multiselect from 'vue-multiselect';

Vue.component('multiselect', Multiselect);

new Vue({
    el: '#users-listing',
    data: {
        filter: '',
    },
    components: {
        UsersListing,
        CreateUser,
        Multiselect
    }
});