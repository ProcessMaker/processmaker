require('./bootstrap');
let Vue = window.Vue;

import avatar from './components/common/avatar';

new Vue({
    el: '#topnav-avatar',
    components: {
        avatar
    }
})

new Vue({
    el: '#sidebarMenu',
    data: {
        expanded: false,
        icon: '/img/sm-wht-icon.png',
        logo: '/img/sm-wht-logo.png'
    },
    methods: {
        toggleVisibility() {
            this.expanded = !this.expanded;
        }
    }
})

$("#menu-toggle").click(function (e) {
    e.preventDefault();
    $("#wrapper").toggleClass("toggled");
});

// Import our requests modal
import requestModal from './components/requests/modal'
import notifications from './components/requests/notifications'

// Setup our request modal and wire it to our button in the navbar
new Vue({
    el: '#navbar-request-button',
    components: {
        requestModal
    }
})

/**
 * Setup the notifications block
 */
new Vue({
    el: '#navbar-notifications-button',
    components: {
        notifications
    },
    data() {
        return {
            messages: ProcessMaker.notifications
        }
    }
})

// Setup our api client interceptor to handle errors and reflect the error 
// in our skin.
window.ProcessMaker.apiClient.interceptors.response.use(function (response) {
    // No need to handle success responses
    return response;
  }, function (error) {
      if (error.response.status != 422 && error.response.status != 404){
        // Replace our content div with our error div
        // Remove our #content-inner
        let elem = document.getElementById('content-inner');
        elem.parentNode.removeChild(elem);
        // Now show our #api-error div
        elem  = document.getElementById('api-error');
        elem.setAttribute('style', 'display: block');
      }
    return Promise.reject(error);
  });
