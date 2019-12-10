import Vue from 'vue';
import ScreenBuilder from './screen';
import '@fortawesome/fontawesome-free/css/all.min.css';
import Vuex from 'vuex';

Vue.use(Vuex);
const store = new Vuex.Store({ modules: {} });

// Bootstrap our Designer application
new Vue({
    store,
    el: '#screen-container',
    components: { ScreenBuilder }
});
