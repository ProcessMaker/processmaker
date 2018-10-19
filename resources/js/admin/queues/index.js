import Vue from 'vue';
import _ from 'lodash';
import axios from 'axios'
import moment from 'moment';
import router from './router';
import App from 'Horizon/components/App'

window.$ = window.jQuery = require('jquery');
window.Popper = require('popper.js').default;

require('bootstrap');

$('body').tooltip({
    selector: '[data-toggle=tooltip]'
});

Vue.prototype.$http = axios.create({
    baseUrl: ''
}); 

// Add a request interceptor
Vue.prototype.$http.interceptors.request.use(function (config) {
    config.url = config.url.replace('/api/1.0/horizon', '/admin/queues');
    // Do something before request is sent
    return config;
  }, function (error) {
    // Do something with request error
    return Promise.reject(error);
  });


window.Bus = new Vue({name: 'Bus'});

Vue.component('loader', require('Horizon/components/Status/Loader.vue'));

Vue.config.errorHandler = function (err, vm, info) {
    console.error(err);
};

Vue.mixin({
    methods: {
        /**
         * Format the given date with respect to timezone.
         */
        formatDate(unixTime){
            return moment(unixTime * 1000).add(new Date().getTimezoneOffset() / 60)
        },


        /**
         * Extract the job base name.
         */
        jobBaseName(name){
            if (!name.includes('\\')) return name;

            var parts = name.split("\\");

            return parts[parts.length - 1];
        },


        /**
         * Convert to human readable timestamp.
         */
        readableTimestamp(timestamp){
            return this.formatDate(timestamp).format('YY-MM-DD HH:mm:ss');
        },


        /**
         * Convert to human readable timestamp.
         */
        displayableTagsList(tags){
            if (!tags || !tags.length) return '';

            return _.reduce(tags, (s, n)=> {
                return (s ? s + ', ' : '') + _.truncate(n);
            }, '');
        }
    }
});

new Vue({
    el: '#root',

    router,

    /**
     * The component's data.
     */
    data() {
        return {}
    },

    render: h => h(App),
});
