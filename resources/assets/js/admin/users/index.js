import Vue from 'vue';
import CreateUser from './components/CreateUser';
import Multiselect from 'vue-multiselect'

Vue.component('multiselect', Multiselect)

new Vue({
    el: '#createUser',
    components: {
        CreateUser,
        Multiselect
    }
});