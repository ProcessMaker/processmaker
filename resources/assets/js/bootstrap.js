import 'bootstrap-vue/dist/bootstrap-vue.css'
import BootstrapVue from 'bootstrap-vue'
import Echo from 'laravel-echo'

window._ = require('lodash');
window.Popper = require('popper.js').default;

/**
 * We'll load jQuery and the Bootstrap jQuery plugin which provides support
 * for JavaScript based Bootstrap features such as modals and tabs. This
 * code may be modified to fit the specific needs of your application.
 */


window.$ = window.jQuery = require('jquery');

require('bootstrap');


/**
 * Vue is a modern JavaScript library for building interactive web interfaces
 * using reactive data binding and reusable components. Vue's API is clean
 * and simple, leaving you to focus on building your next great project.
 */

window.Vue = require('vue');

window.Vue.use(BootstrapVue);

window.ProcessMaker = {
    /**
     * ProcessMaker Notifications
     */
    notifications: [],
    /**
     * Push a notification.
     *
     * @param {object} notification
     *
     * @returns {void}
     */
    pushNotification(notification) {
        this.notifications.push(notification);
    }
};

/**
 * Create a axios instance which any vue component can bring in to call
 * REST api endpoints through oauth authentication
 *
 */
window.ProcessMaker.apiClient = require('axios');
// Have default endpoint and headers
let token = document.head.querySelector('meta[name="api-token"]');

if (token) {
    window.ProcessMaker.apiClient.defaults.headers.common['Authorization'] = 'Bearer ' + token.content;
} else {
  console.error('ProcessMaker API Token not found in document. API requests via JavaScript may not function.');
}
window.ProcessMaker.apiClient.defaults.baseURL = '/api/1.0/';
// Default to a 5 second timeout, which is an eternity in web app terms
window.ProcessMaker.apiClient.defaults.timeout = 5000;

let userUID = document.head.querySelector('meta[name="user-uid"]');

if(userUID) {
  window.ProcessMaker.user = {
    uid: userUID.content
  }
}

let broadcaster = document.head.querySelector('meta[name="broadcaster"]');
let key = document.head.querySelector('meta[name="broadcasting-key"]');
let host = document.head.querySelector('meta[name="broadcasting-host"]');


window.Echo = new Echo({
  broadcaster: broadcaster.content,
  key: key.content,
  host:host.content
});
