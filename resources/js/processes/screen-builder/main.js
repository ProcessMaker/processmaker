import Vue from 'vue'
import screenBuilder from './screen'
import '@fortawesome/fontawesome-free/css/all.min.css';
import i18next from 'i18next';
import VueI18Next from '@panter/vue-i18next';
import Vuex from 'vuex';

// Allow strings to be wrapped in $t(...) for translating
// outside this package. This standalone app just returns
// the English string
Vue.use(VueI18Next)
i18next.init({lng: 'en'})
Vue.mixin({ i18n: new VueI18Next(i18next) })
Vue.use(Vuex);

const store = new Vuex.Store({ modules: {} });

// Bootstrap our Designer application
new Vue({
    store,
    el: '#screen-container',
    components: { screenBuilder }
});
